<?php

namespace App\Providers;

use Session;
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
        $currentMerchant = (new Helper)->getCurrentMerchant($this);

        return $currentMerchant;
    }

    public function ownerMerchant()
    {
        $ownerMerchant = (new Helper)->getOwnerMerchant($this);

        return $ownerMerchant;
    }
}
