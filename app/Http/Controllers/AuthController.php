<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;

class AuthController extends Controller
{
    public function login(AuthRequest $request)
    {
        return response()->json(['error' => 'Not Implemented']);

    }

    public function refresh()
    {
        return response()->json(['error' => 'Not Implemented']);
    }

    public function logout()
    {
        return response()->json(['error' => 'Not Implemented']);
    }

    public function user()
    {
        return response()->json(['error' => 'Not Implemented']);
    }
}
