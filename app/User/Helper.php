<?php

namespace App\User;

use Session;
use App\Http\ApiUrl;
use App\RZP\PublicCollection;
use App\Providers\GenericUser;
use App\Merchant\GenericMerchant;

class Helper
{
    public function getCurrentMerchant(GenericUser $user)
    {
        $sessionMerchantId = Session::get('current_merchant_id');
        $currentMerchant = null;

        $isBankingRequest = ApiUrl::isBankingOriginRequest();

        // Primary role
        $productRole = 'role';
        // Banking role
        $switchProductRole = 'banking_role';

        if ($isBankingRequest === true)
        {
            // Banking role
            $productRole = 'banking_role';
            // Primary role
            $switchProductRole = 'role';
        }

        if ($sessionMerchantId !== null)
        {
            // Check if user is associated to a merchant on given product
            $currentMerchant = $user->merchants->where('id', $sessionMerchantId)
                                               ->filter(function ($item) use ($productRole)
                                                 {
                                                     return ($item->$productRole !== null);
                                                 })
                                               ->first();

            if ($currentMerchant === null)
            {
                // Update the user and check if he accepted any new invites after logging in.
                list($error, $updatedUser) = (new Service())->getUserFromApi($user->id);

                if (empty($error) === true)
                {
                    $currentMerchant = $updatedUser->merchants->where('id', $sessionMerchantId)
                                                              ->filter(function ($item) use ($productRole)
                                                                {
                                                                    return ($item->$productRole !== null);
                                                                })
                                                              ->first();
                }
            }
        }

        if ($currentMerchant === null)
        {
            // Check if user is associated to a merchant on given product
            $currentMerchant = $user->merchants->filter(function ($item) use ($productRole)
                                                 {
                                                     return ($item->$productRole !== null);
                                                 })
                                               ->first();

            // If current merchant is still null, that means that the user is not linked to a merchant on given product
            // We'll allow only owner on switch product to login
            if ($currentMerchant === null)
            {
                $currentMerchant = $user->merchants->filter(function ($item) use ($switchProductRole)
                                                     {
                                                         return ($item->$switchProductRole === 'owner');
                                                     })
                                                   ->first();
            }
        }

        if ($currentMerchant !== null)
        {
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
                $merchant['activated'] = (int) ($merchant['activated'] ?? 0);

                $merchants->push(new GenericMerchant($merchant));
            }
        }

        $genericUser = new GenericUser($user);

        $genericUser->merchants = $merchants;

        return $genericUser;
    }

    public function isOwner($user)
    {
        return $user->role === 'owner';
    }

    public function getUserDevice($browser): string
    {
        if ($browser->isMobile())
        {
            return 'Mobile';
        }
        else if ($browser->isTablet())
        {
            return 'Tablet';
        }
        else
        {
            return 'Web';
        }
    }
}
