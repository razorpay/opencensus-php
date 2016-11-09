<?php

namespace App\Providers;

use Illuminate\Auth\GenericUser;

class GenericUser extends GenericUser
{
    public function toArray()
    {
        return $this->attributes;
    }
}
