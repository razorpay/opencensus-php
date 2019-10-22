<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use Mail;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Mail\Merchant\RazorpayX\RequestKyc;

/**
 * Class GreylistActivationFlow
 *
 * contains activation logic for greylist activation flow
 * For Example :  Business category => NOT_FOR_PROFIT , Business SubCategory => CHARITY
 * fall under greylist activation flow
 * Detailed Mapping can be found here @Class BusinessSubCategoryMetaData
 *
 * @package RZP\Models\Merchant\Detail\ActivationFlow
 */
class Greylist extends Base implements ActivationFlowInterface
{
    /**
     * In greylist  activation flow , merchant won't get activated from basic activation form
     * Full activation form need to be filled for activation
     *
     * @param Entity $merchantDetails
     */
    public function process(Entity $merchantDetails)
    {
        $this->trace->info(TraceCode::MERCHANT_PROCESS_GREYLIST_ACTIVATION);

        $this->sendKycRequestEmail($merchantDetails);

        return;
    }

    public function sendKycRequestEmail(Entity $merchantDetails)
    {
        Mail::queue(
            new RequestKyc($merchantDetails->getContactName(),
                           $merchantDetails->getContactEmail())
        );
    }

    /**
     * Validation specific to the greylist activation flow
     *
     * @param Entity $merchantDetails
     */
    public function validateFullActivationForm(Entity $merchantDetails)
    {
        return;
    }
}
