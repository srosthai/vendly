<?php

namespace App\Actions\Telegram;

use App\Models\PlatformSetting;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LinkStoreTelegram
{
    /**
     * One-time links that connect a chat to the store, valid for 15 minutes:
     * `chat` opens the bot in the vendor's own chat, `group` lets them pick
     * a group, which adds the bot there.
     *
     * @return array{chat: string, group: string}
     */
    public function start(Store $store): array
    {
        $token = Str::random(40);
        Cache::put($this->key($token), $store->id, now()->addMinutes(15));

        $bot = 'https://t.me/'.PlatformSetting::current()->botUsername();

        return [
            'chat' => $bot.'?start=link_'.$token,
            'group' => $bot.'?startgroup=link_'.$token,
        ];
    }

    /**
     * Stop sending the store's requests to its chat.
     */
    public function disconnect(Store $store): void
    {
        $store->telegram_chat_id = null;
        $store->telegram_chat_name = null;
        $store->telegram_connected_at = null;
        $store->save();
    }

    /**
     * Telegram gives a group a new id when it becomes a supergroup; keep
     * every store that used the old id connected.
     */
    public function migrate(string $fromChatId, string $toChatId): void
    {
        Store::query()->where('telegram_chat_id', $fromChatId)->update(['telegram_chat_id' => $toChatId]);
    }

    /**
     * @return Store|null The linked store, or null for an unknown or used token.
     */
    public function complete(string $token, string $chatId, ?string $chatName = null): ?Store
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
        $store->telegram_chat_name = $chatName !== null && $chatName !== '' ? mb_substr($chatName, 0, 255) : null;
        $store->telegram_connected_at = now();
        $store->save();

        return $store;
    }

    private function key(string $token): string
    {
        return 'telegram-link:'.$token;
    }
}
