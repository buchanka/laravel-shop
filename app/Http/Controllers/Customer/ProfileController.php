<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:customer']);
    }

    /**
     * Просмотр профиля текущего пользователя
     */
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('roles') 
        ]);
    }

    /**
     * Обновление профиля текущего пользователя
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
        ]);

        if (array_key_exists('middle_name', $validatedData) && $validatedData['middle_name'] === "") {
            $validatedData['middle_name'] = null; 
        }

        $user->update($validatedData);

        return response()->json([
            'message' => 'Профиль успешно обновлен',
            'user' => $user->fresh()
        ]);
    }
}
