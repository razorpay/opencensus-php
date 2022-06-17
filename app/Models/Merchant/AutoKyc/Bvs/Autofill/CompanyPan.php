<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Autofill;

use App;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Store;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\CompanyPan as CompanyPanDispatcher;

class CompanyPan extends Base
{
    public function __construct(Merchant\Entity $merchant, Merchant\Detail\Entity $merchantDetails)
    {
        parent::__construct($merchant, $merchantDetails);

        $this->attemptsCountCacheKey = Store\ConfigKey::GET_COMPANY_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT;

        $this->maxAttempt = DetailConstants::GET_COMPANY_PAN_DETAILS_MAX_ATTEMPT;

        $this->dispatcher = CompanyPanDispatcher::class;
    }

    protected function updateMerchantContext(string $name)
    {
        $this->merchantDetails->setBusinessNameSuggested($name);

        $this->merchantDetails->setCompanyPanVerificationStatus();
    }
}
