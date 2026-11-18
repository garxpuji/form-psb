<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Odp;
use App\Models\PsbOrder;
use App\Models\Router;
use App\Models\RouterIpSlot;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Interfaces\ClientInterface;
use RouterOS\Interfaces\QueryInterface;
use RouterOS\Query;
use RuntimeException;

class MikrotikProvisionerService
{
    public static function normalizeMacAddress(string $rawMac): string
    {
        $hexOnly = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $rawMac));

        if (strlen($hexOnly) !== 12) {
            throw new InvalidArgumentException('MAC Address harus terdiri dari tepat 12 karakter heksadesimal.');
        }

        return implode(':', str_split($hexOnly, 2));
    }

    public static function formatTechnicianName(string $userName, ?string $rekanTeknisi = null): string
    {
        $primary = trim($userName);
        $partner = trim((string) $rekanTeknisi);

        if ($partner === '') {
            return $primary;
        }

        return "{$primary}&{$partner}";
    }

    public static function formatDateComment(): string
    {
        return 'PSB '.now()->format('j-n-Y');
    }

    public function createClient(Router $router): ClientInterface
    {
        $timeout = (int) config('services.mikrotik.timeout', 10);
        $attempts = (int) config('services.mikrotik.attempts', 2);
        $port = (int) $router->api_port;
        $useSsl = $port === Config::PORT_SSL;

        $config = new Config([
            'host' => $router->ip_host,
            'port' => $port,
            'ssl' => $useSsl,
            'user' => $router->username,
            'pass' => (string) $router->password,
            'timeout' => $timeout,
            'socket_timeout' => $timeout,
            'attempts' => $attempts,
            'delay' => 1,
        ]);

        return new Client($config);
    }

    public function provisionOrder(PsbOrder $order): string
    {
        $order->loadMissing(['odp', 'paket', 'sales.ipRouter']);

        if ($order->odp === null) {
            throw new RuntimeException('Data ODP tidak ditemukan untuk order PSB ini.');
        }

        if ($order->odp->status_upgrade === 'upgrade') {
            return $this->provisionPppoe($order);
        }

        return $this->provisionStaticIp($order);
    }

    protected function provisionPppoe(PsbOrder $order): string
    {
        $pppoeRouter = Router::where('is_pppoe_server', true)->first();

        if ($pppoeRouter === null) {
            throw new RuntimeException('Router PPPoE Server utama (is_pppoe_server = true) belum dikonfigurasi.');
        }

        $client = $this->createClient($pppoeRouter);

        return DB::transaction(function () use ($order, $client): string {
            // Lock the ODP row so concurrent submissions never generate duplicate PPPoE sequence numbers.
            $lockedOdp = Odp::where('id', $order->odp_id)
                ->lockForUpdate()
                ->first();

            if ($lockedOdp === null) {
                throw new RuntimeException('ODP tidak ditemukan saat mengunci sequence PPPoE.');
            }

            $secrets = $this->queryAndRead($client, new Query('/ppp/secret/print'));

            $prefix = "{$lockedOdp->kode_odp}_01_";
            $mikrotikMaxSeq = 0;

            foreach ($secrets as $secret) {
                if (! is_array($secret) || ! isset($secret['name'])) {
                    continue;
                }

                $secretName = (string) $secret['name'];
                if (! str_starts_with($secretName, $prefix)) {
                    continue;
                }

                $segments = explode('_', $secretName);
                if (isset($segments[3]) && is_numeric($segments[3])) {
                    $seqVal = (int) $segments[3];
                    if ($seqVal > $mikrotikMaxSeq) {
                        $mikrotikMaxSeq = $seqVal;
                    }
                }
            }

            $nextSeq = max($mikrotikMaxSeq, (int) $lockedOdp->last_seq) + 1;
            $paddedSeq = str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);

            $cleanCustomerName = $this->cleanCustomerNameForPppoe($order->nama_pelanggan);
            $kodeSalesPppoe = $order->sales?->kode_sales_pppoe ?? '00';

            $pppoeUsername = "{$lockedOdp->kode_odp}_01_{$kodeSalesPppoe}_{$paddedSeq}_{$cleanCustomerName}";
            $defaultPassword = (string) config('services.mikrotik.pppoe_default_password', '123456');
            $comment = self::formatDateComment()." | ODP:{$lockedOdp->nama_odp} P:{$order->port}";

            $addQuery = (new Query('/ppp/secret/add'))
                ->equal('name', $pppoeUsername)
                ->equal('password', $defaultPassword)
                ->equal('service', 'pppoe')
                ->equal('profile', $order->paket->pppoe_profile)
                ->equal('comment', $comment);

            $this->queryAndRead($client, $addQuery);

            $lockedOdp->update([
                'last_seq' => $nextSeq,
            ]);

            return $pppoeUsername;
        });
    }

    protected function provisionStaticIp(PsbOrder $order): string
    {
        $sales = $order->sales;
        $router = $sales?->ipRouter;

        if ($router === null) {
            $router = Router::where('is_pppoe_server', false)
                ->whereHas('ipSlots', fn ($q) => $q->where('status', 'available'))
                ->orderBy('id')
                ->first()
                ?? Router::where('is_pppoe_server', false)->orderBy('id')->first();
        }

        if ($router === null) {
            throw new RuntimeException('Tidak ada router distribusi Static IP yang tersedia.');
        }

        if (empty($router->lan_interface)) {
            throw new RuntimeException("Interface LAN (lan_interface) belum diatur pada router {$router->nama_router}.");
        }

        $formattedMac = self::normalizeMacAddress($order->mac_address);
        $client = $this->createClient($router);

        return DB::transaction(function () use ($order, $sales, $router, $formattedMac, $client): string {
            while (true) {
                $slot = RouterIpSlot::where('router_id', $router->id)
                    ->where('status', 'available')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->first();

                if ($slot === null) {
                    throw new RuntimeException("Slot Simple Queue kosong habis pada router {$router->nama_router}");
                }

                $ip = $slot->ip_address;

                $queueQuery = (new Query('/queue/simple/print'))
                    ->where('target', "{$ip}/32");
                $queues = $this->queryAndRead($client, $queueQuery);

                $liveQueue = null;
                foreach ($queues as $q) {
                    if (is_array($q) && isset($q['.id'], $q['name'])) {
                        $liveQueue = $q;
                        break;
                    }
                }

                $queueValid = $liveQueue !== null
                    && preg_match('/^CLIENT-\d+$/i', (string) $liveQueue['name']) === 1;

                $arpQuery = (new Query('/ip/arp/print'))
                    ->where('address', $ip);
                $arpEntries = $this->queryAndRead($client, $arpQuery);

                $hasStaticArp = false;
                $dynamicArpIds = [];

                foreach ($arpEntries as $arp) {
                    if (! is_array($arp)) {
                        continue;
                    }

                    $isDynamic = strtolower((string) ($arp['dynamic'] ?? 'false')) === 'true';
                    if (! $isDynamic) {
                        $hasStaticArp = true;
                    } elseif (isset($arp['.id'])) {
                        $dynamicArpIds[] = (string) $arp['.id'];
                    }
                }

                $expiredQuery = (new Query('/ip/firewall/address-list/print'))
                    ->where('list', 'EXPIRED')
                    ->where('address', $ip);
                $expiredEntries = $this->queryAndRead($client, $expiredQuery);

                $isIsolated = false;
                foreach ($expiredEntries as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }

                    $isDisabled = strtolower((string) ($entry['disabled'] ?? 'false')) === 'true';
                    if (! $isDisabled) {
                        $isIsolated = true;
                        break;
                    }
                }

                // Skip slots that were manually assigned in Winbox or isolated since the last background sync.
                if (! $queueValid || $hasStaticArp || $isIsolated) {
                    $slot->update([
                        'status' => 'used',
                        'queue_name' => $liveQueue['name'] ?? $slot->queue_name,
                    ]);

                    continue;
                }

                $octets = explode('.', $ip);
                if (count($octets) !== 4) {
                    throw new RuntimeException("Format IP address tidak valid pada slot: {$ip}");
                }
                $octet34 = $octets[2].$octets[3];

                $cleanName = $this->cleanCustomerNameForQueue($order->nama_pelanggan);

                $salesPrefix = strtoupper($sales?->kode_sales_ip ?? 'NON');
                $queueName = "{$salesPrefix}-{$octet34}-{$cleanName}";

                $queueId = (string) $liveQueue['.id'];
                $setQueueQuery = (new Query('/queue/simple/set'))
                    ->equal('.id', $queueId)
                    ->equal('name', $queueName)
                    ->equal('max-limit', $order->paket->queue_max_limit)
                    ->equal('comment', self::formatDateComment())
                    ->equal('disabled', 'no');

                $this->queryAndRead($client, $setQueueQuery);

                // Remove dynamic ARP entries first so RouterOS does not reject the static ARP as a duplicate.
                foreach ($dynamicArpIds as $dynamicArpId) {
                    $removeArpQuery = (new Query('/ip/arp/remove'))
                        ->equal('.id', $dynamicArpId);
                    $this->queryAndRead($client, $removeArpQuery);
                }

                $addArpQuery = (new Query('/ip/arp/add'))
                    ->equal('address', $ip)
                    ->equal('mac-address', $formattedMac)
                    ->equal('interface', (string) $router->lan_interface)
                    ->equal('comment', $queueName);

                $this->queryAndRead($client, $addArpQuery);

                $slot->update([
                    'queue_mk_id' => $queueId,
                    'queue_name' => $queueName,
                    'status' => 'used',
                    'psb_order_id' => $order->id,
                ]);

                return $ip;
            }
        });
    }

    /**
     * @return array<int|string, mixed>
     */
    public function queryAndRead(ClientInterface $client, QueryInterface $query): array
    {
        $response = $client->query($query)->read();

        if (! is_array($response)) {
            throw new RuntimeException('Respons tidak valid dari RouterOS API.');
        }

        if (isset($response['after']['message']) && is_string($response['after']['message'])) {
            throw new RuntimeException('RouterOS API Error: '.$response['after']['message']);
        }

        return $response;
    }

    public function cleanCustomerNameForPppoe(string $rawName): string
    {
        $alnumSpaces = (string) preg_replace('/[^A-Za-z0-9 ]/', '', $rawName);
        $normalized = (string) preg_replace('/\s+/', '_', trim($alnumSpaces));

        return $normalized !== '' ? strtoupper($normalized) : 'PELANGGAN';
    }

    public function cleanCustomerNameForQueue(string $rawName): string
    {
        $alnumSpaces = (string) preg_replace('/[^A-Za-z0-9 ]/', '', $rawName);
        $normalized = (string) preg_replace('/\s+/', ' ', trim($alnumSpaces));

        return $normalized !== '' ? strtoupper($normalized) : 'PELANGGAN';
    }
}
