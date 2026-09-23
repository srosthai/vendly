<?php

namespace App\Actions\Telegram;

use App\Models\PlatformSetting;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LinkStoreTelegram
{
    public function start(Store $store): string
    {
        $token = Str::random(40);
        Cache::put($this->key($token), $store->id, now()->addMinutes(15));

        $username = PlatformSetting::current()->botUsername();

        return 'https://t.me/'.$username.'?start=link_'.$token;
    }

    /**
     * @return Store|null The linked store, or null for an unknown or used token.
     */
    public function complete(string $token, string $chatId): ?Store
    {
        $storeId = Cache::pull($this->key($token));

        if (! is_int($storeId) && ! is_string($storeId)) {
            return null;
        }

        $store = Store::query()->find($storeId);

        if ($store === null) {
            return null;
        }

        $store->telegram_chat_id = $chatId;
        $store->save();

        return $store;
    }

    private function key(string $token): string
    {
        return 'telegram-link:'.$token;
    }
}
