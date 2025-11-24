<?php
/**
 * Notifications Webhook (Slack / Teams / Generic)
 */

declare(strict_types=1);

namespace Helpers;

class Notifier
{
    public static function send(string $title, array $context = []): void
    {
        if (!ALERT_ENABLED || empty(ALERT_WEBHOOK_URL)) {
            Logger::debug('Notifier disabled', [
                'title' => $title,
                'context' => $context,
            ]);
            return;
        }

        $payload = [
            'text' => self::formatMessage($title, $context),
        ];

        $options = [
            CURLOPT_URL => ALERT_WEBHOOK_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST => ALERT_WEBHOOK_METHOD,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        curl_close($ch);
    }

    private static function formatMessage(string $title, array $context): string
    {
        $lines = ["*{$title}*"];

        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $lines[] = "- {$key}: {$value}";
            } elseif (is_array($value)) {
                $lines[] = "- {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return implode("\n", $lines);
    }
}

