<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return response()->json([
        'api' => config('app.name'),
        'status' => 'online',
        'version' => config('app.version'),
        'documentation' => config('app.url').'/'.config('l5-swagger.documentations.default.routes.api'),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth:api')
    ->group(
        function () {
            Route::prefix('auth')
                ->name('auth.')
                ->controller(AuthController::class)
                ->group(
                    function () {
                        Route::post('logout', 'logout')->name('logout');
                        Route::post('refresh', 'refresh')->name('refresh');
                        Route::get('user', 'user')->name('user');
                    }
                );
        }
    );
