<?php

namespace App\Services\Telegram;

use Illuminate\Validation\ValidationException;

class TelegramInitData
{
    /**
     * @return array{id: int|string, first_name?: string, last_name?: string, username?: string}
     */
    public function user(string $initData): array
    {
        parse_str($initData, $data);

        $hash = $data['hash'] ?? null;
        unset($data['hash']);

        if (! is_string($hash) || $hash === '') {
            throw ValidationException::withMessages([
                'init_data' => 'The Telegram sign-in data is invalid.',
            ]);
        }

        $token = config('services.telegram.bot_token');

        if (! is_string($token) || $token === '') {
            throw ValidationException::withMessages([
                'init_data' => 'Telegram sign-in is not configured.',
            ]);
        }

        ksort($data);

        $lines = [];

        foreach ($data as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $lines[] = $key.'='.$value;
        }

        $check = implode("\n", $lines);

        $secret = hash_hmac('sha256', $token, 'WebAppData', true);
        $calculated = hash_hmac('sha256', $check, $secret);

        if (! hash_equals($calculated, $hash)) {
            throw ValidationException::withMessages([
                'init_data' => 'The Telegram sign-in data is invalid.',
            ]);
        }

        $authDate = (int) ($data['auth_date'] ?? 0);

        if ($authDate < now()->subDay()->getTimestamp()) {
            throw ValidationException::withMessages([
                'init_data' => 'The Telegram sign-in data has expired.',
            ]);
        }

        $rawUser = $data['user'] ?? '';

        if (! is_string($rawUser)) {
            throw ValidationException::withMessages([
                'init_data' => 'The Telegram sign-in data is invalid.',
            ]);
        }

        $user = json_decode($rawUser, true);

        if (! is_array($user) || ! isset($user['id'])) {
            throw ValidationException::withMessages([
                'init_data' => 'The Telegram sign-in data is invalid.',
            ]);
        }

        return $user;
    }
}
