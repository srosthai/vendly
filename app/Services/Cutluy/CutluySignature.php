<?php

namespace App\Services\Cutluy;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CutluySignature
{
    public function assertValid(string $header, string $rawBody): void
    {
        $secret = config('services.cutluy.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            Log::error('CutLuy webhook refused: CUTLUY_WEBHOOK_SECRET is not set.');

            throw new HttpException(401, 'CutLuy webhooks are not configured.');
        }

        if (! preg_match('/^t=(\d+),v1=([a-fA-F0-9]+)$/', $header, $matches)) {
            throw new HttpException(401, 'Invalid CutLuy signature.');
        }

        $timestamp = (int) $matches[1];

        if (abs(now()->getTimestamp() - $timestamp) > 300) {
            throw new HttpException(401, 'CutLuy signature has expired.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        if (! hash_equals($expected, strtolower($matches[2]))) {
            throw new HttpException(401, 'Invalid CutLuy signature.');
        }
    }
}
