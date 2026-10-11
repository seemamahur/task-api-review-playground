<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    private const DUMMY_HASH = '$2y$12$6fv5Jd298sVUQdTw4WH1Z.KsykVDlk4LyGR8B0nfNJk9yH5F/iFqa';
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:128',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json(['id' => $user->id, 'email' => $user->email], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        // Always run one bcrypt check so response time doesn't reveal
        // whether the email is registered.
        $passwordOk = Hash::check($data['password'], $user?->password ?? self::DUMMY_HASH);

        if (! $user || ! $passwordOk) {
            return response()->json(['error' => 'invalid credentials'], 401);
        }

        // Remove old tokens so they don't pile up, and expire the new one in 24 hours.
        $user->tokens()->where('name', 'api')->delete();

        return response()->json([
            'token' => $user->createToken('api', ['*'], now()->addDay())->plainTextToken,
        ]);
    }
}
