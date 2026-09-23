<?php

namespace App\Services\Cutluy;

use App\Exceptions\CutluyRequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

class CutluyClient
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array{id: string, status: string, checkout_url: string, qr_string: string}
     */
    public function createPayment(float $amount, string $referenceId, array $metadata, string $idempotencyKey): array
    {
        $payload = [
            'amount' => $amount,
            'reference_id' => $referenceId,
            'metadata' => $metadata,
            'idempotency_key' => $idempotencyKey,
        ];

        $response = $this->request()->post('/v1/payments', $payload);

        if ($response->status() === 429) {
            Sleep::sleep($this->retryAfter($response));
            $response = $this->request()->post('/v1/payments', $payload);
        }

        return $this->payment($response);
    }

    /**
     * @return array{id: string, status: string, checkout_url: string, qr_string: string}
     */
    private function payment(Response $response): array
    {
        if (in_array($response->status(), [401, 402, 403, 429], true)) {
            throw new CutluyRequestException(
                $response->status(),
                (string) $response->json('error', 'request_failed'),
                (string) $response->json('message', ''),
            );
        }

        if (! $response->successful()) {
            $response->throw();
        }

        /** @var array{id?: string, status?: string, checkout_url?: string, qr_string?: string} $json */
        $json = $response->json();

        return [
            'id' => (string) ($json['id'] ?? ''),
            'status' => (string) ($json['status'] ?? 'pending'),
            'checkout_url' => (string) ($json['checkout_url'] ?? ''),
            'qr_string' => (string) ($json['qr_string'] ?? ''),
        ];
    }

    private function retryAfter(Response $response): int
    {
        $seconds = (int) $response->header('Retry-After');

        return $seconds > 0 ? $seconds : 1;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.cutluy.base_url'), '/'))
            ->withToken((string) config('services.cutluy.key'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(10);
    }
}
