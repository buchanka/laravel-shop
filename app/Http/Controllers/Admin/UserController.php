<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:admin']);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
         return User::select(['id', 'first_name', 'last_name', 'middle_name', 'email', 'avatar'])
              ->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[А-ЯЁ][а-яё]*(?:-[А-ЯЁ][а-яё]*)*$/u'
            ],
            'last_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[А-ЯЁ][а-яё]*(?:-[А-ЯЁ][а-яё]*)*$/u'
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[А-ЯЁ][а-яё]*(?:-[А-ЯЁ][а-яё]*)*$/u'
            ],
            'email' => [
                'required',
                'string',
                'email', 
                'max:255',
                'unique:users'
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/',
                'unique:users'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'role' => 'sometimes|string|in:admin,customer'
        ], [
            'first_name.regex' => 'Имя должно начинаться с заглавной буквы и может содержать дефис',
            'last_name.regex' => 'Фамилия должна начинаться с заглавной буквы и может содержать дефис',
            'middle_name.regex' => 'Отчество должно начинаться с заглавной буквы и может содержать дефис',
            'phone.regex' => 'Телефон должен быть в формате +7(999)123-45-67',
            'password.regex' => 'Пароль должен содержать минимум 8 символов, одну заглавную букву, одну цифру и один спецсимвол',
        ]);

        $validatedData['first_name'] = $this->normalizeName($validatedData['first_name']);
        $validatedData['last_name'] = $this->normalizeName($validatedData['last_name']);
        $validatedData['middle_name'] = !empty($validatedData['middle_name']) 
            ? $this->normalizeName($validatedData['middle_name'])
            : null;

        $validatedData['password'] = Hash::make($validatedData['password']);

        $validatedData['avatar'] = 'https://storage.yandexcloud.net/new-test-bucket-123/placeholder-avatar.png';
        
        $user = User::create($validatedData);
        $roleName = $request->input('role', 'customer');
    
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        
        // Затем назначаем роль пользователю
        $user->assignRole($role);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Пользователь создан',
            'token' => $token,
            'user' => $user,
        ]);
}

    private function normalizeName(string $name): string
    {
        $parts = explode('-', $name);
        $normalizedParts = array_map(function($part) {
            return Str::ucfirst(Str::lower($part));
        }, $parts);
        return implode('-', $normalizedParts);
    }

    public function show(User $user)
    {
        return $user;
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[А-ЯЁ][а-яё]*(?:-[А-ЯЁ][а-яё]*)*$/u'
            ],
            'last_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[А-ЯЁ][а-яё]*(?:-[А-ЯЁ][а-яё]*)*$/u'
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[А-ЯЁ][а-яё]*(?:-[А-ЯЁ][а-яё]*)*$/u'
            ],
            'email' => [
                'required',
                'string',
                'email', 
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/',
                'unique:users,phone,' . $user->id,
            ],
        ], [
            'first_name.regex' => 'Имя должно начинаться с заглавной буквы и может содержать дефис',
            'last_name.regex' => 'Фамилия должна начинаться с заглавной буквы и может содержать дефис',
            'middle_name.regex' => 'Отчество должно начинаться с заглавной буквы и может содержать дефис',
            'phone.regex' => 'Телефон должен быть в формате +7(999)123-45-67',
        ]);

        $validatedData['first_name'] = $this->normalizeName($validatedData['first_name']);
        $validatedData['last_name'] = $this->normalizeName($validatedData['last_name']);
        $validatedData['middle_name'] = !empty($validatedData['middle_name']) 
            ? $this->normalizeName($validatedData['middle_name'])
            : null;

        $user->update($validatedData);

        return $user;
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    public function uploadAvatar(Request $request, User $user)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $file = $request->file('avatar');
        $filename = 'user_' . $user->id . '.' . $file->getClientOriginalExtension();
        $path = 'avatars/' . $filename;

        try {
            if ($user->avatar) {
                $oldPath = parse_url($user->avatar, PHP_URL_PATH);
                Storage::disk('yandex')->delete($oldPath);
            }

            Storage::disk('yandex')->put($path, file_get_contents($file), 'public');
            
            $user->avatar = Storage::disk('yandex')->url($path);
            $user->save();

            \Log::info("Uploading avatar for user {$user->id}, filename: {$filename}");

            return response()->json([
                'message' => 'Аватар успешно загружен',
                'url' => $user->avatar
            ]);

        } catch (\Exception $e) {
            \Log::error("Upload error: " . $e->getMessage());
            return response()->json(['message' => 'Ошибка загрузки: ' . $e->getMessage()], 500);
        }
    }
}
