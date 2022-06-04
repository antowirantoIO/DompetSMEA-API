<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserSettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('transaction/handlewebhook', [TransactionController::class, 'handleWebhook']);

Route::middleware(['auth:sanctum'])->group(function(){
    Route::prefix('wallet')->group(function(){
        Route::get('getWallet', [WalletController::class, 'getWallet']);
    });

    Route::prefix('transaction')->group(function(){
        Route::post('depositBalance', [TransactionController::class, 'depositBalance']);
        Route::post('transferBalance', [TransactionController::class, 'transferBalance']);
    });

    Route::prefix('settings/user')->group(function(){
        Route::post('setPin', [UserSettingsController::class, 'setPin']);
    });
});
