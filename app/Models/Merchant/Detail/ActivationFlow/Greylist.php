<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use Mail;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Models\Merchant\Entity;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Activate;
use RZP\Services\MerchantRiskClient;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Mail\Merchant\RazorpayX\RequestKyc;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

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

        $this->handleFlowForRazorpayx($merchant);
    }

    public function sendKycRequestEmail(Entity $merchant)
    {
        $product = $this->app['basicauth']->getRequestOriginProduct();

        if ($product === Product::BANKING)
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

    protected function handleFlowForRazorpayx(Entity $merchant)
    {
        $this->sendKycRequestEmail($merchant);

        //
        // Calling this here for onboarding merchant onto test mode
        // the code inside handles for not onboarding merchant on live mode
        //
        (new Activate)->activateBusinessBankingIfApplicable($merchant);
    }
}
