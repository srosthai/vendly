<?php

namespace App\Services\Cutluy;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CutluySignature
{
    public function assertValid(string $header, string $rawBody): void
    {
        if (! preg_match('/^t=(\d+),v1=([a-fA-F0-9]+)$/', $header, $matches)) {
            throw new HttpException(401, 'Invalid CutLuy signature.');
        }

        $timestamp = (int) $matches[1];

        if (abs(now()->getTimestamp() - $timestamp) > 300) {
            throw new HttpException(401, 'CutLuy signature has expired.');
        }

        $secret = (string) config('services.cutluy.webhook_secret');
        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        if (! hash_equals($expected, strtolower($matches[2]))) {
            throw new HttpException(401, 'Invalid CutLuy signature.');
        }
    }
}
