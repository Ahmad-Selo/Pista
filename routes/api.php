<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/
Route::post('/verificationCode',[UserController::class, 'code']);
Route::post('setNewPassword/checkCode',[UserController::class, 'checkCode']);
Route::post('/setNewPassword',[UserController::class, 'setNewPassword']);
Route::post('/register',[LoginController::class, 'register']);
Route::post('/login',[LoginController::class, 'login']);
Route::prefix('/user')->middleware('auth:sanctum')->group(function(){
    Route::delete('/logout',[LoginController::class, 'logout']);
    Route::post('/resetPassword',[UserController::class, 'resetPassword']);
    Route::delete('/delete-account',[UserController::class, 'deleteAccount']);///////////////////////////////////
    Route::post('/',[UserController::class, 'update'])->name('update');
    Route::get('/',[UserController::class, 'show'])->name('show');
});
Route::name('order.')->prefix('/order')->middleware('auth:sanctum')->group(function(){
    Route::prefix('/user')->group(function(){
        Route::get('/',[OrderController::class, 'index'])->name('index');
        Route::post('/',[OrderController::class,'store'])->name('store');
        Route::get('/update/{orderId}',[OrderController::class, 'update'])->name('update');
        Route::delete('/{orderId}',[OrderController::class, 'destroy'])->name('delete');});

    Route::name('store.')->prefix('/store')->middleware('auth:sanctum')->group(function(){
        Route::get('/',[OrderController::class, 'ShowSubOrders'])->name('index');
        Route::patch('/{subOrderId}/updateState',[OrderController::class,'updateState'])->name('updateState');
});
});
