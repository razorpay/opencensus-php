<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity;

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
     * @param Entity $merchant
     *
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\LogicException
     * @throws \Throwable
     */
    public function process(Entity $merchant)
    {
        $this->trace->info(TraceCode::MERCHANT_PROCESS_WHITELIST_ACTIVATION);

        $merchantDetails = $merchant->merchantDetail;

        (new Merchant\Activate)->instantlyActivate($merchant, $merchantDetails);
    }

    /**
     * validation specific to whitelist activation flow
     *
     * @param Entity $merchant
     */
    public function validateFullActivationForm(Entity $merchant)
    {
        return;
    }
}
