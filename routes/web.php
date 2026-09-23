<?php

use App\Http\Controllers\Website\MarketingController;
use App\Http\Controllers\Website\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('features', [MarketingController::class, 'features'])->name('features');
Route::get('how-it-works', [MarketingController::class, 'howItWorks'])->name('how-it-works');
Route::get('pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('testimonials', [MarketingController::class, 'testimonials'])->name('testimonials');
Route::get('sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

require __DIR__.'/settings.php';
require __DIR__.'/shop.php';
