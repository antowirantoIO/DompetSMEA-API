<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\WebhookHandlesController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserSettingsController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Api\PaymentGatewayController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;

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

Route::post('/transaction/handlewebhook', [WebhookHandlesController::class, 'handleWebhookMidtrans']);

Route::middleware(['auth:sanctum'])->group(function(){
    Route::get('/wallet/getWallet', [WalletController::class, 'getWallet']);

    Route::get('/transaction/history', [TransactionController::class, 'transactionHistory']);
    Route::get('/transaction/showDetail/{transaction:uuid}', [TransactionController::class, 'showDetail']);

    Route::post('/transaction/depositBalance', DepositController::class);
    Route::post('/transaction/transferBalance', TransferController::class);

    Route::get('/transfer/history', [TransferController::class, 'transferHistory']);
    Route::get('/payment/history', [PaymentController::class, 'paymentHistory']);

    Route::post('/transaction/pay', [PaymentController::class, 'pay']);

    Route::post('/settings/user/setPin', [UserSettingsController::class, 'setPin']);
});
