<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Telegram\TelegramInitData;

class AuthenticateTelegramUser
{
    public function __construct(private TelegramInitData $initData) {}

    public function handle(string $initData): User
    {
        $telegramUser = $this->initData->user($initData);
        $telegramId = (string) $telegramUser['id'];

        $user = User::query()->where('telegram_id', $telegramId)->first();

        if ($user === null) {
            $user = new User;
            $user->telegram_id = $telegramId;
            $user->password = null;
        }

        $first = trim((string) ($telegramUser['first_name'] ?? ''));
        $last = trim((string) ($telegramUser['last_name'] ?? ''));
        $name = trim($first.' '.$last);

        if (! $user->exists) {
            $user->name = $name !== '' ? $name : 'Telegram customer';
        }

        $user->telegram_username = isset($telegramUser['username'])
            ? (string) $telegramUser['username']
            : $user->telegram_username;
        $user->save();

        return $user;
    }
}
