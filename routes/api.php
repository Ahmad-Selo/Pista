<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login/{id}', [LoginController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::name('store.')->prefix('/stores')->group(function () {

        Route::get('/', [StoreController::class, 'index'])->name('index');

        Route::post('/', [StoreController::class, 'store'])
            ->middleware('role:admin')
            ->name('store');

        Route::prefix('/{store}')->group(function () {

            Route::get('/', [StoreController::class, 'show'])->name('show');

            Route::name('product.')->prefix('/products')->group(function () {

                Route::get('/', [StoreController::class, 'products'])->name('index');

                Route::post('/', [ProductController::class, 'store'])->name('store');

            });

            Route::post('/', [StoreController::class, 'update'])
                ->middleware('role:admin')
                ->name('update');

            Route::delete('/', [StoreController::class, 'destroy'])->name('destroy');
        });

    });

    Route::name('product.')->prefix('/products')->group(function () {

        Route::get('/', [ProductController::class, 'index'])->name('index');

        Route::get('/search', [ProductController::class, 'search'])->name('search');

        Route::prefix('/{product}')->group(function () {

            Route::get('/', [ProductController::class, 'show'])->name('show');

            Route::post('/', [ProductController::class, 'update'])->name('update');

            Route::delete('/', [ProductController::class, 'destroy'])->name('destroy');
        });

    });

    // TODO: need to check {user} with user access token
    Route::get('/users/{user}/stores', [UserController::class, 'stores'])->name('user.stores');
});

