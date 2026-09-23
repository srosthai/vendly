<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $admin_chat_id
 * @property string|null $bot_username
 * @property string|null $mini_app_short_name
 */
#[Fillable(['admin_chat_id', 'bot_username', 'mini_app_short_name'])]
class PlatformSetting extends Model
{
    public static function current(): self
    {
        $setting = static::query()->first();

        if ($setting instanceof self) {
            return $setting;
        }

        return static::query()->create([]);
    }

    public function adminChatId(): string
    {
        if (is_string($this->admin_chat_id) && $this->admin_chat_id !== '') {
            return $this->admin_chat_id;
        }

        return (string) config('services.telegram.admin_chat_id');
    }

    public function botUsername(): string
    {
        if (is_string($this->bot_username) && $this->bot_username !== '') {
            return $this->bot_username;
        }

        return (string) config('services.telegram.bot_username');
    }

    /**
     * The mini app link for a store, or null until the bot and mini app are set.
     */
    public function miniAppLink(string $slug): ?string
    {
        $username = $this->botUsername();
        $short = is_string($this->mini_app_short_name) && $this->mini_app_short_name !== ''
            ? $this->mini_app_short_name
            : (string) config('services.telegram.mini_app_short_name');

        if ($username === '' || $short === '') {
            return null;
        }

        return 'https://t.me/'.$username.'/'.$short.'?startapp='.$slug;
    }
}
