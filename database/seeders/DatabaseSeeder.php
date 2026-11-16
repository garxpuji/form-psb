<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Odp;
use App\Models\Paket;
use App\Models\Router;
use App\Models\RouterIpSlot;
use App\Models\Sale;
use App\Models\TelegramGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        TelegramGroup::updateOrCreate(
            ['nama_grup' => 'ALL TEAM PSB'],
            [
                'chat_id' => '-1001000000001',
                'thread_id' => null,
                'is_all_team' => true,
            ]
        );

        $teamGroups = [];
        for ($i = 1; $i <= 4; $i++) {
            $teamGroups[$i] = TelegramGroup::updateOrCreate(
                ['nama_grup' => "TEAM {$i}"],
                [
                    'chat_id' => sprintf('-100100000000%d', $i + 1),
                    'thread_id' => null,
                    'is_all_team' => false,
                ]
            );
        }

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin NOC',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'telegram_group_id' => null,
                'is_active' => true,
            ]
        );

        $sampleTechnicians = [
            ['username' => 'andri', 'name' => 'andri', 'role' => 'teknisi', 'group' => $teamGroups[1]->id],
            ['username' => 'budi', 'name' => 'budi', 'role' => 'teknisi', 'group' => $teamGroups[2]->id],
            ['username' => 'candra', 'name' => 'candra', 'role' => 'teknisi', 'group' => $teamGroups[3]->id],
            ['username' => 'deni', 'name' => 'deni', 'role' => 'teknisi', 'group' => $teamGroups[4]->id],
            ['username' => 'eko', 'name' => 'eko', 'role' => 'umum', 'group' => null],
        ];

        foreach ($sampleTechnicians as $tech) {
            User::updateOrCreate(
                ['username' => $tech['username']],
                [
                    'name' => $tech['name'],
                    'password' => Hash::make('password'),
                    'role' => $tech['role'],
                    'telegram_group_id' => $tech['group'],
                    'is_active' => true,
                ]
            );
        }

        Router::updateOrCreate(
            ['nama_router' => 'CCR-PPPOE-PUSAT'],
            [
                'ip_host' => '10.10.0.1',
                'api_port' => 8728,
                'username' => 'api-psb',
                'password' => 'secret-router-api',
                'lan_interface' => null,
                'is_pppoe_server' => true,
            ]
        );

        $distRoutersData = [
            'PAY' => ['nama_router' => 'DIST-MIKROTIK-PAY', 'ip_host' => '10.10.1.1', 'subnet_oct3' => 94],
            'KD'  => ['nama_router' => 'DIST-MIKROTIK-KD',  'ip_host' => '10.10.1.2', 'subnet_oct3' => 103],
            'PN'  => ['nama_router' => 'DIST-MIKROTIK-PN',  'ip_host' => '10.10.1.3', 'subnet_oct3' => 102],
            'JN'  => ['nama_router' => 'DIST-MIKROTIK-JN',  'ip_host' => '10.10.1.4', 'subnet_oct3' => 104],
            'JB'  => ['nama_router' => 'DIST-MIKROTIK-JB',  'ip_host' => '10.10.1.5', 'subnet_oct3' => 105],
            'RSV' => ['nama_router' => 'DIST-MIKROTIK-06',  'ip_host' => '10.10.1.6', 'subnet_oct3' => 106],
        ];

        $distRouters = [];
        foreach ($distRoutersData as $key => $rData) {
            $router = Router::updateOrCreate(
                ['nama_router' => $rData['nama_router']],
                [
                    'ip_host' => $rData['ip_host'],
                    'api_port' => 8728,
                    'username' => 'api-psb',
                    'password' => 'secret-router-api',
                    'lan_interface' => 'bridge-local',
                    'is_pppoe_server' => false,
                ]
            );
            $distRouters[$key] = $router;

            $oct3 = $rData['subnet_oct3'];
            for ($oct4 = 190; $oct4 <= 195; $oct4++) {
                $ip = "172.16.{$oct3}.{$oct4}";
                $queueName = "CLIENT-{$oct3}{$oct4}";
                RouterIpSlot::updateOrCreate(
                    [
                        'router_id' => $router->id,
                        'ip_address' => $ip,
                    ],
                    [
                        'queue_mk_id' => '*' . strtoupper(dechex($oct3 * 10 + ($oct4 - 189))),
                        'queue_name' => $queueName,
                        'status' => 'available',
                        'psb_order_id' => null,
                    ]
                );
            }
        }

        $salesData = [
            ['kode_ip' => 'PAY', 'kode_pppoe' => '01', 'nama' => 'Sales PAY', 'router' => $distRouters['PAY']->id],
            ['kode_ip' => 'KD',  'kode_pppoe' => '02', 'nama' => 'Sales KD',  'router' => $distRouters['KD']->id],
            ['kode_ip' => 'PN',  'kode_pppoe' => '03', 'nama' => 'Sales PN',  'router' => $distRouters['PN']->id],
            ['kode_ip' => 'JN',  'kode_pppoe' => '04', 'nama' => 'Sales JN',  'router' => $distRouters['JN']->id],
            ['kode_ip' => 'JB',  'kode_pppoe' => '05', 'nama' => 'Sales JB',  'router' => $distRouters['JB']->id],
        ];

        foreach ($salesData as $s) {
            Sale::updateOrCreate(
                ['kode_sales_ip' => $s['kode_ip']],
                [
                    'nama_sales' => $s['nama'],
                    'kode_sales_pppoe' => $s['kode_pppoe'],
                    'ip_router_id' => $s['router'],
                ]
            );
        }

        $paketsData = [
            ['nama_paket' => '10mbps', 'queue_max_limit' => '10M/10M', 'pppoe_profile' => 'PAKET-10M'],
            ['nama_paket' => '15mbps', 'queue_max_limit' => '15M/15M', 'pppoe_profile' => 'PAKET-15M'],
            ['nama_paket' => '20mbps', 'queue_max_limit' => '20M/20M', 'pppoe_profile' => 'PAKET-20M'],
            ['nama_paket' => '30mbps', 'queue_max_limit' => '30M/30M', 'pppoe_profile' => 'PAKET-30M'],
            ['nama_paket' => '50mbps', 'queue_max_limit' => '50M/50M', 'pppoe_profile' => 'PAKET-50M'],
        ];

        foreach ($paketsData as $p) {
            Paket::updateOrCreate(
                ['nama_paket' => $p['nama_paket']],
                [
                    'queue_max_limit' => $p['queue_max_limit'],
                    'pppoe_profile' => $p['pppoe_profile'],
                ]
            );
        }

        $odpsData = [
            ['nama_odp' => 'U15 B', 'kode_odp' => '12', 'status_upgrade' => 'upgrade',       'last_seq' => 767],
            ['nama_odp' => 'U15 A', 'kode_odp' => '12', 'status_upgrade' => 'upgrade',       'last_seq' => 120],
            ['nama_odp' => 'P11 B', 'kode_odp' => '11', 'status_upgrade' => 'upgrade',       'last_seq' => 45],
            ['nama_odp' => 'K04 C', 'kode_odp' => '04', 'status_upgrade' => 'belum_upgrade', 'last_seq' => 0],
            ['nama_odp' => 'K08 A', 'kode_odp' => '08', 'status_upgrade' => 'belum_upgrade', 'last_seq' => 0],
            ['nama_odp' => 'J03 A', 'kode_odp' => '03', 'status_upgrade' => 'belum_upgrade', 'last_seq' => 0],
        ];

        foreach ($odpsData as $o) {
            Odp::updateOrCreate(
                ['nama_odp' => $o['nama_odp']],
                [
                    'kode_odp' => $o['kode_odp'],
                    'status_upgrade' => $o['status_upgrade'],
                    'last_seq' => $o['last_seq'],
                ]
            );
        }
    }
}
