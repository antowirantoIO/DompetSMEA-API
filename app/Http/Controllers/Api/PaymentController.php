<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Transfer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\CastServiceInterface;
use App\Services\AtomicServiceInterface;
use App\Services\PrepareServiceInterface;
use App\Services\TransferServiceInterface;

class PaymentController extends Controller
{
    /**
     * Handle the incoming request for payment mernchant.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @property String merchant_uuid
     * @property String amount
     * @property String pin
     */
    public function pay(Request $request)
    {
        $merchant = User::where('uuid', $request->merchant_uuid)->first();
        if($merchant){
            if(!$merchant->roles[0] == 'merchant'){
                return response()->json(['status' => 'failed', 'message' => 'User is not a merchant'], 200);
            }
        } else {
            return response()->json(['status' => 'failed', 'message' => 'Merchant not found'], 200);
        }
        if(Hash::check($request->pin, $merchant->pin)){
            if($request->amount > $merchant->wallet->balance){
                return response()->json(['status' => 'failed', 'message' => 'Insufficient balance'], 200);
            } else {
                $meta = [
                    'message' => $request->message,
                    'type' => 'payment',
                    'merchant_name' => $merchant->name,
                ];
                $transaction = app(AtomicServiceInterface::class)->block(Auth::user(), function () use ($merchant, $amount, $meta) {
                    $transferLazyDto = app(PrepareServiceInterface::class)
                        ->transferLazy(Auth::user(), $merchant, Transfer::STATUS_PAID, $amount, $meta);
                    $transfers = app(TransferServiceInterface::class)->apply([$transferLazyDto]);
                    return current($transfers);
                });
                return response()->json(['status' => 'success', 'message' => 'Payment has been made', 'data' => $transaction], 200);
            }
        } else {
            return response()->json(['status' => 'failed', 'message' => 'Invalid pin'], 200);
        }
    }

    public function paymentHistory(){
        $transfers = app(CastServiceInterface::class)
        ->getWallet(Auth::user(), false)
        ->morphMany(config('wallet.transfer.model', Transfer::class), 'from');
        return response()->json(['status' => 'success', 'message' => 'Payment history', 'data' => $transfers], 200);
    }
}
