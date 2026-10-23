<?php

namespace App\Actions\Auth;

final class NormalizeIdentity
{
    public static function email(?string $email): ?string
    {
        return filled($email) ? mb_strtolower(trim($email)) : null;
    }

    public static function phone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $phone = trim($phone);

        return preg_match('/^0[35789][0-9]{8}$/D', $phone) ? '+84'.substr($phone, 1) : $phone;
    }
}
