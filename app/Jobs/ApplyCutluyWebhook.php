<?php

namespace App\Jobs;

use App\Actions\Billing\ApplyCutluyEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplyCutluyWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public string $event, public array $payload) {}

    public function handle(ApplyCutluyEvent $action): void
    {
        $action->handle($this->event, $this->payload);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('CutLuy webhook job failed', [
            'event' => $this->event,
            'payment' => $this->payload['id'] ?? null,
            'exception' => $exception,
        ]);
    }
}
