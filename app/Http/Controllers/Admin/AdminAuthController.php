<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        // Проверяем пользователя
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверные учетные данные'],
            ]);
        }

        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $customClaims = ['role' => 'admin'];

        $token = JWTAuth::claims($customClaims)->attempt($credentials);

        if (!$token) {
            return response()->json(['error' => 'Не удалось создать токен'], 500);
        }

        return response()->json([
            'message' => 'Успешный вход',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function user(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json($user);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Недействительный токен'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Токен истёк'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Вы успешно вышли']);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Недействительный токен'], 401);
        }
    }
}