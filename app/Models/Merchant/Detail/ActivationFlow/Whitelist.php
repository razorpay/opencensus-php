<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
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
class Whitelist extends Base implements ActivationFlowInterface
{
    /**
     * @param Entity $merchantDetails
     */
    public function process(Entity $merchantDetails)
    {
        $this->trace->info(TraceCode::MERCHANT_PROCESS_WHITELIST_ACTIVATION);

        //
        // The merchant entity returned by the '$merchantDetails->merchant' relation gets reloaded here.
        // The function autoUpdateMerchantCategoryDetailsIfApplicable() updates a few merchant attributes.
        // Since the $merchantDetails variable in saveInstantActivationDetails() is defined before updating these
        // merchant entity attributes, these values will not be reflected in the relation unless explicitly reloaded.
        //
        $merchantDetails->load('merchant');

        $merchant = $merchantDetails->merchant;

        (new Merchant\Activate)->instantlyActivate($merchant, $merchantDetails);
    }

    /**
     * validation specific to whitelist activation flow
     *
     * @param Entity $merchantDetails
     */
    public function validateFullActivationForm(Entity $merchantDetails)
    {
        return;
    }
}
