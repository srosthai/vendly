<?php

namespace App\Jobs;

use App\Models\Inquiry;
use App\Services\Telegram\TelegramClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30];

    public int $timeout = 15;

    public function __construct(
        public string $chatId,
        public string $text,
        public ?int $inquiryId = null,
        public ?string $destination = null,
    ) {}

    public function handle(TelegramClient $telegram): void
    {
        if (! $telegram->sendMessage($this->chatId, $this->text) || $this->inquiryId === null) {
            return;
        }

        $inquiry = Inquiry::query()->find($this->inquiryId);

        if ($inquiry === null) {
            return;
        }

        if ($this->destination === 'admin') {
            $inquiry->admin_notified_at = now();
        }

        if ($this->destination === 'vendor') {
            $inquiry->vendor_notified_at = now();
        }

        $inquiry->save();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Telegram message failed', [
            'chat_id' => $this->chatId,
            'inquiry_id' => $this->inquiryId,
            'exception' => $exception,
        ]);
    }
}
