<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(int $id)
    {
        $user = User::find($id);

        $token = $user->createToken('access_token')->plainTextToken;

        return response()->json([
            'access_token' => $token
        ]);
    }
}
