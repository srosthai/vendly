<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
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
use App\Http\Controllers\Selling\StartSellingController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreSuspensionController;
use App\Http\Controllers\TelegramLinkController;
use App\Http\Controllers\Vendor\WorkspaceController;
use App\Http\Controllers\Webhooks\CutluyWebhookController;
use App\Http\Controllers\Webhooks\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/cutluy', CutluyWebhookController::class)->name('webhooks.cutluy');
Route::post('webhooks/telegram', TelegramWebhookController::class)->name('webhooks.telegram');

Route::get('sign-in', [EmailCodeController::class, 'create'])->name('auth.sign-in');
Route::get('sign-in/code', [EmailCodeController::class, 'code'])->name('auth.sign-in.code');

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
    Route::get('dashboard', [WorkspaceController::class, 'home'])->name('dashboard');
    Route::get('start-selling', [StartSellingController::class, 'create'])->name('selling.create');
    Route::get('vendor/store', [WorkspaceController::class, 'store'])->name('vendor.store');
    Route::put('vendor/store', [WorkspaceController::class, 'updateStore'])->name('vendor.store.update');
    Route::get('vendor/products', [WorkspaceController::class, 'products'])->name('vendor.products');
    Route::get('vendor/products/create', [WorkspaceController::class, 'createProduct'])->name('vendor.products.create');
    Route::get('vendor/categories', [WorkspaceController::class, 'categories'])->name('vendor.categories');
    Route::get('vendor/brands', [WorkspaceController::class, 'brands'])->name('vendor.brands');
    Route::get('vendor/plan', [WorkspaceController::class, 'plan'])->name('vendor.plan');
    Route::get('vendor/telegram', [WorkspaceController::class, 'telegram'])->name('vendor.telegram');
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

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('vendors', [AdminDashboardController::class, 'vendors'])->name('vendors');
        Route::get('plans', [AdminDashboardController::class, 'plans'])->name('plans');
        Route::get('payments', [AdminDashboardController::class, 'payments'])->name('payments');
        Route::get('telegram', [AdminDashboardController::class, 'telegram'])->name('telegram');
        Route::put('telegram', [AdminDashboardController::class, 'updateTelegram'])->name('telegram.update');
        Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::post('stores/{store}/suspend', [StoreSuspensionController::class, 'store'])->name('stores.suspend');
        Route::delete('stores/{store}/suspend', [StoreSuspensionController::class, 'destroy'])->name('stores.restore');
    });
});
