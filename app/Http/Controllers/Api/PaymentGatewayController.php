<?php

namespace App\Http\Controllers\Api;

use Xendit\Xendit;
use Xendit\Balance;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentGatewayController extends Controller
{
    public function __construct()
    {
        Xendit::setApiKey(config('payment.xendit.secret_key'));
    }

    public function getBalanceAccount(Request $request)
    {
        $balance = Balance::getBalance('CASH');
        return response()->json($balance, 200);
    }
}
