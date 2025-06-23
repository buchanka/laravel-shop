<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CartItem $cartItem): bool
    {
        return $user->id === optional($cartItem->cart)->user_id;
    }

    public function create(User $user): bool
    {
        return true; // Разрешаем создание без дополнительных проверок
    }

    public function update(User $user, CartItem $cartItem): bool
    {
        return $user->id === optional($cartItem->cart)->user_id;
    }

    public function delete(User $user, CartItem $cartItem): bool
    {
        return $user->id === optional($cartItem->cart)->user_id;
    }

    public function restore(User $user, CartItem $cartItem): bool
    {
        return $user->id === optional($cartItem->cart)->user_id;
    }

    public function forceDelete(User $user, CartItem $cartItem): bool
    {
        return $user->id === optional($cartItem->cart)->user_id;
    }
}
