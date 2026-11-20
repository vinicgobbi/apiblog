<?php

use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/api');

Route::get('feed', [FeedController::class, 'index'])->name('feed.index');
Route::get('feed/tags/{tag:slug}', [FeedController::class, 'tag'])->name('feed.tag');
Route::get('feed/categories/{category:slug}', [FeedController::class, 'category'])->name('feed.category');
