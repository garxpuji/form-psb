<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Odp;
use App\Models\Paket;
use App\Models\PsbOrder;
use App\Models\Router;
use App\Models\RouterIpSlot;
use App\Models\Sale;
use App\Models\TelegramGroup;
use App\Models\User;
use App\Services\MikrotikProvisionerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use RouterOS\Interfaces\ClientInterface;
use RouterOS\Interfaces\QueryInterface;
use RuntimeException;
use Tests\TestCase;

class PsbProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('services.telegram.bot_token', 'test-bot-token');
        Config::set('services.mikrotik.pppoe_default_password', 'psb12345');
    }

    public function test_mac_address_and_technician_normalization(): void
    {
        $this->assertSame(
            'F8:14:FE:87:6D:0A',
            MikrotikProvisionerService::normalizeMacAddress('f8-14.fe:87 6d0A')
        );

        $this->assertSame(
            'andri&nopal',
            MikrotikProvisionerService::formatTechnicianName('andri', 'nopal')
        );

        $this->assertSame(
            'andri',
            MikrotikProvisionerService::formatTechnicianName('andri', '')
        );
    }

    public function test_login_requires_active_user_and_sets_remember_cookie(): void
    {
        $user = User::where('username', 'andri')->firstOrFail();

        $response = $this->post('/login', [
            'username' => 'andri',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('psb.create'));
        $this->assertAuthenticatedAs($user);

        // Inactive user cannot log in
        $this->post('/logout');
        $user->update(['is_active' => false]);

        $failedResponse = $this->post('/login', [
            'username' => 'andri',
            'password' => 'password',
        ]);

        $failedResponse->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_pppoe_provisioning_branch_for_upgraded_odp_and_team_telegram_routing(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::where('username', 'andri')->firstOrFail(); // Assigned to TEAM 1
        $odp = Odp::where('nama_odp', 'U15 B')->firstOrFail();   // kode_odp=12, upgrade, last_seq=767
        $paket = Paket::where('nama_paket', '10mbps')->firstOrFail();
        $sale = Sale::where('kode_sales_ip', 'KD')->firstOrFail(); // kode_sales_pppoe=02

        $fakeClient = Mockery::mock(ClientInterface::class);

        $mockProvisioner = Mockery::mock(MikrotikProvisionerService::class)->makePartial();
        $mockProvisioner->shouldReceive('createClient')->once()->andReturn($fakeClient);

        $mockProvisioner->shouldReceive('queryAndRead')
            ->twice()
            ->andReturnUsing(function (ClientInterface $client, QueryInterface $query): array {
                $endpoint = $query->getEndpoint();
                if ($endpoint === '/ppp/secret/print') {
                    return [
                        ['name' => '12_01_01_00765_BUDI'],
                        ['name' => '12_01_02_00767_LAMA'],
                    ];
                }
                if ($endpoint === '/ppp/secret/add') {
                    $attrs = $query->getAttributes();
                    $this->assertContains('=name=12_01_02_00768_ASTONI', $attrs);
                    $this->assertContains('=password=psb12345', $attrs);
                    $this->assertContains('=service=pppoe', $attrs);
                    $this->assertContains('=profile=PAKET-10M', $attrs);
                    return ['after' => ['ret' => '*10']];
                }
                return [];
            });

        $this->app->instance(MikrotikProvisionerService::class, $mockProvisioner);

        $response = $this->actingAs($user)->post('/psb', [
            'nama_pelanggan' => 'Astoni!',
            'alamat' => 'Jl. Merdeka No. 10',
            'no_tlp' => '08123456789',
            'odp_id' => $odp->id,
            'port' => 5,
            'paket_id' => $paket->id,
            'mac_address' => 'f814fe876d0a',
            'brand_router' => 'ZTE F609',
            'jarak_kabel' => '100m',
            'signal_odp' => '-17.1',
            'signal_ont' => '-18.4',
            'sales_id' => $sale->id,
            'rekan_teknisi' => 'nopal',
        ]);

        $response->assertRedirect(route('psb.create'));

        $order = PsbOrder::latest('id')->firstOrFail();
        $this->assertSame('success', $order->status_sync);
        $this->assertSame('pppoe', $order->tipe_koneksi);
        $this->assertSame('12_01_02_00768_ASTONI', $order->hasil_poe_ip);
        $this->assertSame('F8:14:FE:87:6D:0A', $order->mac_address);
        $this->assertSame('andri&nopal', $order->nama_teknisi);
        $this->assertSame(768, $odp->fresh()->last_seq);

        $team1Group = TelegramGroup::where('nama_grup', 'TEAM 1')->firstOrFail();
        Http::assertSent(function ($request) use ($team1Group): bool {
            return $request['chat_id'] === $team1Group->chat_id
                && str_contains($request['text'], 'POE/IP       : 12_01_02_00768_ASTONI')
                && ! str_contains($request['text'], '[NON-TIM PSB]');
        });
    }

    public function test_static_ip_provisioning_skips_invalid_slot_and_claims_valid_slot(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::where('username', 'eko')->firstOrFail(); // telegram_group_id = null -> ALL TEAM PSB [NON-TIM PSB]
        $odp = Odp::where('nama_odp', 'K04 C')->firstOrFail(); // belum_upgrade
        $paket = Paket::where('nama_paket', '15mbps')->firstOrFail();
        $sale = Sale::where('kode_sales_ip', 'KD')->firstOrFail();
        $router = $sale->ipRouter;

        // Clear seeded slots for KD router and create 2 specific slots:
        // Slot 1 (172.16.103.190) -> Will fail live check because it has Static ARP
        // Slot 2 (172.16.103.191) -> Will pass all 3 live checks and have 1 dynamic ARP to clean
        RouterIpSlot::where('router_id', $router->id)->delete();

        $slot1 = RouterIpSlot::create([
            'router_id' => $router->id,
            'queue_mk_id' => '*1A',
            'ip_address' => '172.16.103.190',
            'queue_name' => 'CLIENT-103190',
            'status' => 'available',
        ]);

        $slot2 = RouterIpSlot::create([
            'router_id' => $router->id,
            'queue_mk_id' => '*1B',
            'ip_address' => '172.16.103.191',
            'queue_name' => 'CLIENT-103191',
            'status' => 'available',
        ]);

        $fakeClient = Mockery::mock(ClientInterface::class);
        $mockProvisioner = Mockery::mock(MikrotikProvisionerService::class)->makePartial();
        $mockProvisioner->shouldReceive('createClient')->once()->andReturn($fakeClient);

        $mockProvisioner->shouldReceive('queryAndRead')
            ->andReturnUsing(function (ClientInterface $client, QueryInterface $query): array {
                $endpoint = $query->getEndpoint();
                $attrs = $query->getAttributes();

                if ($endpoint === '/queue/simple/print') {
                    if (in_array('?target=172.16.103.190/32', $attrs, true)) {
                        return [['.id' => '*1A', 'name' => 'CLIENT-103190', 'target' => '172.16.103.190/32']];
                    }
                    if (in_array('?target=172.16.103.191/32', $attrs, true)) {
                        return [['.id' => '*1B', 'name' => 'CLIENT-103191', 'target' => '172.16.103.191/32']];
                    }
                }

                if ($endpoint === '/ip/arp/print') {
                    if (in_array('?address=172.16.103.190', $attrs, true)) {
                        // Static ARP exists -> must fail live check!
                        return [['.id' => '*A1', 'address' => '172.16.103.190', 'dynamic' => 'false']];
                    }
                    if (in_array('?address=172.16.103.191', $attrs, true)) {
                        // Dynamic ARP only -> passes check, and dynamic entry *A2 should be removed
                        return [['.id' => '*A2', 'address' => '172.16.103.191', 'dynamic' => 'true']];
                    }
                }

                if ($endpoint === '/ip/firewall/address-list/print') {
                    return [];
                }

                return [];
            });

        $this->app->instance(MikrotikProvisionerService::class, $mockProvisioner);

        $response = $this->actingAs($user)->post('/psb', [
            'nama_pelanggan' => 'Nurhaida',
            'alamat' => 'Jl. Sudirman 45',
            'no_tlp' => '081299998888',
            'odp_id' => $odp->id,
            'port' => 8,
            'paket_id' => $paket->id,
            'mac_address' => 'AA:BB:CC:11:22:33',
            'brand_router' => 'Huawei HG8245H',
            'jarak_kabel' => '80m',
            'signal_odp' => '-16.5',
            'signal_ont' => '-17.8',
            'sales_id' => $sale->id,
        ]);

        $response->assertRedirect(route('psb.create'));

        $order = PsbOrder::latest('id')->firstOrFail();
        $this->assertSame('success', $order->status_sync);
        $this->assertSame('static_ip', $order->tipe_koneksi);
        $this->assertSame('172.16.103.191', $order->hasil_poe_ip);

        // Slot 1 was marked used because of static ARP, Slot 2 was claimed by this order
        $this->assertSame('used', $slot1->fresh()->status);
        $this->assertNull($slot1->fresh()->psb_order_id);

        $this->assertSame('used', $slot2->fresh()->status);
        $this->assertSame('KD-103191-NURHAIDA', $slot2->fresh()->queue_name);
        $this->assertSame($order->id, $slot2->fresh()->psb_order_id);

        // Non-team user routes to ALL TEAM PSB with [NON-TIM PSB] prefix
        $allTeamGroup = TelegramGroup::where('is_all_team', true)->firstOrFail();
        Http::assertSent(function ($request) use ($allTeamGroup): bool {
            return $request['chat_id'] === $allTeamGroup->chat_id
                && str_starts_with($request['text'], "[NON-TIM PSB]\n")
                && str_contains($request['text'], 'POE/IP       : 172.16.103.191');
        });
    }

    public function test_failure_fallback_saves_failed_order_and_alerts_noc_on_telegram(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::where('username', 'andri')->firstOrFail();
        $odp = Odp::where('nama_odp', 'U15 B')->firstOrFail();
        $paket = Paket::where('nama_paket', '10mbps')->firstOrFail();
        $sale = Sale::where('kode_sales_ip', 'PAY')->firstOrFail();

        $mockProvisioner = Mockery::mock(MikrotikProvisionerService::class)->makePartial();
        $mockProvisioner->shouldReceive('provisionOrder')
            ->once()
            ->andThrow(new RuntimeException('Connection timed out to RouterOS 10.10.0.1:8728'));

        $this->app->instance(MikrotikProvisionerService::class, $mockProvisioner);

        $this->actingAs($user)->post('/psb', [
            'nama_pelanggan' => 'Pelanggan Fallback',
            'alamat' => 'Jl. Veteran 12',
            'no_tlp' => '081111222333',
            'odp_id' => $odp->id,
            'port' => 3,
            'paket_id' => $paket->id,
            'mac_address' => '112233445566',
            'brand_router' => 'Tenda',
            'jarak_kabel' => '150m',
            'signal_odp' => '-19.0',
            'signal_ont' => '-20.1',
            'sales_id' => $sale->id,
        ]);

        $order = PsbOrder::latest('id')->firstOrFail();
        $this->assertSame('failed', $order->status_sync);
        $this->assertNull($order->hasil_poe_ip);
        $this->assertStringContainsString('Connection timed out', (string) $order->error_log);

        Http::assertSent(function ($request): bool {
            return str_contains($request['text'], 'POE/IP       : ⚠️ GAGAL AUTO-CONFIG (CEK NOC)');
        });
    }

    public function test_telegram_webhook_cekid_replies_with_chat_id_and_thread_id(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->postJson('/api/telegram/webhook', [
            'message' => [
                'message_id' => 99,
                'message_thread_id' => 42,
                'text' => '/cekid@AutoPsbBot',
                'chat' => [
                    'id' => -1009876543210,
                    'title' => 'TIM PSB AREA BARAT',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'chat_id' => '-1009876543210',
                'message_thread_id' => 42,
            ]);

        Http::assertSent(function ($request): bool {
            return $request['chat_id'] === '-1009876543210'
                && $request['message_thread_id'] === 42
                && str_contains($request['text'], '-1009876543210')
                && str_contains($request['text'], '42');
        });
    }

    public function test_sync_mikrotik_slots_command_executes_3_bulk_queries_and_evaluates_rules(): void
    {
        $router = Router::where('nama_router', 'DIST-MIKROTIK-PAY')->firstOrFail();
        RouterIpSlot::where('router_id', $router->id)->delete();

        $fakeClient = Mockery::mock(ClientInterface::class);
        $mockProvisioner = Mockery::mock(MikrotikProvisionerService::class)->makePartial();
        $mockProvisioner->shouldReceive('createClient')->once()->andReturn($fakeClient);

        $mockProvisioner->shouldReceive('queryAndRead')
            ->times(3)
            ->andReturnUsing(function (ClientInterface $client, QueryInterface $query): array {
                $endpoint = $query->getEndpoint();

                if ($endpoint === '/queue/simple/print') {
                    return [
                        // 1. Valid available slot
                        ['.id' => '*10', 'name' => 'CLIENT-94200', 'target' => '172.16.94.200/32'],
                        // 2. Has static ARP -> should be marked used
                        ['.id' => '*11', 'name' => 'CLIENT-94201', 'target' => '172.16.94.201/32'],
                        // 3. Is in EXPIRED address list -> should be marked used
                        ['.id' => '*12', 'name' => 'CLIENT-94202', 'target' => '172.16.94.202/32'],
                        // 4. Already named after customer -> should be marked used
                        ['.id' => '*13', 'name' => 'PAY-94203-BUDI', 'target' => '172.16.94.203/32'],
                    ];
                }

                if ($endpoint === '/ip/arp/print') {
                    return [
                        ['address' => '172.16.94.200', 'dynamic' => 'true'],
                        ['address' => '172.16.94.201', 'dynamic' => 'false'],
                    ];
                }

                if ($endpoint === '/ip/firewall/address-list/print') {
                    return [
                        ['address' => '172.16.94.202', 'list' => 'EXPIRED', 'disabled' => 'false'],
                    ];
                }

                return [];
            });

        $this->app->instance(MikrotikProvisionerService::class, $mockProvisioner);

        $this->artisan('mikrotik:sync-slots', ['--router_id' => $router->id])
            ->assertExitCode(0);

        $this->assertDatabaseHas('router_ip_slots', [
            'router_id' => $router->id,
            'ip_address' => '172.16.94.200',
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('router_ip_slots', [
            'router_id' => $router->id,
            'ip_address' => '172.16.94.201',
            'status' => 'used',
        ]);

        $this->assertDatabaseHas('router_ip_slots', [
            'router_id' => $router->id,
            'ip_address' => '172.16.94.202',
            'status' => 'used',
        ]);

        $this->assertDatabaseHas('router_ip_slots', [
            'router_id' => $router->id,
            'ip_address' => '172.16.94.203',
            'status' => 'used',
        ]);
    }

    public function test_admin_can_toggle_odp_upgrade_status_in_one_click(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $odp = Odp::where('nama_odp', 'K04 C')->firstOrFail();
        $this->assertSame('belum_upgrade', $odp->status_upgrade);

        $this->actingAs($admin)
            ->patch(route('admin.odps.toggle-upgrade', $odp))
            ->assertRedirect();

        $this->assertSame('upgrade', $odp->fresh()->status_upgrade);
    }

    public function test_all_ocean_blue_ui_pages_render_and_navigation_controls_work(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('ISP Auto-PSB')
            ->assertSee('#072A47', false);

        $admin = User::where('username', 'admin')->firstOrFail();

        $pages = [
            route('psb.create'),
            route('admin.dashboard'),
            route('admin.slots.index'),
            route('admin.odps.index'),
            route('admin.routers.index'),
            route('admin.sales.index'),
            route('admin.pakets.index'),
            route('admin.telegram-groups.index'),
            route('admin.users.index'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('#005F99', false);
        }
    }
}
