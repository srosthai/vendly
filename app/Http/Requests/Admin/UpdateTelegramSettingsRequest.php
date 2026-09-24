<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The Vendly bot from Admin > Telegram. The token is write-only: an empty
 * field keeps the saved one. The bot username comes from Telegram.
 */
class UpdateTelegramSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bot_token' => ['nullable', 'string', 'max:100', 'regex:/^\d+:[A-Za-z0-9_-]{30,}$/'],
            'admin_chat_id' => ['nullable', 'string', 'max:32', 'regex:/^-?\d+$/'],
            'mini_app_short_name' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]{3,64}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bot_token.regex' => 'A bot token looks like 123456789:ABC... Copy it again from @BotFather.',
            'admin_chat_id.regex' => 'A chat id is a number, such as 5283073511 or -1001234567890 for a group.',
            'mini_app_short_name.regex' => 'Use the short name you gave @BotFather: letters, numbers, and underscores.',
        ];
    }
}
