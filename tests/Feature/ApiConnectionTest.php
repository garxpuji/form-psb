<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Router;
use App\Models\TelegramGroup;
use App\Models\User;
use App\Services\MikrotikProvisionerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use RouterOS\Interfaces\ClientInterface;
use RouterOS\Interfaces\QueryInterface;
use Tests\TestCase;

class ApiConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_test_a_saved_router_using_a_read_only_query(): void
    {
        $this->withoutVite();
        $router = $this->router();
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('query')->once()->with(Mockery::on(
            fn (QueryInterface $query): bool => $query->getQuery() === ['/system/resource/print', '=.proplist=version']
        ))->andReturnSelf();
        $client->shouldReceive('read')->once()->andReturn([['version' => '7.18.2 (stable)']]);

        $this->partialMock(MikrotikProvisionerService::class)
            ->shouldReceive('createClient')->once()
            ->with(Mockery::on(fn (Router $saved): bool => $saved->is($router)))
            ->andReturn($client);

        $this->actingAs(User::factory()->admin()->create())
            ->followingRedirects()
            ->post(route('admin.routers.test-connection', $router))
            ->assertOk()
            ->assertSee('Koneksi Berhasil')
            ->assertSee('API MikroTik Router Uji terhubung (RouterOS 7.18.2 (stable)).')
            ->assertSee(route('admin.routers.test-connection', $router));
    }

    public function test_router_api_failure_does_not_expose_raw_diagnostics(): void
    {
        $router = $this->router();
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('query')->once()->andReturnSelf();
        $client->shouldReceive('read')->once()->andReturn(['after' => ['message' => 'private router diagnostic']]);
        $this->partialMock(MikrotikProvisionerService::class)
            ->shouldReceive('createClient')->once()->andReturn($client);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.routers.test-connection', $router))
            ->assertRedirectToRoute('admin.routers.index')
            ->assertSessionHas('connection_test', [
                'successful' => false,
                'message' => 'API MikroTik Router Uji gagal merespons. Periksa host, port, dan izin baca akun API.',
            ]);
    }

    public function test_telegram_test_uses_get_me_and_displays_the_bot_username(): void
    {
        $this->withoutVite();
        TelegramGroup::create(['nama_grup' => 'Grup Utama Uji', 'chat_id' => '-1001234567001', 'is_all_team' => true]);
        TelegramGroup::create(['nama_grup' => 'Grup Tim Uji', 'chat_id' => '-1001234567002', 'is_all_team' => false]);
        config(['services.telegram.bot_token' => '123456:example']);
        $apiUrl = 'https://api.telegram.org/bot123456:example/getMe';
        Http::preventStrayRequests()->fake([
            $apiUrl => Http::response(['ok' => true, 'result' => ['username' => 'psb_test_bot']]),
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->followingRedirects()
            ->post(route('admin.telegram-groups.test-connection'))
            ->assertOk()
            ->assertSee('API Telegram terhubung sebagai @psb_test_bot.')
            ->assertSee(route('admin.telegram-groups.test-connection'))
            ->assertSeeInOrder(['Grup Utama Uji', 'Edit', 'Tes Koneksi Bot', 'Grup Tim Uji', 'Edit', 'Tes Koneksi Bot'])
            ->assertDontSee('Endpoint Webhook')
            ->assertDontSee('TELEGRAM_WEBHOOK_SECRET')
            ->assertDontSee('/api/telegram/webhook')
            ->assertDontSee('123456:example');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request->url() === $apiUrl);
    }

    public function test_telegram_test_reports_a_missing_or_rejected_token(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $url = route('admin.telegram-groups.test-connection');
        Http::preventStrayRequests()->fake([
            'https://api.telegram.org/bot123456:example/getMe' => Http::response(['ok' => false], 401),
        ]);

        config(['services.telegram.bot_token' => '']);
        $this->post($url)->assertSessionHas('connection_test', [
            'successful' => false,
            'message' => 'TELEGRAM_BOT_TOKEN belum diatur.',
        ]);
        Http::assertNothingSent();

        config(['services.telegram.bot_token' => '123456:example']);
        $this->post($url)->assertSessionHas('connection_test', [
            'successful' => false,
            'message' => 'Telegram menolak token bot. Periksa TELEGRAM_BOT_TOKEN.',
        ]);
        Http::assertSentCount(1);
    }

    public function test_connection_tests_are_restricted_to_active_admins(): void
    {
        $routerUrl = route('admin.routers.test-connection', $this->router());
        $telegramUrl = route('admin.telegram-groups.test-connection');
        $this->mock(MikrotikProvisionerService::class)->shouldNotReceive('testConnection');
        Http::preventStrayRequests();

        $this->post($routerUrl)->assertRedirectToRoute('login');
        $this->post($telegramUrl)->assertRedirectToRoute('login');

        $this->actingAs(User::factory()->create());
        $this->post($routerUrl)->assertForbidden();
        $this->post($telegramUrl)->assertForbidden();

        $this->actingAs(User::factory()->admin()->create(['is_active' => false]));
        $this->post($telegramUrl)->assertRedirectToRoute('login');
    }

    private function router(): Router
    {
        return Router::create([
            'nama_router' => 'Router Uji',
            'ip_host' => '192.0.2.10',
            'api_port' => 8728,
            'username' => 'api-user',
            'password' => 'test-password',
        ]);
    }
}
