<?php

namespace App\Services\Cutluy;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CutluySignature
{
    public function assertValid(string $header, string $rawBody): void
    {
        $secret = PlatformSetting::current()->cutluyWebhookSecret();

        if ($secret === '') {
            Log::error('CutLuy webhook refused: no webhook secret is set in Site settings or CUTLUY_WEBHOOK_SECRET.');

            throw new HttpException(401, 'CutLuy webhooks are not configured.');
        }

        if (! preg_match('/^t=(\d+),v1=([a-fA-F0-9]+)$/', $header, $matches)) {
            $this->refuse('Invalid CutLuy signature.', 'the signature header is missing or malformed');
        }

        $timestamp = (int) $matches[1];

        if (abs(now()->getTimestamp() - $timestamp) > 300) {
            $this->refuse('CutLuy signature has expired.', 'the signature is more than five minutes old');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        if (! hash_equals($expected, strtolower($matches[2]))) {
            $this->refuse('Invalid CutLuy signature.', 'the signature does not match the webhook secret');
        }
    }

    /**
     * A refused delivery is logged with the reason, never the secret, so a
     * webhook that never lands can be traced.
     */
    private function refuse(string $message, string $reason): never
    {
        Log::warning('CutLuy webhook refused: '.$reason.'. Check that the signing secret in CutLuy Dashboard > Webhooks matches Site settings.');

        throw new HttpException(401, $message);
    }
}
