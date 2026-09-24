<?php

namespace App\Jobs;

use App\Models\Inquiry;
use App\Services\Telegram\TelegramClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        /** @var array<string, mixed> Extra Bot API fields, such as parse_mode or reply_markup. */
        public array $options = [],
    ) {}

    /**
     * A 400 or 403 (chat not found, bot blocked) will not fix itself, so it
     * fails at once with Telegram's reason. A 429 waits as long as Telegram
     * asks. Anything else is retried with the backoff.
     */
    public function handle(TelegramClient $telegram): void
    {
        try {
            $sent = $telegram->sendMessage($this->chatId, $this->text, $this->options);
        } catch (RequestException $exception) {
            $status = $exception->response->status();
            $reason = (string) $exception->response->json('description', 'Telegram refused the message.');

            if ($status === 429) {
                $this->release(max(1, (int) $exception->response->json('parameters.retry_after', 30)));

                return;
            }

            if (in_array($status, [400, 403], true)) {
                $this->recordError($reason);
                $this->fail($exception);

                return;
            }

            throw $exception;
        }

        if ($sent) {
            $this->recordDelivery();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->recordError(match (true) {
            $exception instanceof RequestException => (string) $exception->response->json('description', 'Telegram refused the message.'),
            default => 'Telegram could not be reached.',
        }, onlyIfEmpty: true);

        Log::error('Telegram message failed', [
            'chat_id' => $this->chatId,
            'inquiry_id' => $this->inquiryId,
            'error' => $exception === null ? null : $exception::class,
            'status' => $exception instanceof RequestException ? $exception->response->status() : null,
        ]);
    }

    private function recordDelivery(): void
    {
        $inquiry = $this->inquiry();

        if ($inquiry === null) {
            return;
        }

        $inquiry->forceFill([
            $this->destination.'_notified_at' => now(),
            $this->destination.'_error' => null,
        ])->save();
    }

    private function recordError(string $reason, bool $onlyIfEmpty = false): void
    {
        $inquiry = $this->inquiry();

        if ($inquiry === null || ($onlyIfEmpty && $inquiry->getAttribute($this->destination.'_error') !== null)) {
            return;
        }

        $inquiry->forceFill([$this->destination.'_error' => Str::limit($reason, 250)])->save();
    }

    private function inquiry(): ?Inquiry
    {
        if ($this->inquiryId === null || ! in_array($this->destination, ['admin', 'vendor'], true)) {
            return null;
        }

        return Inquiry::query()->find($this->inquiryId);
    }
}
