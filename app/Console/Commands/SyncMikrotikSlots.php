<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Router;
use App\Models\RouterIpSlot;
use App\Services\MikrotikProvisionerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class SyncMikrotikSlots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:sync-slots {--router_id= : ID router distribusi spesifik yang ingin disinkronkan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi slot IP Simple Queue dari router distribusi MikroTik (non-PPPoE) secara bulk';

    /**
     * Execute the console command.
     */
    public function handle(MikrotikProvisionerService $provisioner): int
    {
        $routerIdOption = $this->option('router_id');

        $query = Router::query()->where('is_pppoe_server', false);

        if ($routerIdOption !== null && $routerIdOption !== '') {
            $query->where('id', (int) $routerIdOption);
        }

        $routers = $query->get();

        if ($routers->isEmpty()) {
            $this->warn('Tidak ada router distribusi (is_pppoe_server = false) yang ditemukan.');
            return self::SUCCESS;
        }

        $hasFailure = false;

        foreach ($routers as $router) {
            $this->info("Menyinkronkan router: {$router->nama_router} ({$router->ip_host}:{$router->api_port})...");

            try {
                $client = $provisioner->createClient($router);

                // 1. Bulk API Call #1: Fetch all Simple Queues
                $queues = $provisioner->queryAndRead($client, new Query('/queue/simple/print'));

                // 2. Bulk API Call #2: Fetch all ARP entries
                $arpEntries = $provisioner->queryAndRead($client, new Query('/ip/arp/print'));

                // 3. Bulk API Call #3: Fetch all EXPIRED firewall address-list entries
                $expiredQuery = (new Query('/ip/firewall/address-list/print'))
                    ->where('list', 'EXPIRED');
                $expiredEntries = $provisioner->queryAndRead($client, $expiredQuery);

                // Build O(1) lookup set for Static ARP IPs (dynamic === 'false')
                $staticArpIps = [];
                foreach ($arpEntries as $arp) {
                    if (! is_array($arp) || empty($arp['address'])) {
                        continue;
                    }

                    $isDynamic = strtolower((string) ($arp['dynamic'] ?? 'false')) === 'true';
                    if (! $isDynamic) {
                        $staticArpIps[trim((string) $arp['address'])] = true;
                    }
                }

                // Build O(1) lookup set for active EXPIRED IPs (disabled === 'false')
                $activeExpiredIps = [];
                foreach ($expiredEntries as $entry) {
                    if (! is_array($entry) || empty($entry['address'])) {
                        continue;
                    }

                    $isDisabled = strtolower((string) ($entry['disabled'] ?? 'false')) === 'true';
                    if (! $isDisabled) {
                        $activeExpiredIps[trim((string) $entry['address'])] = true;
                    }
                }

                $now = now();
                $upsertRows = [];
                $availableCount = 0;
                $usedCount = 0;

                foreach ($queues as $queue) {
                    if (! is_array($queue) || empty($queue['.id']) || empty($queue['name']) || empty($queue['target'])) {
                        continue;
                    }

                    $ip = $this->extractSingleHostIp((string) $queue['target']);
                    if ($ip === null) {
                        continue;
                    }

                    $queueName = trim((string) $queue['name']);
                    $queueMkId = trim((string) $queue['.id']);

                    // Slot is available IF AND ONLY IF:
                    // 1. Simple Queue name matches /^CLIENT-\d+$/i
                    // 2. IP is NOT in Static ARP (dynamic === 'false')
                    // 3. IP is NOT active in EXPIRED address list
                    $matchesClientPattern = preg_match('/^CLIENT-\d+$/i', $queueName) === 1;
                    $inStaticArp = isset($staticArpIps[$ip]);
                    $inActiveExpired = isset($activeExpiredIps[$ip]);

                    $isAvailable = $matchesClientPattern && ! $inStaticArp && ! $inActiveExpired;
                    $status = $isAvailable ? 'available' : 'used';

                    if ($isAvailable) {
                        $availableCount++;
                    } else {
                        $usedCount++;
                    }

                    $upsertRows[] = [
                        'router_id' => $router->id,
                        'ip_address' => $ip,
                        'queue_mk_id' => $queueMkId,
                        'queue_name' => $queueName,
                        'status' => $status,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($upsertRows, 500) as $chunk) {
                    RouterIpSlot::upsert(
                        $chunk,
                        ['router_id', 'ip_address'],
                        ['queue_mk_id', 'queue_name', 'status', 'updated_at']
                    );
                }

                $this->info(
                    "Selesai {$router->nama_router}: {$availableCount} slot available, {$usedCount} slot used."
                );
            } catch (\Throwable $e) {
                $hasFailure = true;
                $this->error("Gagal sinkronisasi router {$router->nama_router}: {$e->getMessage()}");
                Log::error('SyncMikrotikSlots gagal pada router.', [
                    'router_id' => $router->id,
                    'nama_router' => $router->nama_router,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Extract a valid single IPv4 address from a RouterOS Simple Queue target string (e.g. "172.16.94.200/32").
     * Returns null for multi-target strings or non-/32 subnets.
     */
    private function extractSingleHostIp(string $rawTarget): ?string
    {
        $trimmed = trim($rawTarget);

        if ($trimmed === '' || str_contains($trimmed, ',')) {
            return null;
        }

        if (str_contains($trimmed, '/')) {
            [$ipPart, $cidrPart] = explode('/', $trimmed, 2);
            if ($cidrPart !== '32') {
                return null;
            }
            $candidate = $ipPart;
        } else {
            $candidate = $trimmed;
        }

        return filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            ? $candidate
            : null;
    }
}
