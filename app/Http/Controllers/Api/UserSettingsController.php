<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserSettingsController extends Controller
{
    public function setPin(Request $request)
    {
        $user = Auth::user();
        $user->pin = Hash::make($request->pin);
        $user->save();
        return response()->json(['message' => 'Pin has been set'], 200);
    }
}
