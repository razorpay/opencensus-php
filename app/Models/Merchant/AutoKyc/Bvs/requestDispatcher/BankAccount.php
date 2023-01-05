<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Trace\TraceCode;
use RZP\Models\Feature;
use Illuminate\Support\Facades\Bus;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Detail\PennyTesting as DetailsPennyTesting;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Store\Constants as StoreConstants;

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

        return [
            Constant::ARTEFACT_TYPE   => Constant::BANK_ACCOUNT,
            Constant::CONFIG_NAME     => $this->getConfigName(),
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::DETAILS         => [
                Constant::ACCOUNT_NUMBER       => $this->merchantDetails->getBankAccountNumber(),
                Constant::IFSC                 => $this->merchantDetails->getBankBranchIfsc(),
                Constant::BENEFICIARY_NAME     => $this->merchantDetails->getBankAccountName(),
                Constant::ACCOUNT_HOLDER_NAMES => $accountHolderNames,
            ],
        ];
    }

    public function getVerificationResponseKey($validation)
    {
        $errorCode              = $validation->getErrorCode();

        $errorDescription       = $validation->getErrorDescription();

        $errorDescriptionCode   = substr($errorDescription,0,4);

        return Constant::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . $errorCode . $errorDescriptionCode;
    }

    public function getConfigName()
    {
        if ($this->merchant->isNoDocOnboardingEnabled() === true)
        {
            switch ($this->merchantDetails->getBusinessType())
            {
                case BusinessType::PROPRIETORSHIP:
                case BusinessType::NOT_YET_REGISTERED:
                    return Constant::BANK_ACCOUNT_WITH_BUSINESS_OR_PROMOTER_PAN;

                default:
                    return Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN;
            }
        }

        if ($this->merchantDetails->isUnregisteredBusiness() === true)
        {
            return Constant::BANK_ACCOUNT_WITH_PERSONAL_PAN;
        }

        switch ($this->merchantDetails->getBusinessType())
        {
            case BusinessType::PRIVATE_LIMITED:
            case BusinessType::PUBLIC_LIMITED:
            case BusinessType::LLP:
            case BusinessType::PARTNERSHIP:
            case BusinessType::NGO:
            case BusinessType::TRUST:
            case BusinessType::SOCIETY:

                return Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN;

            default:
                return Constant::BANK_ACCOUNT_WITH_BUSINESS_OR_PROMOTER_PAN;
        }
    }

    public function performPostProcessOperation(BvsValidation\Entity $bvsValidation): void
    {
        $this->incrementBankAccountVerification();

        if ($this->merchantDetails->getBankDetailsVerificationStatus() === BvsValidationConstants::PENDING)
        {
            $this->merchantDetails->setBankDetailsVerificationStatus(BvsValidationConstants::INITIATED);
        }
    }

    private function incrementBankAccountVerification()
    {

        $core=new StoreCore();

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];

        $data = (new StoreCore())->fetchValuesFromStore($this->merchant->getId(),
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $verificationAttempts = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $data = [
            StoreConstants::NAMESPACE                          => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT => $verificationAttempts + 1
        ];

        $data = $core->updateMerchantStore($this->merchant->getId(),
                                                       $data,
                                                       StoreConstants::INTERNAL);

    }
}
