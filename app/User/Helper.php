<?php

namespace App\User;

use Session;
use App\RZP\PublicCollection;
use App\Providers\GenericUser;
use App\Merchant\GenericMerchant;

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

    public function createGenericUsers(array $users)
    {
        $genericUsers = new PublicCollection;

        foreach ($users as $user)
        {
            $genericUsers->push($this->createdGenericUser($user));
        }

        return $genericUsers;
    }

    public function createdGenericUser(array $user)
    {
        $merchants = new PublicCollection;

        if (isset($user['merchants']) === true)
        {
            foreach ($user['merchants'] as $merchant)
            {
                $merchants->push(new GenericMerchant($merchant));
            }
        }

        $genericUser = new GenericUser($user);

        $genericUser->merchants = $merchants;

        return $genericUser;
    }
}
