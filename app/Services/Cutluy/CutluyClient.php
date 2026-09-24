<?php

namespace App\Services\Cutluy;

use App\Exceptions\CutluyRequestException;
use App\Models\PlatformSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class CutluyClient
{
    public const MaxRetryAfterSeconds = 60;

    /**
     * Sends one create request. It never waits or retries inside the web
     * request: a 429 is reported with its Retry-After so the caller can
     * queue the single delayed retry.
     *
     * @param  array<string, mixed>  $metadata
     * @return array{id: string, status: string, checkout_url: string, qr_string: string, expires_at: string|null}
     *
     * @throws CutluyRequestException
     */
    public function createPayment(float $amount, string $referenceId, array $metadata, string $idempotencyKey): array
    {
        try {
            $response = $this->request()->post('/v1/payments', [
                'amount' => $amount,
                'reference_id' => $referenceId,
                'metadata' => $metadata,
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (ConnectionException) {
            throw new CutluyRequestException(0, 'connection_failed');
        }

        return $this->payment($response);
    }

    /**
     * One status read. Callers never loop on it; a 429 is reported like any
     * other failure.
     *
     * @return array<string, mixed>
     *
     * @throws CutluyRequestException
     */
    public function findPayment(string $id): array
    {
        try {
            $response = $this->request()->get('/v1/payments/'.rawurlencode($id));
        } catch (ConnectionException) {
            throw new CutluyRequestException(0, 'connection_failed');
        }

        $this->ensureSuccessful($response);

        /** @var array<string, mixed> $json */
        $json = $response->json();

        return $json;
    }

    /**
     * @throws CutluyRequestException
     */
    private function ensureSuccessful(Response $response): void
    {
        if (! $response->successful()) {
            throw new CutluyRequestException(
                $response->status(),
                (string) $response->json('error', $response->serverError() ? 'server_error' : 'request_failed'),
                (string) $response->json('message', ''),
                $response->status() === 429 ? $this->retryAfter($response) : 0,
            );
        }
    }

    /**
     * @return array{id: string, status: string, checkout_url: string, qr_string: string, expires_at: string|null}
     */
    private function payment(Response $response): array
    {
        $this->ensureSuccessful($response);

        /** @var array{id?: string, status?: string, checkout_url?: string, qr_string?: string, expires_at?: mixed} $json */
        $json = $response->json();

        return [
            'id' => (string) ($json['id'] ?? ''),
            'status' => (string) ($json['status'] ?? 'pending'),
            'checkout_url' => (string) ($json['checkout_url'] ?? ''),
            'qr_string' => (string) ($json['qr_string'] ?? ''),
            'expires_at' => is_string($json['expires_at'] ?? null) ? $json['expires_at'] : null,
        ];
    }

    /**
     * Retry-After is either whole seconds or an HTTP date. The wait is kept
     * between 1 and {@see self::MaxRetryAfterSeconds} seconds.
     */
    private function retryAfter(Response $response): int
    {
        $header = trim($response->header('Retry-After'));
        $seconds = 1;

        if (ctype_digit($header)) {
            $seconds = (int) $header;
        } elseif ($header !== '') {
            try {
                $seconds = (int) ceil(now()->diffInSeconds(CarbonImmutable::parse($header), false));
            } catch (Throwable) {
                $seconds = 1;
            }
        }

        return max(1, min(self::MaxRetryAfterSeconds, $seconds));
    }

    private function request(): PendingRequest
    {
        $settings = PlatformSetting::current();

        return Http::baseUrl($settings->cutluyBaseUrl())
            ->withToken($settings->cutluyApiKey())
            ->acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(10);
    }
}
