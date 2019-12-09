<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use Mail;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Models\Merchant\Entity;
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
     * @param Entity $merchant
     */
    public function process(Entity $merchant)
    {
        $this->trace->info(TraceCode::MERCHANT_PROCESS_GREYLIST_ACTIVATION);

        $this->sendKycRequestEmail($merchant);

        return;
    }

    public function sendKycRequestEmail(Entity $merchant)
    {
        $product = $this->auth->getRequestOriginProduct();

        if($product === Product::BANKING)
        {
            Mail::queue(new RequestKyc($merchant->getEntityName(), $merchant->getEmail()));
        }
    }

    /**
     * Validation specific to the greylist activation flow
     *
     * @param Entity $merchant
     */
    public function validateFullActivationForm(Entity $merchant)
    {
        return;
    }
}
