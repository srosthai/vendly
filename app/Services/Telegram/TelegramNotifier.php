<?php

namespace App\Services\Telegram;

use App\Jobs\SendTelegramMessage;
use App\Models\Inquiry;
use App\Models\PlatformSetting;
use App\Models\Store;
use App\Support\Money;

class TelegramNotifier
{
    public function newStore(Store $store): void
    {
        $this->dispatch(
            PlatformSetting::current()->adminChatId(),
            "New store — {$store->name}\n".route('stores.show', $store),
            null,
            'admin',
        );
    }

    public function planPaid(Store $store): void
    {
        $store->loadMissing('subscription.plan');
        $plan = $store->subscription?->plan->name ?? 'plan';
        $text = "Plan paid — {$store->name}\n{$plan}";

        $this->dispatch(PlatformSetting::current()->adminChatId(), $text, null, 'admin');
        $this->dispatch((string) $store->telegram_chat_id, $text, null, 'vendor');
    }

    public function planExpired(Store $store): void
    {
        $text = "Plan expired — {$store->name}\nNew products cannot be published until the plan is renewed.";

        $this->dispatch(PlatformSetting::current()->adminChatId(), $text, null, 'admin');
        $this->dispatch((string) $store->telegram_chat_id, $text, null, 'vendor');
    }

    public function paymentsUnavailable(string $error): void
    {
        $this->dispatch(
            PlatformSetting::current()->adminChatId(),
            'CutLuy payments are unavailable ('.$error.').',
            null,
            'admin',
        );
    }

    /**
     * A request as a tidy message: the number and store, who sent it (their
     *
     * @username taps through to a chat), each product linked to its page,
     * and the total. Buttons open it in Vendly and message the customer.
     * Every value from a person is escaped for Telegram's HTML. A retry
     * passes the chats that have not received it yet.
     *
     * @param  list<'admin'|'vendor'>|null  $only
     */
    public function inquiry(Inquiry $inquiry, ?array $only = null): void
    {
        $inquiry->loadMissing(['store', 'items.product', 'customer']);
        $store = $inquiry->store;
        $e = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = $inquiry->items->map(function ($item) use ($store, $e): string {
            $name = $item->product === null
                ? $e($item->name)
                : '<a href="'.$e(route('stores.products.show', ['store' => $store, 'productSlug' => $item->product->slug])).'">'.$e($item->name).'</a>';

            return $item->quantity.' × '.$name.'  <b>'.Money::format($item->price_cents * $item->quantity).'</b>';
        })->implode("\n");

        $total = (int) $inquiry->items->sum(fn ($item): int => $item->price_cents * $item->quantity);
        $count = (int) $inquiry->items->sum('quantity');
        $username = self::customerUsername($inquiry);
        $who = $e($inquiry->customer_name);

        if ($username !== null) {
            $who .= ' · <a href="https://t.me/'.$e($username).'">@'.$e($username).'</a>';
        }

        if ($inquiry->contact !== null && $inquiry->contact !== '@'.$username) {
            $who .= ' · '.$e($inquiry->contact);
        }

        $text = implode("\n", [
            '<b>New request '.$e($inquiry->reference()).'</b> · '.$e($store->name),
            '',
            '<b>Customer</b>',
            $who,
            '',
            '<b>'.($inquiry->from_cart ? 'Cart' : 'Buy now').'</b> · '.$count.' '.($count === 1 ? 'item' : 'items'),
            $lines,
            '',
            '<b>Total '.Money::format($total).'</b>',
        ]);

        // A cart ends with the store link, so the whole store is one tap away.
        if ($inquiry->from_cart) {
            $text .= "\n".$e(route('stores.show', $store));
        }

        $contactButton = $username === null ? [] : [['text' => 'Message customer', 'url' => 'https://t.me/'.$username]];

        if ($only === null || in_array('admin', $only, true)) {
            $this->dispatch(PlatformSetting::current()->adminChatId(), $text, $inquiry->id, 'admin', $this->markup(
                $this->openButton(route('admin.requests', ['search' => $inquiry->customer_name])),
                $contactButton,
            ));
        }

        if (($only === null || in_array('vendor', $only, true)) && filled($store->telegram_chat_id)) {
            $this->dispatch($store->telegram_chat_id, $text, $inquiry->id, 'vendor', $this->markup(
                $this->openButton(route('vendor.requests', ['open' => $inquiry->id])),
                $contactButton,
            ));
        }
    }

    /**
     * The customer's Telegram username, from their account or from a contact
     * that is one, so the vendor can message them.
     */
    public static function customerUsername(Inquiry $inquiry): ?string
    {
        $candidates = [$inquiry->customer?->telegram_username, $inquiry->contact];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && preg_match('/^@?([A-Za-z0-9_]{5,32})$/', trim($candidate), $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Telegram only opens https links from buttons, so a local http site
     * gets no Open button.
     *
     * @return list<array{text: string, url: string}>
     */
    private function openButton(string $url): array
    {
        return str_starts_with($url, 'https://') ? [['text' => 'Open in Vendly', 'url' => $url]] : [];
    }

    /**
     * @param  list<array{text: string, url: string}>  ...$buttons
     * @return array<string, mixed>
     */
    private function markup(array ...$buttons): array
    {
        $row = array_merge(...$buttons);
        $options = ['parse_mode' => 'HTML', 'link_preview_options' => ['is_disabled' => true]];

        if ($row !== []) {
            $options['reply_markup'] = ['inline_keyboard' => [$row]];
        }

        return $options;
    }

    public function storeConnected(Store $store): void
    {
        $this->dispatch(
            (string) $store->telegram_chat_id,
            "Connected to {$store->name}. Buy requests from your store now arrive here.",
            null,
            'vendor',
        );
    }

    public function storeDisconnected(Store $store): void
    {
        $this->dispatch(
            (string) $store->telegram_chat_id,
            "This chat no longer receives buy requests from {$store->name}.",
            null,
            'vendor',
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function dispatch(string $chatId, string $text, ?int $inquiryId, string $destination, array $options = []): void
    {
        SendTelegramMessage::dispatch($chatId, $text, $inquiryId, $destination, $options);
    }
}
