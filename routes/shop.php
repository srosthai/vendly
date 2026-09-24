<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\TelegramSettingsController;
use App\Http\Controllers\Admin\TelegramTestController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TelegramAuthController;
use App\Http\Controllers\Billing\PlanAvailabilityController;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\PlanPaymentController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\MiniAppController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Selling\StartSellingController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreSuspensionController;
use App\Http\Controllers\TelegramLinkController;
use App\Http\Controllers\Vendor\RequestController as VendorRequestController;
use App\Http\Controllers\Vendor\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::get('register/code', [RegisterController::class, 'code'])->name('auth.register.code');
    Route::post('register/code', [RegisterController::class, 'store'])
        ->middleware('throttle:email-code')
        ->name('auth.register.store');
    Route::post('register/code/resend', [RegisterController::class, 'resend'])
        ->middleware('throttle:email-code')
        ->name('auth.register.resend');
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
Route::patch('s/{store:slug}/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('s/{store:slug}/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [WorkspaceController::class, 'home'])->name('dashboard');
    Route::get('start-selling', [StartSellingController::class, 'create'])->name('selling.create');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
    Route::middleware('throttle:inquiries')->group(function () {
        Route::post('s/{store:slug}/products/{product}/buy', [InquiryController::class, 'product'])->name('inquiries.product');
        Route::post('s/{store:slug}/cart/send', [InquiryController::class, 'cart'])->name('inquiries.cart');
    });

    Route::get('search', SearchController::class)
        ->middleware('throttle:60,1')
        ->name('search');

    Route::middleware('vendor')->group(function () {
        Route::get('vendor/store', [WorkspaceController::class, 'store'])->name('vendor.store');
        Route::put('vendor/store', [WorkspaceController::class, 'updateStore'])->name('vendor.store.update');
        Route::get('vendor/products', [WorkspaceController::class, 'products'])->name('vendor.products');
        Route::get('vendor/products/create', [WorkspaceController::class, 'createProduct'])->name('vendor.products.create');
        Route::get('vendor/categories', [WorkspaceController::class, 'categories'])->name('vendor.categories');
        Route::get('vendor/brands', [WorkspaceController::class, 'brands'])->name('vendor.brands');
        Route::get('vendor/plan', [WorkspaceController::class, 'plan'])->name('vendor.plan');
        Route::get('vendor/telegram', [WorkspaceController::class, 'telegram'])->name('vendor.telegram');
        Route::get('vendor/requests', [VendorRequestController::class, 'index'])->name('vendor.requests');
        Route::patch('vendor/requests/{inquiry}', [VendorRequestController::class, 'update'])->name('vendor.requests.update');
        Route::get('vendor/products/{product}/edit', [WorkspaceController::class, 'editProduct'])->name('vendor.products.edit');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
        Route::put('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::scopeBindings()->group(function () {
            Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
            Route::post('products/{product}/images/{image}/cover', [ProductImageController::class, 'cover'])->name('products.images.cover');
        });
        Route::post('products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
        Route::post('telegram/link', [TelegramLinkController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('telegram.link');
        Route::delete('telegram/link', [TelegramLinkController::class, 'destroy'])->name('telegram.unlink');
        Route::post('telegram/test', [TelegramLinkController::class, 'test'])
            ->middleware('throttle:5,1')
            ->name('telegram.test');
        Route::get('vendor/plan/payments/{publicId}', [PlanPaymentController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('vendor.plan.payments.show');
        Route::post('vendor/plan/payments/{publicId}/cancel', [PlanPaymentController::class, 'cancel'])
            ->middleware('throttle:30,1')
            ->name('vendor.plan.payments.cancel');
        Route::post('plans/{plan}/payments', [PlanPaymentController::class, 'store'])
            ->middleware('throttle:plan-payments')
            ->name('plans.payments.store');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('vendors', [AdminDashboardController::class, 'vendors'])->name('vendors');
        Route::get('vendors/{store}', [VendorController::class, 'show'])->name('vendors.show');
        Route::get('plans', [AdminDashboardController::class, 'plans'])->name('plans');
        Route::get('payments', [AdminDashboardController::class, 'payments'])->name('payments');
        Route::get('telegram', [TelegramSettingsController::class, 'edit'])->name('telegram');
        Route::get('requests', [AdminInquiryController::class, 'index'])->name('requests');
        Route::get('site', [SiteSettingsController::class, 'edit'])->name('site');
        Route::put('site', [SiteSettingsController::class, 'update'])->name('site.update');
        Route::put('site/cutluy', [SiteSettingsController::class, 'updateCutluy'])->name('site.cutluy.update');
        Route::put('site/google', [SiteSettingsController::class, 'updateGoogle'])->name('site.google.update');
        Route::post('site/payment-methods', [SiteSettingsController::class, 'storePaymentMethod'])->name('site.payment-methods.store');
        Route::put('site/payment-methods/{paymentMethod}', [SiteSettingsController::class, 'updatePaymentMethod'])->name('site.payment-methods.update');
        Route::delete('site/payment-methods/{paymentMethod}', [SiteSettingsController::class, 'destroyPaymentMethod'])->name('site.payment-methods.destroy');
        Route::get('testimonials', [AdminTestimonialController::class, 'index'])->name('testimonials');
        Route::post('testimonials', [AdminTestimonialController::class, 'store'])->name('testimonials.store');
        Route::put('testimonials/{testimonial}', [AdminTestimonialController::class, 'update'])->name('testimonials.update');
        Route::delete('testimonials/{testimonial}', [AdminTestimonialController::class, 'destroy'])->name('testimonials.destroy');
        Route::post('requests/{inquiry}/retry', [AdminInquiryController::class, 'retry'])
            ->middleware('throttle:30,1')
            ->name('requests.retry');
        Route::put('telegram', [TelegramSettingsController::class, 'update'])->name('telegram.update');
        Route::post('telegram/webhook', [TelegramSettingsController::class, 'registerWebhook'])
            ->middleware('throttle:6,1')
            ->name('telegram.webhook');
        Route::post('telegram/test', TelegramTestController::class)
            ->middleware('throttle:6,1')
            ->name('telegram.test');
        Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::patch('plans/{plan}/availability', PlanAvailabilityController::class)->name('plans.availability');
        Route::post('stores/{store}/suspend', [StoreSuspensionController::class, 'store'])->name('stores.suspend');
        Route::delete('stores/{store}/suspend', [StoreSuspensionController::class, 'destroy'])->name('stores.restore');
    });
});
