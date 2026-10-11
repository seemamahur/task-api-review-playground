<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $hashed = md5($request->password);
        $now = date('Y-m-d H:i:s');

        DB::insert("INSERT INTO users (name, email, password, created_at, updated_at)
                    VALUES ('{$request->name}', '{$request->email}', '{$hashed}', '{$now}', '{$now}')");

        return response()->json(['status' => 'registered'], 201);
    }

    public function login(Request $request)
    {
        $hashed = md5($request->password);

        $user = DB::select("SELECT * FROM users
                            WHERE email = '{$request->email}' AND password = '{$hashed}'");

        if ($user) {
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['error' => 'invalid credentials'], 401);
    }
}
