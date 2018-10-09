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
        // If a merchant does not have website or app, we would need to activate them
        // only with PLs, Invoices and should not get API keys in live mode. Merchant's has_key_access
        // should be set to true only if one submits website details, there by will be able to
        // generate/access keys.

        (new Merchant\Detail\Core)->checkAndMarkHasKeyAccess($merchantDetails);
    }
}
