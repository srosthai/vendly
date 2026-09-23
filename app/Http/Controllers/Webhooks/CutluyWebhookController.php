<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Billing\ApplyCutluyEvent;
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

        // The body is signed and the header is not, so the body names the event.
        $event = is_string($payload['type'] ?? null) ? $payload['type'] : (string) $request->header('X-CutLuy-Event', '');

        if (in_array($event, ApplyCutluyEvent::Events, true)) {
            ApplyCutluyWebhook::dispatch($event, $payload);
        }

        return response()->noContent();
    }
}
