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

class TransferController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if(Hash::check($request->pin, Auth::user()->pin)){
            if($request->amount > Auth::user()->wallet->balance){
                return response()->json(['status' => 'failed', 'message' => 'Insufficient balance'], 200);
            } else {
                $meta = [
                    'message' => $request->message,
                    'type' => 'transfer',
                ];
                $reciver = User::where('id', $request->to_user_id)->first();
                $amount = $request->amount;
                if($reciver){
                    $transaction = app(AtomicServiceInterface::class)->block(Auth::user(), function () use ($reciver, $amount, $meta) {
                        $transferLazyDto = app(PrepareServiceInterface::class)
                            ->transferLazy(Auth::user(), $reciver, Transfer::STATUS_TRANSFER, $amount, $meta)
                        ;

                        $transfers = app(TransferServiceInterface::class)->apply([$transferLazyDto]);

                        return current($transfers);
                    });
                } else {
                    return response()->json(['status' => 'failed', 'message' => 'Reciver not found'], 200);
                }
                return response()->json(['status' => 'success', 'message' => 'Transfer has been made', 'data' => $transaction], 200);
            }
        } else {
            return response()->json(['status' => 'failed', 'message' => 'Invalid pin'], 200);
        }
    }

    

    /**
     * the transfer table is used to confirm the payment this method receives all transfers.
     */
    public function transferHistory(){
        $transfers = Auth::user()->wallet->transfers;

        return response()->json($transfers, 200);
    }
}
