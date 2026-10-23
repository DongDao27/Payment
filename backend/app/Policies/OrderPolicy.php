<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function view(User $user, Order $order): Response
    {
        return $user->isAdmin() || $order->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, Order $order): Response
    {
        return $this->view($user, $order);
    }
}
