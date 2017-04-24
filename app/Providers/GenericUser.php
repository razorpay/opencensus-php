<?php

namespace App\Providers;

use App\User;
use App\Merchant;
use Illuminate\Auth\GenericUser as AuthGenericUser;

class GenericUser extends AuthGenericUser
{
    public function toArray()
    {
        return $this->attributes;
    }

    public function currentMerchant()
    {
        $currentMerchant = (new User\Service)->getCurrentMerchant($this->attributes);

        return new Merchant\GenericMerchant((array) $currentMerchant);
    }
}
