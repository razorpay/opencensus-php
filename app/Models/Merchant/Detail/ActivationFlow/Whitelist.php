<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail\Entity;

/**
 * Class WhitelistActivationFlow
 *
 * contains activation logic for whitelist activation flow
 * For Example :  Business category => TOURS_AND_TRAVEL , Business SubCategory => ACCOMMODATION
 * fall under whitelist activation flow
 * Detailed Mapping can be found here @Class BusinessCategoryMetaData
 *
 * @package RZP\Models\Merchant\Detail\ActivationFlow
 */
class Whitelist implements ActivationFlowInterface
{
    public function process(Entity $merchantDetails)
    {
        (new Merchant\Activate)->instantlyActivate($merchantDetails->merchant);
    }
}
