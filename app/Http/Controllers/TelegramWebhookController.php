<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming Telegram Bot webhook updates.
     * Replies with the group's chat_id and message_thread_id whenever `/cekid` is sent.
     */
    public function handle(Request $request, TelegramService $telegramService): JsonResponse
    {
        $message = $request->input('message') ?? $request->input('edited_message');

        if (! is_array($message)) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $text = trim((string) ($message['text'] ?? ''));

        if (preg_match('/^\/cekid(?:@\S+)?\b/i', $text) !== 1) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $chat = $message['chat'] ?? [];
        $chatId = $chat['id'] ?? null;

        if ($chatId === null) {
            return response()->json(['ok' => false, 'error' => 'Missing chat.id'], 400);
        }

        $chatTitle = (string) ($chat['title'] ?? $chat['username'] ?? 'Private Chat');
        $threadId = isset($message['message_thread_id']) ? (int) $message['message_thread_id'] : null;
        $messageId = isset($message['message_id']) ? (int) $message['message_id'] : null;

        $threadDisplay = $threadId !== null ? (string) $threadId : 'Tidak ada (General / Non-Topic)';

        $replyText = implode("\n", [
            'ℹ️ INFO ID GRUP TELEGRAM [AUTO-NOC]',
            "Nama Grup : {$chatTitle}",
            "chat_id : {$chatId}",
            "message_thread_id : {$threadDisplay}",
        ]);

        $telegramService->sendMessage(
            chatId: $chatId,
            text: $replyText,
            threadId: $threadId,
            replyToMessageId: $messageId,
        );

        return response()->json([
            'ok' => true,
            'chat_id' => (string) $chatId,
            'message_thread_id' => $threadId,
            'reply_text' => $replyText,
        ]);
    }
}
