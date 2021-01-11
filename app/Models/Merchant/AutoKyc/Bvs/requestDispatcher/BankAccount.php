<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Detail\PennyTesting as DetailsPennyTesting;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class BankAccount extends Base
{

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        return ($this->merchantDetails->getBankDetailsVerificationStatus() === BvsValidationConstants::PENDING);
    }

    /**
     * @return array
     */
    public function getRequestPayload(): array
    {
        $accountHolderNames = (new DetailsPennyTesting())->getAllowedMerchantAttributesDetails($this->merchantDetails);

        $accountHolderNames = array_values($accountHolderNames);

        $configName = $this->merchantDetails->isUnregisteredBusiness() ?
            Constant::BANK_ACCOUNT_UNREG :
            Constant::BANK_ACCOUNT_REG;

        return [
            Constant::ARTEFACT_TYPE   => Constant::BANK_ACCOUNT,
            Constant::CONFIG_NAME     => $configName,
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::DETAILS         => [
                Constant::ACCOUNT_NUMBER       => $this->merchantDetails->getBankAccountNumber(),
                Constant::IFSC                 => $this->merchantDetails->getBankBranchIfsc(),
                Constant::BENEFICIARY_NAME     => $this->merchantDetails->getBankAccountName(),
                Constant::ACCOUNT_HOLDER_NAMES => $accountHolderNames,
            ],
        ];
    }

    public function performPostProcessOperation(): void
    {
        $this->merchantDetails->setBankDetailsVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
