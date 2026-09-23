<?php

use App\Http\Controllers\Webhooks\CutluyWebhookController;
use App\Http\Controllers\Webhooks\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
 * Webhooks run outside the web middleware group: no session, no cookies,
 * and no CSRF token. Each controller's secret check is the only gate.
 */
Route::post('webhooks/cutluy', CutluyWebhookController::class)->name('webhooks.cutluy');
Route::post('webhooks/telegram', TelegramWebhookController::class)->name('webhooks.telegram');
