<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Role, Permission};
use App\Models\User;
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Создание разрешений
        $permissions = [
            'manage users',
            'manage products',
            'manage orders',
            'view orders',
            'create order',
            'cancel order',
            'manage favorites',
            'manage cart'
        ];
    
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    
        // Создание ролей
        $admin = Role::create(['name' => 'admin'])
            ->givePermissionTo(['manage users', 'manage products', 'manage orders']);
    
        $customer = Role::create(['name' => 'customer'])
            ->givePermissionTo(['view orders', 'create order', 'cancel order', 'manage favorites', 'manage cart']);
    
        // Назначение роли админа первому пользователю
        User::first()->assignRole('admin');

        
    }
}
