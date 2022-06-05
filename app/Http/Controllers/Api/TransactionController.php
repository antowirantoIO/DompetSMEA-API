<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Services\ConsistencyServiceInterface;

class TransactionController extends Controller
{
    public function transactionHistory()
    {
        $data = Auth::user()->transactions;
        return response()->json($data, 200);
    }

    public function depositBalance(Request $request)
    {
        $response = $this->_handleCharge($request);
        $meta = [
            'transaction_id' => $response['transaction_id'],
            'order_id' => $response['order_id'],
            'merchant_id' => $response['merchant_id'],
            'payment_type' => $request->payment_type,
            'vendor' => $request->vendor,
            'transaction_time' => $response['transaction_time'],
            'transaction_status' => $response['transaction_status'],
            'va_numbers' => $response['va_numbers'] ?? $response['permata_va_number'] ?? '',
            'biller_code' => $response['biller_code'] ?? '',
            'bill_key' => $response['bill_key'] ?? '',
            'action_status' => $response['action'] ?? '',
            'redirect_url' => $response['redirect_url'] ?? '',
            'payment_code' => $response['payment_code'] ?? '',
        ];
        $transaction = Auth::user()->deposit($request->amount, $meta, false);
        return response()->json($transaction, 200);
    }

    private function _handleCharge($request){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => env('AUTH_STRING')
        ])->post(env('URL_MIDTRANS'), [
            'payment_type' => $request->payment_type,
            'transaction_details' => [
                'order_id' => 'DMTS-' . time(),
                'gross_amount' => $request->amount
            ],
            'bank_transfer' => [
                'bank' => $request->vendor ?? '',
            ],
            "customer_details" => [
                "first_name" => Auth::user()->name,
                "email" => Auth::user()->email,
                "phone" => Auth::user()->number_phone ?? '',
            ],
            "echannel" => [
                "bill_info1" => "Payment:",
                "bill_info2" => "Isi Saldo DompetSMEA"
            ],
            "bca_klikpay" => [
                "description" => "Isi Saldo DompetSMEA"
            ],
            "cimb_clicks" => [
                "description" => "Isi Saldo DompetSMEA"
            ],
        ]);

        return $response->json();
    }

    public function handleWebhook(Request $request){
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

    public function transferBalance(Request $request)
    {
        if(Hash::check($request->pin, Auth::user()->pin)){
            if($request->amount > Auth::user()->wallet->balance){
                return response()->json(['status' => 'failed', 'message' => 'Insufficient balance'], 200);
            } else {
                $meta = [
                    'message' => $request->message,
                    'type' => 'trasfer',
                ];
                $reciver = User::where('id', $request->to_user_id)->first();
                Auth::user()->forceTransfer($reciver, $request->amount, $meta);
                return response()->json(['status' => 'success', 'message' => 'Transfer has been made'], 200);
            }
        } else {
            return response()->json(['status' => 'failed', 'message' => 'Invalid pin'], 200);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
