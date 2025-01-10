<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
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

        Route::get('/', [ProductController::class, 'index'])
            ->middleware('role:admin')
            ->name('index');

        Route::prefix('/{product}')->group(function () {

            Route::get('/', [ProductController::class, 'show'])->name('show');

            Route::post('/', [ProductController::class, 'update'])->name('update');

            Route::delete('/', [ProductController::class, 'destroy'])->name('destroy');
        });

    });

    Route::get('/home', HomeController::class)->name('home');

    Route::get('/search', SearchController::class)->name('search');

    // TODO: need to check {user} with user access token

    Route::name('user.')->prefix('/users')->group(function () {

        Route::prefix('/{user}')->group(function () {

            Route::get('/stores', [UserController::class, 'stores'])->name('store.index');

            Route::name('favorite.')->prefix('/favorites')
                ->middleware('ownership')->group(function () {

                    Route::get('/', [FavoriteController::class, 'index'])->name('index');

                    Route::post('/{product}', [FavoriteController::class, 'store'])->name('store');

                    Route::delete('/{product}', [FavoriteController::class, 'destroy'])->name('destroy');
                });

        });
    });
});

