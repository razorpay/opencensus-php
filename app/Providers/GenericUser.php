<?php

namespace App\Providers;

use Illuminate\Auth\GenericUser as AuthGenericUser;

class GenericUser extends AuthGenericUser
{
    public function toArray()
    {
        return $this->attributes;
    }
}
