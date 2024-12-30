<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function stores(User $user)
    {
        $stores = $user->stores;

        return response()->json([
            'stores' => $stores,
        ]);
    }
}
