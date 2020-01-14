<?php

namespace RZP\Models\Merchant\Detail\InternationalActivationFlow;

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
             (new Partner\Core)->isForceGreylistAccount($this->merchant) === true))
        {
            return true;
        }

        return false;
    }
}
