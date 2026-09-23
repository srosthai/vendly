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
     * A single product ends with its own link. A cart ends with its total and
     * then the store link, as in the plan's message shape. A retry passes the
     * chats that have not received it yet.
     *
     * @param  list<'admin'|'vendor'>|null  $only
     */
    public function inquiry(Inquiry $inquiry, ?array $only = null): void
    {
        $inquiry->loadMissing(['store', 'items.product']);
        $store = $inquiry->store;
        $lines = $inquiry->items->map(function ($item) use ($store): string {
            $url = $item->product === null
                ? route('stores.show', $store)
                : route('stores.products.show', ['store' => $store, 'productSlug' => $item->product->slug]);

            return $item->quantity.' × '.$item->name.' — '.Money::format($item->price_cents)."\n".$url;
        })->implode("\n");

        $total = (int) $inquiry->items->sum(fn ($item): int => $item->price_cents * $item->quantity);
        $contact = $inquiry->contact !== null ? ' ('.$inquiry->contact.')' : '';
        $text = "New request {$inquiry->reference()} — {$store->name}\nFrom: {$inquiry->customer_name}{$contact}\n{$lines}\nTotal: ".Money::format($total);

        if ($inquiry->from_cart) {
            $text .= "\n".route('stores.show', $store);
        }

        if ($only === null || in_array('admin', $only, true)) {
            $this->dispatch(PlatformSetting::current()->adminChatId(), $text, $inquiry->id, 'admin');
        }

        if (($only === null || in_array('vendor', $only, true)) && filled($store->telegram_chat_id)) {
            $this->dispatch($store->telegram_chat_id, $text, $inquiry->id, 'vendor');
        }
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

    private function dispatch(string $chatId, string $text, ?int $inquiryId, string $destination): void
    {
        SendTelegramMessage::dispatch($chatId, $text, $inquiryId, $destination);
    }
}
