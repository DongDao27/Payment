<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CartPolicy
{
    public function update(User $user, Cart $cart): Response
    {
        return $user->role === 'customer' && $cart->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
