<?php

namespace App\Providers;

use App\User\Helper;
use Illuminate\Auth\GenericUser as AuthGenericUser;

class GenericUser extends AuthGenericUser
{
    public function toArray()
    {
        $merchantArray = $this->merchants->toArray()['items'];

        $userArray = array_merge($this->attributes, ['merchants' => $merchantArray]);

        return $userArray;
    }

    public function currentMerchant()
    {
        $currentMerchant = (new User\Helper)->getCurrentMerchant($this);

        return $currentMerchant;
    }

    public function ownerMerchant()
    {
        $ownerMerchant = (new User\Helper)->getCurrentMerchant($this);

        return $ownerMerchant;
    }
}
