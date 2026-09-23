<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ApplyCutluyWebhook;
use App\Services\Cutluy\CutluySignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonException;

class CutluyWebhookController extends Controller
{
    public function __invoke(Request $request, CutluySignature $signature): Response
    {
        $raw = $request->getContent();
        $signature->assertValid((string) $request->header('X-CutLuy-Signature', ''), $raw);

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            abort(400, 'Invalid JSON.');
        }

        if (! is_array($payload)) {
            abort(400, 'Invalid JSON.');
        }

        ApplyCutluyWebhook::dispatch(
            (string) $request->header('X-CutLuy-Event', ''),
            $payload,
        );

        return response()->noContent();
    }
}
