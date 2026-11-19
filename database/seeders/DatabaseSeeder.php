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
            ['nama_router' => 'RO_ZTE'],
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
            'RO_POS1' => ['nama_router' => 'RO_POS1',  'ip_host' => '10.10.1.1', 'subnet_oct3' => 101],
            'RO_FK' => ['nama_router' => 'RO_FK',    'ip_host' => '10.10.1.2', 'subnet_oct3' => 102],
            'RO_GAYUH' => ['nama_router' => 'RO_GAYUH', 'ip_host' => '10.10.1.3', 'subnet_oct3' => 103],
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
                        'queue_mk_id' => '*'.strtoupper(dechex($oct3 * 10 + ($oct4 - 189))),
                        'queue_name' => $queueName,
                        'status' => 'available',
                        'psb_order_id' => null,
                    ]
                );
            }
        }

        $salesData = [
            ['kode_ip' => 'LITE',   'kode_pppoe' => '01', 'nama' => 'GAYUH (Tanpa Sales)', 'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'JB',     'kode_pppoe' => '02', 'nama' => 'MIFTAH',              'router' => $distRouters['RO_FK']->id],
            ['kode_ip' => 'JN',     'kode_pppoe' => '03', 'nama' => 'JABRIK',              'router' => $distRouters['RO_FK']->id],
            ['kode_ip' => 'PS',     'kode_pppoe' => '04', 'nama' => 'MAMAT S',             'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'RK',     'kode_pppoe' => '05', 'nama' => 'ROKI',                'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'KN',     'kode_pppoe' => '06', 'nama' => 'SUBUR',               'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'SALES',  'kode_pppoe' => '07', 'nama' => 'MALIK',               'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'PN',     'kode_pppoe' => '08', 'nama' => 'PAY',                 'router' => $distRouters['RO_FK']->id],
            ['kode_ip' => 'SEWAN',  'kode_pppoe' => '09', 'nama' => 'HABUDIN',             'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'SANURI', 'kode_pppoe' => '10', 'nama' => 'SANURI',              'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'BOY',    'kode_pppoe' => '11', 'nama' => 'RW BOY',              'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'ND',     'kode_pppoe' => '12', 'nama' => 'NADIH',               'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'SALES',  'kode_pppoe' => '13', 'nama' => 'MALIK-KOLE',          'router' => $distRouters['RO_POS1']->id],
            ['kode_ip' => 'PGB',    'kode_pppoe' => '14', 'nama' => 'PGB',                 'router' => $distRouters['RO_FK']->id],
            ['kode_ip' => 'SN',     'kode_pppoe' => '15', 'nama' => 'SN',                  'router' => $distRouters['RO_FK']->id],
            ['kode_ip' => 'FK',     'kode_pppoe' => '16', 'nama' => 'FKDS',                'router' => $distRouters['RO_FK']->id],
            ['kode_ip' => 'KD',     'kode_pppoe' => '17', 'nama' => 'SUMARDI',              'router' => $distRouters['RO_FK']->id],
        ];

        foreach ($salesData as $s) {
            Sale::updateOrCreate(
                ['kode_sales_pppoe' => $s['kode_pppoe']],
                [
                    'nama_sales' => $s['nama'],
                    'kode_sales_ip' => $s['kode_ip'],
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
            ['nama_odp' => 'GSA-01', 'kode_odp' => '91', 'status_upgrade' => 'belum_upgrade', 'last_seq' => 0],
            ['nama_odp' => 'GSB-01', 'kode_odp' => '92', 'status_upgrade' => 'belum_upgrade', 'last_seq' => 0],
            ['nama_odp' => 'GSMA-01', 'kode_odp' => '93', 'status_upgrade' => 'belum_upgrade', 'last_seq' => 0],
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
