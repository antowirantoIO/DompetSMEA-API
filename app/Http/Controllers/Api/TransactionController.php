<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\CastServiceInterface;

class TransactionController extends Controller
{
    public function transactionHistory()
    {
        $data = Auth::user()->wallet->transactions;

        return response()->json($data, 200);
    }

    public function showDetail(Transaction $transaction)
    {
        return response()->json($transaction, 200);
    }
}
