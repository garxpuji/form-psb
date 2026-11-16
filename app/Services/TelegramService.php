<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PsbOrder;
use App\Models\TelegramGroup;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramService
{
    public const FAILED_POE_IP_LABEL = '⚠️ GAGAL AUTO-CONFIG (CEK NOC)';

    /**
     * Resolve the target Telegram group for the given user according to PSB routing rules:
     * - If $user->telegram_group_id is set and points to a group with is_all_team = false,
     *   route ONLY to that specific team group.
     * - Otherwise, route ONLY to the ALL TEAM PSB group (is_all_team = true).
     */
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

    /**
     * Determine whether the message should be prefixed with "[NON-TIM PSB]\n".
     */
    public function isNonTeamUser(User $user): bool
    {
        $user->loadMissing('telegramGroup');

        return $user->telegram_group_id === null
            || $user->telegramGroup === null
            || $user->telegramGroup->is_all_team;
    }

    /**
     * Build the exact formatted Telegram PSB installation report message.
     */
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
            "SALES        : {$order->sales?->kode_sales_ip}",
            "NAMA TEKNISI : {$order->nama_teknisi}",
            '[AUTO-NOC]',
        ];

        $body = implode("\n", $lines);

        if ($targetUser !== null && $this->isNonTeamUser($targetUser)) {
            return "[NON-TIM PSB]\n" . $body;
        }

        return $body;
    }

    /**
     * Send the PSB installation report to the appropriate Telegram group.
     */
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

            return $this->sendMessage(
                chatId: $group->chat_id,
                text: $message,
                threadId: $group->thread_id,
            );
        } catch (\Throwable $e) {
            Log::error('TelegramService: Gagal mengirim laporan PSB ke Telegram.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a raw message to a Telegram chat ID (with optional topic thread ID and reply message ID).
     */
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
                'body' => $response->body(),
                'chat_id' => $chatId,
            ]);

            return false;
        }

        return true;
    }
}
