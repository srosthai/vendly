<?php

use App\Http\Controllers\Auth\EmailCodeController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\TelegramAuthController;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\PlanPaymentController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreSuspensionController;
use App\Http\Controllers\TelegramLinkController;
use App\Http\Controllers\Webhooks\CutluyWebhookController;
use App\Http\Controllers\Webhooks\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/cutluy', CutluyWebhookController::class)->name('webhooks.cutluy');
Route::post('webhooks/telegram', TelegramWebhookController::class)->name('webhooks.telegram');

Route::middleware('throttle:email-code')->group(function () {
    Route::post('auth/email-code', [EmailCodeController::class, 'store'])->name('auth.email-code.store');
});

Route::post('auth/email-code/verify', [EmailCodeController::class, 'verify'])->name('auth.email-code.verify');
Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('auth/telegram', [TelegramAuthController::class, 'store'])
    ->middleware('throttle:telegram-auth')
    ->name('auth.telegram.store');

Route::get('s/{store:slug}', [StoreController::class, 'show'])->name('stores.show');
Route::get('s/{store:slug}/p/{productSlug}', [ProductController::class, 'show'])->name('stores.products.show');
Route::post('s/{store:slug}/products/{product}/cart', [CartController::class, 'store'])->name('cart.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
    Route::post('telegram/link', [TelegramLinkController::class, 'store'])->name('telegram.link');
    Route::post('s/{store:slug}/products/{product}/buy', [InquiryController::class, 'product'])->name('inquiries.product');
    Route::post('s/{store:slug}/cart/send', [InquiryController::class, 'cart'])->name('inquiries.cart');
    Route::post('plans/{plan}/payments', [PlanPaymentController::class, 'store'])->name('plans.payments.store');

    Route::middleware('admin')->group(function () {
        Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::post('stores/{store}/suspend', [StoreSuspensionController::class, 'store'])->name('stores.suspend');
        Route::delete('stores/{store}/suspend', [StoreSuspensionController::class, 'destroy'])->name('stores.restore');
    });
});
