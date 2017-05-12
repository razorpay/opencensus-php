<?php

namespace App\User;

use Session;
use App\Providers\GenericUser;

class Helper
{
    public function getCurrentMerchant(GenericUser $user)
    {
        $sessionMerchantId = Session::get('current_merchant_id');

        $currentMerchant = null;

        if ($sessionMerchantId !== null)
        {
            $currentMerchant = $user->merchants->where('id', $sessionMerchantId)
                                               ->first();

            if ($currentMerchant === null)
            {
                $currentMerchant = $user->merchants->first();

                Session::put('current_merchant_id', $currentMerchant->id);
            }
        }
        else
        {
            $currentMerchant = $user->merchants->first();

            Session::put('current_merchant_id', $currentMerchant->id);
        }

        return $currentMerchant;
    }

    public function getOwnerMerchant(GenericUser $user)
    {
        $currentMerchant = $this->getCurrentMerchant($user);

        $ownerMerchant = $user->merchants->where('id', $currentMerchant->id)
                                         ->where('role', 'owner')
                                         ->first();

        return $ownerMerchant;
    }
}
