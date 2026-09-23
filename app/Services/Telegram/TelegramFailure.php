<?php

namespace App\Services\Telegram;

/**
 * Turns Telegram's error description into what a seller should do next.
 */
class TelegramFailure
{
    public static function explain(string $description): string
    {
        $lower = strtolower($description);

        return match (true) {
            str_contains($lower, 'bot was blocked') => 'You blocked the Vendly bot in Telegram. Open the bot, tap Restart, then send the test again.',
            str_contains($lower, 'chat not found') => 'Telegram cannot find this chat any more. Connect your chat again.',
            str_contains($lower, 'kicked') || str_contains($lower, 'not a member') => 'The Vendly bot was removed from this chat. Add it back, or connect a different chat.',
            str_contains($lower, 'deactivated') => 'This Telegram account was deleted. Connect a different chat.',
            str_contains($lower, 'too many requests') => 'Telegram asked us to slow down. Wait a minute and try again.',
            default => 'Telegram refused the message: '.$description,
        };
    }
}
