<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Services\AtomicServiceInterface;
use App\Services\TransactionServiceInterface;

class DepositController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws AmountInvalid
     * @throws LockProviderNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     */
    public function __invoke(Request $request)
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
        // $transaction = Auth::user()->deposit($request->amount, $meta, false);
        $transaction = app(AtomicServiceInterface::class)->block(
            Auth::user(),
            fn () => app(TransactionServiceInterface::class)
                ->makeOne(Auth::user(), Transaction::TYPE_DEPOSIT, $request->amount, $meta, false)
        );
        return response()->json($transaction, 200);
    }

    private function _handleCharge($request){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => config('payment.midtrans.auth_string')
        ])->post(config('payment.midtrans.midtrans_url'), [
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
}
