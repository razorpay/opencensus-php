<?php

namespace RZP\Models\Merchant\Detail\InternationalActivationFlow;

use App;

use RZP\Models\Partner;
use RZP\Models\Merchant\Detail;

/**
 * Class GreylistActivationFlow
 *
 * Contains activation logic for greylist international activation flow
 * For Example :  Business category => NOT_FOR_PROFIT , Business SubCategory => CHARITY
 * fall under greylist  international activation flow
 * Detailed Mapping can be found here @Class BusinessSubCategoryMetaData
 *
 * @package RZP\Models\Merchant\Detail\InternationalActivationFlow
 */
class Greylist extends Base implements ActivationFlowInterface
{

    public function shouldActivateInternational(): bool
    {
        if ((($this->merchantDetail->getActivationStatus() === Detail\Status::ACTIVATED) and
             (new Partner\Core)->isForceGreylistMerchant($this->merchant, null) === true))
        {
            return true;
        }

        $app = App::getFacadeRoot();

        // If the route is for a workflow approval of international enablement
        if ($app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            return true;
        }

        return false;
    }
}
