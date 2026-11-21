<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use App\Http\Middleware\SetPublicCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return response()->json([
        'api' => config('app.name'),
        'status' => 'online',
        'version' => config('app.version'),
        'documentation' => config('app.url').'/'.config('l5-swagger.documentations.default.routes.api'),
    ]);
})->middleware(SetPublicCacheHeaders::class)->name('status');

Route::middleware(SetPublicCacheHeaders::class)->group(function () {
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/{post:slug}', [PostController::class, 'show'])->name('posts.show');
    Route::get('posts/{post:slug}/comments', [PostCommentController::class, 'index'])->name('posts.comments.index');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::get('tags/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
});

Route::post('posts/{post:slug}/comments', [PostCommentController::class, 'store'])->middleware('throttle:3,1')->name('posts.comments.store');

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:10,1')->name('auth.refresh');
});

Route::middleware(['auth:api', 'cache.headers:no_store;private'])
    ->group(
        function () {
            Route::prefix('auth')
                ->name('auth.')
                ->controller(AuthController::class)
                ->group(
                    function () {
                        Route::post('logout', 'logout')->name('logout');
                        Route::get('user', 'user')->name('user');
                    }
                );
        }
    );

Route::middleware(['auth:api', 'cache.headers:no_store;private'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::apiResource('posts', Admin\PostController::class);
        Route::apiResource('categories', Admin\CategoryController::class);
        Route::apiResource('tags', Admin\TagController::class);
        Route::apiResource('pages', Admin\PageController::class);
        Route::apiResource('media', Admin\MediaController::class)->parameters(['media' => 'media']);
        Route::get('comments/counts', [Admin\CommentController::class, 'counts'])->name('comments.counts');
        Route::apiResource('comments', Admin\CommentController::class)->except(['store']);
        Route::post('markdown/preview', Admin\MarkdownPreviewController::class)->name('markdown.preview');
    });
