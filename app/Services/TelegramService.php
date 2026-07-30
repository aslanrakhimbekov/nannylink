<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TelegramService
{
    protected ?string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
    }

    /**
     * Send OTP code to a Telegram chat ID or recipient username.
     */
    public function sendOtpCode(string $chatId, string $otp): bool
    {
        if (!$this->botToken) {
            Log::info("Telegram Bot Token not configured. OTP {$otp} logged for chat {$chatId}");
            return false;
        }

        $message = "🔐 <b>NannyLink</b>\n\nВаш код подтверждения для входа:\n<code>{$otp}</code>\n\nНикому не сообщайте этот код.";

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info("Telegram OTP successfully sent to chat {$chatId}: {$otp}");
                return true;
            }

            Log::error("Failed to send Telegram OTP to {$chatId}: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Telegram API Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch latest chat ID from bot getUpdates if no explicit telegram_id stored yet.
     */
    public function getLatestChatId(): ?string
    {
        if (!$this->botToken) {
            return null;
        }

        try {
            $response = Http::get("https://api.telegram.org/bot{$this->botToken}/getUpdates");
            if ($response->successful()) {
                $result = $response->json('result') ?? [];
                if (!empty($result)) {
                    $lastUpdate = end($result);
                    $chatId = $lastUpdate['message']['chat']['id'] ?? $lastUpdate['channel_post']['chat']['id'] ?? null;
                    return $chatId ? (string) $chatId : null;
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch Telegram getUpdates: " . $e->getMessage());
        }

        return null;
    }
}
