<?php

namespace App\Http\Controllers\ContactApi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function userDetails()
    {
        return response()->json([
            'user' => Auth::user(),
        ]);
    }
}
