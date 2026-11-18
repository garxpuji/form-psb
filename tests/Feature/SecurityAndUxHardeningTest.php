<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Admin\HistoryController;
use App\Models\Odp;
use App\Models\Paket;
use App\Models\PsbOrder;
use App\Models\Sale;
use App\Models\User;
use App\Services\MikrotikProvisionerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class SecurityAndUxHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('services.telegram.bot_token', 'test-bot-token');
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_ktp_and_installation_photos_are_stored_privately_and_protected_by_admin_auth(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $teknisi = User::where('username', 'andri')->firstOrFail();
        $admin = User::where('username', 'admin')->firstOrFail();
        $odp = Odp::where('nama_odp', 'U15 B')->firstOrFail();
        $paket = Paket::where('nama_paket', '10mbps')->firstOrFail();

        $mockProvisioner = Mockery::mock(MikrotikProvisionerService::class)->makePartial();
        $mockProvisioner->shouldReceive('provisionOrder')->once()->andReturn('12_01_00_00768_PELANGGAN');
        $this->app->instance(MikrotikProvisionerService::class, $mockProvisioner);

        $this->actingAs($teknisi)->post('/psb', [
            'nama_pelanggan' => 'Siti Aminah',
            'alamat' => 'Jl. Kenanga 9',
            'shareloc' => 'https://www.google.com/maps?q=-6.2,106.8',
            'no_tlp' => '081234567890',
            'odp_id' => $odp->id,
            'port' => 4,
            'paket_id' => $paket->id,
            'mac_address' => 'AABBCCDDEEFF',
            'brand_router' => 'ZTE F609',
            'jarak_kabel' => '90m',
            'signal_odp' => '-17.2 dBm',
            'signal_ont' => '-18.0 dBm',
            'foto_mac_ont' => UploadedFile::fake()->image('mac.jpg', 400, 300),
            'foto_odp' => UploadedFile::fake()->image('odp.jpg', 400, 300),
            'foto_kertas_registrasi' => UploadedFile::fake()->image('reg.jpg', 400, 300),
            'foto_ktp_pelanggan' => UploadedFile::fake()->image('ktp.jpg', 400, 300),
            'foto_rumah_pelanggan' => UploadedFile::fake()->image('rumah.jpg', 400, 300),
        ])->assertRedirect(route('psb.create'));

        $order = PsbOrder::latest('id')->firstOrFail();

        // Must exist on private local disk and NOT on public disk
        Storage::disk('local')->assertExists($order->foto_ktp_pelanggan);
        Storage::disk('public')->assertMissing($order->foto_ktp_pelanggan);

        $photoUrl = route('admin.orders.photo', ['order' => $order->id, 'field' => 'foto_ktp_pelanggan']);

        // Guest redirected to login
        auth()->logout();
        $this->get($photoUrl)->assertRedirect(route('login'));

        // Non-admin technician receives 403 Forbidden
        $this->actingAs($teknisi)->get($photoUrl)->assertForbidden();

        // Admin receives 200 with private no-store headers
        $adminResponse = $this->actingAs($admin)->get($photoUrl);
        $adminResponse->assertOk();
        $this->assertStringContainsString('no-store', (string) $adminResponse->headers->get('Cache-Control'));
        $adminResponse->assertHeader('X-Content-Type-Options', 'nosniff');

        // Unknown field returns 404
        $this->actingAs($admin)
            ->get(route('admin.orders.photo', ['order' => $order->id, 'field' => 'password']))
            ->assertNotFound();
    }

    public function test_telegram_webhook_rejects_missing_or_invalid_secret_token_when_configured(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        Config::set('services.telegram.webhook_secret', 'super-secret-noc-token');

        $payload = [
            'message' => [
                'message_id' => 10,
                'text' => '/cekid',
                'chat' => ['id' => -100123456, 'title' => 'GROUP TEST'],
            ],
        ];

        $this->postJson('/api/telegram/webhook', $payload)->assertForbidden();

        $this->postJson('/api/telegram/webhook', $payload, [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-token',
        ])->assertForbidden();

        $this->postJson('/api/telegram/webhook', $payload, [
            'X-Telegram-Bot-Api-Secret-Token' => 'super-secret-noc-token',
        ])->assertOk()->assertJson(['ok' => true]);
    }

    public function test_login_rate_limiting_and_inactive_user_anti_enumeration(): void
    {
        $user = User::where('username', 'budi')->firstOrFail();
        $user->update(['is_active' => false]);

        // Wrong password on inactive user MUST NOT reveal inactive status (anti-enumeration)
        $wrongPassResponse = $this->post('/login', [
            'username' => 'budi',
            'password' => 'wrong-password',
        ]);
        $wrongPassResponse->assertSessionHasErrors('username');
        $this->assertSame(
            'Username atau password yang Anda masukkan salah.',
            session('errors')->first('username')
        );

        // Exceeding 5 failed attempts triggers rate limiting lockout
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', [
                'username' => 'budi',
                'password' => 'wrong-password',
            ]);
        }

        $lockedResponse = $this->post('/login', [
            'username' => 'budi',
            'password' => 'password',
        ]);
        $lockedResponse->assertSessionHasErrors('username');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan masuk gagal',
            session('errors')->first('username')
        );
    }

    public function test_admin_self_lockout_protection_and_stronger_password_policy(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        // Weak password rejected (< 8 chars for teknisi, < 12 chars for admin)
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Teknisi Baru',
            'username' => 'tekbaru',
            'password' => '12345',
            'role' => 'teknisi',
            'is_active' => '1',
        ])->assertSessionHasErrors('password');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Admin Baru',
            'username' => 'admbaru',
            'password' => '12345678',
            'role' => 'admin',
            'is_active' => '1',
        ])->assertSessionHasErrors('password');

        // Self-deactivation or role demotion blocked
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'username' => $admin->username,
            'role' => 'teknisi',
            'is_active' => '1',
        ])->assertSessionHas('error');

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_router_ssrf_host_validation_and_csv_formula_sanitization(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        // Loopback & link-local metadata IPs blocked
        foreach (['127.0.0.1', 'localhost', '169.254.169.254', '0.0.0.0'] as $forbiddenHost) {
            $this->actingAs($admin)->post(route('admin.routers.store'), [
                'nama_router' => 'TEST-SSRF',
                'ip_host' => $forbiddenHost,
                'api_port' => 8729,
                'username' => 'api',
                'password' => 'secret1234',
                'lan_interface' => 'bridge-local',
                'is_pppoe_server' => false,
            ])->assertSessionHasErrors('ip_host');
        }

        // CSV Formula Injection sanitization preserves optical dBm but neutralizes Excel formulas
        $this->assertSame("'=cmd|' /C calc'!A0", HistoryController::sanitizeCsvCell("=cmd|' /C calc'!A0"));
        $this->assertSame("'+HYPERLINK(\"http://evil.test\")", HistoryController::sanitizeCsvCell('+HYPERLINK("http://evil.test")'));
        $this->assertSame("'-10+20", HistoryController::sanitizeCsvCell('-10+20'));
        $this->assertSame('-17.4 dBm', HistoryController::sanitizeCsvCell('-17.4 dBm'));
        $this->assertSame('-19.2', HistoryController::sanitizeCsvCell('-19.2'));
    }

    public function test_idempotent_retry_guard_safe_shareloc_and_security_headers(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $odp = Odp::where('nama_odp', 'U15 B')->firstOrFail();
        $paket = Paket::where('nama_paket', '10mbps')->firstOrFail();
        $sale = Sale::where('kode_sales_ip', 'PAY')->firstOrFail();

        $order = PsbOrder::create([
            'user_id' => $admin->id,
            'nama_pelanggan' => 'Sudah Sukses',
            'alamat' => 'Jl. Aman 1',
            'shareloc' => 'https://phishing-domain.example/fake-maps',
            'no_tlp' => '0811111111',
            'odp_id' => $odp->id,
            'port' => 1,
            'paket_id' => $paket->id,
            'mac_address' => 'AA:BB:CC:DD:EE:99',
            'brand_router' => 'ZTE',
            'jarak_kabel' => '50m',
            'signal_odp' => '-16 dBm',
            'signal_ont' => '-17 dBm',
            'sales_id' => $sale->id,
            'nama_teknisi' => 'andri',
            'foto_mac_ont' => 'psb-photos/mac.jpg',
            'foto_odp' => 'psb-photos/odp.jpg',
            'foto_kertas_registrasi' => 'psb-photos/reg.jpg',
            'foto_ktp_pelanggan' => 'psb-photos/ktp.jpg',
            'foto_rumah_pelanggan' => 'psb-photos/rumah.jpg',
            'tipe_koneksi' => 'pppoe',
            'hasil_poe_ip' => '12_01_01_00768_SUDAH_SUKSES',
            'status_sync' => 'success',
        ]);

        // Untrusted shareloc host must NOT link directly to external phishing domain
        $this->assertStringStartsWith(
            'https://www.google.com/maps/search/?api=1&query=',
            $order->safeSharelocUrl()
        );

        // Retrying an already-succeeded order must not call provisionOrder again
        $mockProvisioner = Mockery::mock(MikrotikProvisionerService::class)->makePartial();
        $mockProvisioner->shouldNotReceive('provisionOrder');
        $this->app->instance(MikrotikProvisionerService::class, $mockProvisioner);

        $this->actingAs($admin)
            ->post(route('admin.orders.retry', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Verify Security Headers & absence of external Play CDN scripts
        $pageResponse = $this->actingAs($admin)->get(route('psb.create'));
        $pageResponse->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('cdn.jsdelivr.net', false);
    }
}
