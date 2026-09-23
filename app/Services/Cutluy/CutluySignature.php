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
