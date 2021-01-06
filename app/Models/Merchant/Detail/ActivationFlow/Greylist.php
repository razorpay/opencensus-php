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

        $this->handleFlowForImpersonatedMerchant($merchant);
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

    private function handleFlowForImpersonatedMerchant(Entity $merchant)
    {
        $isDedupeEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::DEDUPE_FUNCTIONALITY);

        if ($isDedupeEnabled === false)
        {
            return;
        }

        $riskFactor = (new MerchantRiskClient)->getMerchantRiskFactor($merchant);

        if (isset($riskFactor['impersonated']) === true and
            $riskFactor['impersonated'] === true)
        {
            $merchantDetails = $merchant->merchantDetail;
            $oldMerchantDetails = clone $merchantDetails;
            $newMerchantDetails = clone $merchantDetails;
            $newMerchantDetails->setActivationFlow(ActivationFlow::WHITELIST);

            $this->app['workflow']
                ->setPermission(Permission\Name::IMPERSONATING_MERCHANT_DEDUPE)
                ->setRouteName(DetailConstants::ACTIVATION_ROUTE_NAME)
                ->setController(DetailConstants::ACTIVATION_CONTROLLER)
                ->setWorkflowMaker($merchant)
                ->setWorkflowMakerType(MakerType::MERCHANT)
                ->setRouteParams([DetailEntity::ID => $merchant->getId()])
                ->setInput([])
                ->setEntity($merchant->merchantDetail->getEntity())
                ->setOriginal($oldMerchantDetails)
                ->setDirty($newMerchantDetails);

            try {
                $this->app['workflow']->handle();
            }
            catch(Exception\EarlyWorkflowResponse $e)
            {
                // Catching exception because we do not want to abort the code flow
                $workflowActionData = json_decode($e->getMessage(), true);
                $this->app['workflow']->saveActionIfTransactionFailed($workflowActionData);
            }
        }
    }
}
