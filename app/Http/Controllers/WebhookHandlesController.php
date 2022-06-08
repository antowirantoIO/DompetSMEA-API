<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class WebhookHandlesController extends Controller
{
    public function handleWebhookMidtrans(Request $request){
        $transaction = Transaction::where('meta->order_id', $request['order_id'])->first();

        $user = $transaction->user;

        if($transaction){
            DB::table('transactions')
                ->where('id', $transaction->id)
                ->update([
                'meta->transaction_status' => $request['transaction_status'],
            ]);

            if($request['transaction_status'] == 'settlement'){
                $user->confirm($transaction);
                $user->wallet->refreshBalance();
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
