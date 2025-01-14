<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('locale')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::name('store.')->prefix('/stores')->group(function () {

            Route::get('/', [StoreController::class, 'index'])->name('index');

            Route::post('/', [StoreController::class, 'store'])
                ->middleware('role:owner')
                ->name('store');

            Route::prefix('/{store}')->group(function () {

                Route::get('/', [StoreController::class, 'show'])->name('show');

                Route::name('product.')->prefix('/products')->group(function () {
                    Route::get('/', [StoreController::class, 'products'])->name('index');

                    Route::get('/available', [StoreController::class, 'availableProducts'])->name('available');

                    Route::post('/', [ProductController::class, 'store'])->name('store');

                });

                Route::post('/', [StoreController::class, 'update'])
                    ->middleware('role:owner')
                    ->name('update');

                Route::delete('/', [StoreController::class, 'destroy'])->name('destroy');
            });

        });

        Route::name('product.')->prefix('/products')->group(function () {

            Route::get('/', [ProductController::class, 'index'])
                ->middleware('role:owner')
                ->name('index');

            Route::prefix('/{product}')->group(function () {

                Route::get('/', [ProductController::class, 'show'])->name('show');

                Route::post('/', [ProductController::class, 'update'])->name('update');

                Route::delete('/', [ProductController::class, 'destroy'])->name('destroy');

                Route::post('/rate', [ProductController::class, 'rate'])->name('rate');
            });

        });

        Route::name('category.')->prefix('/categories')->group(function () {

            Route::get('/', [CategoryController::class, 'index'])->name('index');

            Route::middleware('role:owner')->group(function () {
                Route::post('/', [CategoryController::class, 'store'])->name('store');

                Route::prefix('/{category}')->group(function () {
                    Route::put('/', [CategoryController::class, 'update'])->name('update');

                    Route::delete('/', [CategoryController::class, 'destroy'])->name('destroy');
                });

            });
        });

        Route::get('/home', HomeController::class)->name('home');

        Route::get('/search', SearchController::class)->name('search');

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

    Route::post('/verificationCode', [UserController::class, 'code']);
    Route::post('setNewPassword/checkCode', [UserController::class, 'checkCode']);
    Route::post('/setNewPassword', [UserController::class, 'setNewPassword']);
    Route::post('/register', [LoginController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);

    Route::prefix('/user')->middleware('auth:sanctum')->group(function () {
        Route::post('/resetPassword', [UserController::class, 'resetPassword']);
        Route::delete('/delete-account', [UserController::class, 'deleteAccount']);
        Route::post('/', [UserController::class, 'update'])->name('update');
        Route::get('/', [UserController::class, 'show'])->name('show');
        Route::delete('/logout', [LoginController::class, 'logout']);
    });
    Route::name('order.')->prefix('/order')->middleware('auth:sanctum')->group(function () {
        Route::prefix('/user')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::post('/', [OrderController::class, 'store'])->name('store');
            Route::get('/update/{orderId}', [OrderController::class, 'update'])->name('update');
            Route::delete('/{orderId}', [OrderController::class, 'destroy'])->name('delete');
        });

        Route::name('store.')->prefix('/store')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [OrderController::class, 'ShowSubOrders'])->name('index');
            Route::patch('/{subOrderId}/updateState', [OrderController::class, 'updateState'])->name('updateState');
        });
    });
});
