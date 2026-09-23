<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TelegramAuthController;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\PlanPaymentController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\MiniAppController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Selling\StartSellingController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreSuspensionController;
use App\Http\Controllers\TelegramLinkController;
use App\Http\Controllers\Vendor\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::get('register/code', [RegisterController::class, 'code'])->name('auth.register.code');
    Route::post('register/code', [RegisterController::class, 'store'])
        ->middleware('throttle:email-code')
        ->name('auth.register.store');
    Route::post('register/code/verify', [RegisterController::class, 'verify'])
        ->middleware('throttle:register-verify')
        ->name('auth.register.verify');
});

Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('auth/telegram', [TelegramAuthController::class, 'store'])
    ->middleware('throttle:telegram-auth')
    ->name('auth.telegram.store');

Route::get('m', MiniAppController::class)->name('mini-app');
Route::get('s/{store:slug}', [StoreController::class, 'show'])->name('stores.show');
Route::get('s/{store:slug}/p/{productSlug}', [ProductController::class, 'show'])->name('stores.products.show');
Route::post('s/{store:slug}/products/{product}/cart', [CartController::class, 'store'])->name('cart.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [WorkspaceController::class, 'home'])->name('dashboard');
    Route::get('start-selling', [StartSellingController::class, 'create'])->name('selling.create');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
    Route::middleware('throttle:inquiries')->group(function () {
        Route::post('s/{store:slug}/products/{product}/buy', [InquiryController::class, 'product'])->name('inquiries.product');
        Route::post('s/{store:slug}/cart/send', [InquiryController::class, 'cart'])->name('inquiries.cart');
    });

    Route::middleware('vendor')->group(function () {
        Route::get('vendor/store', [WorkspaceController::class, 'store'])->name('vendor.store');
        Route::put('vendor/store', [WorkspaceController::class, 'updateStore'])->name('vendor.store.update');
        Route::get('vendor/products', [WorkspaceController::class, 'products'])->name('vendor.products');
        Route::get('vendor/products/create', [WorkspaceController::class, 'createProduct'])->name('vendor.products.create');
        Route::get('vendor/categories', [WorkspaceController::class, 'categories'])->name('vendor.categories');
        Route::get('vendor/brands', [WorkspaceController::class, 'brands'])->name('vendor.brands');
        Route::get('vendor/plan', [WorkspaceController::class, 'plan'])->name('vendor.plan');
        Route::get('vendor/telegram', [WorkspaceController::class, 'telegram'])->name('vendor.telegram');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
        Route::post('telegram/link', [TelegramLinkController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('telegram.link');
        Route::post('plans/{plan}/payments', [PlanPaymentController::class, 'store'])->name('plans.payments.store');
    });

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
