<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Autofill;

use App;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Store;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\PersonalPan as PersonalPanDispatcher;

class PersonalPan extends Base
{
    public function __construct(Merchant\Entity $merchant, Merchant\Detail\Entity $merchantDetails)
    {
        parent::__construct($merchant, $merchantDetails);

        $this->attemptsCountCacheKey = Store\ConfigKey::GET_PROMOTER_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT;

        $this->maxAttempt = DetailConstants::GET_PROMOTER_PAN_DETAILS_MAX_ATTEMPT;

        $this->dispatcher = PersonalPanDispatcher::class;
    }

    protected function updateMerchantContext(string $name)
    {
        $this->merchantDetails->setPromoterPanNameSuggested($name);

        $this->merchantDetails->setPoiVerificationStatus();
    }
}
