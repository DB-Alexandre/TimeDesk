<?php
/**
 * Envoi d'emails basique
 */

declare(strict_types=1);

namespace Helpers;

class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        if (!MAIL_ENABLED || empty(MAIL_FROM_ADDRESS)) {
            Logger::info('Mail disabled, dumping message', [
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
            ]);
            return false;
        }

        $headers = [];
        $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>';
        $headers[] = 'Reply-To: ' . MAIL_FROM_ADDRESS;
        $headers[] = 'Content-Type: text/plain; charset=utf-8';

        return @mail($to, $subject, $body, implode("\r\n", $headers));
    }
}

