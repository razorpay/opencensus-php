<?php

namespace RZP\Models\GiftCards;

use RZP\Exception;
use RZP\Models\Emi\AffordabilityProvider;

class GiftCardsProviders
{

    const RAZORPAYGIFTCARD = 'razorpay_gift_card';
    const GIFTCARDS = 'gift_cards';


    protected static $providers = [
        self::RAZORPAYGIFTCARD
    ];


    public static function getAllGiftCardsProviders()
    {
        return self::$providers;
    }

    public static function getEnabledProviders($all_addon_methods, $addon_methods): array
    {

        $method = self::GIFTCARDS;

        $consolidatedGiftCardProviders = [];

                foreach ($all_addon_methods[$method] as $provider)
                {
                    if(isset($addon_methods[$method]) === true)
                    {
                        if(isset($addon_methods[$method][$provider]) === true && $addon_methods[$method][$provider] === 1)
                        {
                            $consolidatedGiftCardProviders[$provider] = 1;
                        }
                        else
                        {
                            $consolidatedGiftCardProviders[$provider] = 0;
                        }
                    }
                    else
                    {
                        $consolidatedGiftCardProviders[$provider] = 0;
                    }
                }

                return $consolidatedGiftCardProviders;
    }
}
