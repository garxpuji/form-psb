<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PsbOrder;
use App\Models\TelegramGroup;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class TelegramService
{
    public const FAILED_POE_IP_LABEL = '⚠️ GAGAL AUTO-CONFIG (CEK NOC)';

    /**
     * @return array{successful: bool, message: string}
     */
    public function testConnection(): array
    {
        $botToken = trim((string) config('services.telegram.bot_token', ''));

        if ($botToken === '') {
            return ['successful' => false, 'message' => 'TELEGRAM_BOT_TOKEN belum diatur.'];
        }

        try {
            $response = Http::timeout((int) config('services.telegram.timeout', 15))
                ->withoutRedirecting()
                ->get("https://api.telegram.org/bot{$botToken}/getMe");
        } catch (ConnectionException) {
            return ['successful' => false, 'message' => 'API Telegram tidak dapat dijangkau. Periksa koneksi internet server.'];
        } catch (InvalidArgumentException) {
            return ['successful' => false, 'message' => 'Format TELEGRAM_BOT_TOKEN tidak valid.'];
        }

        if (in_array($response->status(), [401, 404], true)) {
            return ['successful' => false, 'message' => 'Telegram menolak token bot. Periksa TELEGRAM_BOT_TOKEN.'];
        }

        $username = $response->json('result.username');

        if (! $response->successful() || $response->json('ok') !== true || ! is_string($username) || $username === '') {
            return ['successful' => false, 'message' => 'API Telegram gagal memberikan identitas bot. Coba lagi.'];
        }

        return ['successful' => true, 'message' => "API Telegram terhubung sebagai @{$username}."];
    }

    public function resolveTargetGroup(User $user): TelegramGroup
    {
        $user->loadMissing('telegramGroup');

        if ($user->telegram_group_id !== null && $user->telegramGroup !== null && ! $user->telegramGroup->is_all_team) {
            return $user->telegramGroup;
        }

        $allTeamGroup = TelegramGroup::where('is_all_team', true)->first();

        if ($allTeamGroup === null) {
            throw new RuntimeException('Grup Telegram ALL TEAM PSB (is_all_team = true) belum dikonfigurasi di database.');
        }

        return $allTeamGroup;
    }

    public function isNonTeamUser(User $user): bool
    {
        $user->loadMissing('telegramGroup');

        return $user->telegram_group_id === null
            || $user->telegramGroup === null
            || $user->telegramGroup->is_all_team;
    }

    public function formatOrderMessage(PsbOrder $order, ?User $user = null): string
    {
        $order->loadMissing(['odp', 'paket', 'sales', 'user.telegramGroup']);
        $targetUser = $user ?? $order->user;

        $poeOrIp = ($order->status_sync === 'success' && ! empty($order->hasil_poe_ip))
            ? $order->hasil_poe_ip
            : self::FAILED_POE_IP_LABEL;

        $lines = [
            "NAMA         : {$order->nama_pelanggan}",
            "Alamat.      : {$order->alamat}",
            "SHARELOC     : {$order->shareloc}",
            "NO.TLP       : {$order->no_tlp}",
            "ODP          : {$order->odp?->nama_odp}",
            "PORT         : {$order->port}",
            "PAKET        : {$order->paket?->nama_paket}",
            "MAC          : {$order->mac_address}",
            "POE/IP       : {$poeOrIp}",
            "BRAND ROUTER : {$order->brand_router}",
            "JARAK KABEL  : {$order->jarak_kabel}",
            "SIGNAL ODP   : {$order->signal_odp}",
            "SIGNAL ONT   : {$order->signal_ont}",
            'SALES        : '.($order->sales?->kode_sales_ip ?? 'TANPA SALES'),
            "NAMA TEKNISI : {$order->nama_teknisi}",
            '[AUTO-NOC]',
        ];

        $body = implode("\n", $lines);

        if ($targetUser !== null && $this->isNonTeamUser($targetUser)) {
            return "[NON-TIM PSB]\n".$body;
        }

        return $body;
    }

    public function sendPsbReport(PsbOrder $order, ?User $user = null): bool
    {
        $order->loadMissing(['user.telegramGroup', 'odp', 'paket', 'sales']);
        $targetUser = $user ?? $order->user;

        if ($targetUser === null) {
            Log::warning('TelegramService: Tidak dapat mengirim laporan karena user order tidak ditemukan.', [
                'order_id' => $order->id,
            ]);

            return false;
        }

        try {
            $group = $this->resolveTargetGroup($targetUser);
            $message = $this->formatOrderMessage($order, $targetUser);

            $sentText = $this->sendMessage(
                chatId: $group->chat_id,
                text: $message,
                threadId: $group->thread_id,
            );

            $this->sendOrderPhotosAlbum(
                order: $order,
                chatId: $group->chat_id,
                threadId: $group->thread_id,
            );

            return $sentText;
        } catch (\Throwable $e) {
            Log::error('TelegramService: Gagal mengirim laporan PSB ke Telegram.', [
                'order_id' => $order->id,
                'error' => $this->redactBotToken($e->getMessage()),
            ]);

            return false;
        }
    }

    public function sendOrderPhotosAlbum(PsbOrder $order, string|int $chatId, ?int $threadId = null): bool
    {
        $botToken = trim((string) config('services.telegram.bot_token', ''));
        if ($botToken === '') {
            return false;
        }

        $localDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');
        $mediaItems = [];
        $attachments = [];
        $index = 0;

        foreach (PsbOrder::PHOTO_FIELDS as $field => $label) {
            $relPath = (string) ($order->{$field} ?? '');
            if ($relPath === '') {
                continue;
            }

            $contents = null;
            if ($localDisk->exists($relPath)) {
                $contents = $localDisk->get($relPath);
            } elseif ($publicDisk->exists($relPath)) {
                $contents = $publicDisk->get($relPath);
            }

            if ($contents === null) {
                continue;
            }

            $attachKey = "photo_{$index}";
            $attachments[$attachKey] = [
                'contents' => $contents,
                'filename' => basename($relPath),
            ];

            $mediaItems[] = [
                'type' => 'photo',
                'media' => "attach://{$attachKey}",
                'caption' => "{$label} - {$order->nama_pelanggan} ({$order->odp?->nama_odp} P:{$order->port})",
            ];
            $index++;
        }

        if ($mediaItems === []) {
            return false;
        }

        $timeout = (int) config('services.telegram.timeout', 15);
        $url = "https://api.telegram.org/bot{$botToken}/sendMediaGroup";

        $request = Http::timeout($timeout)->asMultipart();
        foreach ($attachments as $key => $fileData) {
            $request = $request->attach($key, $fileData['contents'], $fileData['filename']);
        }

        $payload = [
            'chat_id' => (string) $chatId,
            'media' => json_encode($mediaItems),
        ];

        if ($threadId !== null && $threadId > 0) {
            $payload['message_thread_id'] = (string) $threadId;
        }

        $response = $request->post($url, $payload);

        if (! $response->successful()) {
            Log::warning('TelegramService: Gagal mengirim album foto PSB ke Telegram.', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $this->redactBotToken($response->body()),
            ]);

            return false;
        }

        return true;
    }

    public function sendMessage(
        string|int $chatId,
        string $text,
        ?int $threadId = null,
        ?int $replyToMessageId = null,
        ?string $parseMode = null
    ): bool {
        $botToken = trim((string) config('services.telegram.bot_token', ''));

        if ($botToken === '') {
            Log::warning('TelegramService: TELEGRAM_BOT_TOKEN belum diatur di environment. Pesan diabaikan.', [
                'chat_id' => $chatId,
                'thread_id' => $threadId,
            ]);

            return false;
        }

        $timeout = (int) config('services.telegram.timeout', 15);
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $payload = [
            'chat_id' => (string) $chatId,
            'text' => $text,
        ];

        if ($threadId !== null && $threadId > 0) {
            $payload['message_thread_id'] = $threadId;
        }

        if ($replyToMessageId !== null && $replyToMessageId > 0) {
            $payload['reply_to_message_id'] = $replyToMessageId;
        }

        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        $response = Http::timeout($timeout)->post($url, $payload);

        if (! $response->successful()) {
            Log::error('TelegramService: API Telegram mengembalikan status error.', [
                'status' => $response->status(),
                'body' => $this->redactBotToken($response->body()),
                'chat_id' => $chatId,
            ]);

            return false;
        }

        return true;
    }

    private function redactBotToken(string $message): string
    {
        $botToken = trim((string) config('services.telegram.bot_token', ''));

        if ($botToken === '') {
            return $message;
        }

        return str_replace($botToken, '[REDACTED-BOT-TOKEN]', $message);
    }
}
