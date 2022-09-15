<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Trace\TraceCode;

class BankAccount extends DefaultStatusUpdater
{
    /**
     * BankAccount constructor.
     *
     * @param MerchantEntity $merchant
     * @param Detail\Entity  $merchantDetail
     * @param Entity         $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetail,
                                Entity $consumedValidation)
    {
        parent::__construct($merchant,$merchantDetail, Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS, $consumedValidation);
    }

    public function getUpdatedActivationStatus(): string
    {
        return (new Detail\Core())->getApplicableActivationStatus($this->merchantDetails);
    }

    public function updateValidationStatus(): void
    {
        $this->processUpdateValidationStatus();

        if ($this->merchant->isNoDocOnboardingEnabled() === true)
        {
            $this->bankAccountValidationForNoDocOnboarding();
        }
        $this->postUpdateValidationStatus();
    }

    protected function bankAccountValidationForNoDocOnboarding()
    {
        $store             = new StoreCore();
        $merchantDetailCore = (new MerchantDetailCore());
        $data         = $store->fetchValuesFromStore($this->merchant->getId(), ConfigKey::ONBOARDING_NAMESPACE,
                                                          [ConfigKey::NO_DOC_ONBOARDING_INFO], StoreConstants::INTERNAL);
        $verificationStatus = $this->merchantDetails->getBankDetailsVerificationStatus();

        $noDocData = $data[ConfigKey::NO_DOC_ONBOARDING_INFO]??null;
        if (empty($noDocData) === true)
        {
            $this->postUpdateValidationStatus();

            return;
        }

        if ($verificationStatus === DEConstants::VERIFIED)
        {
            $noDocData[DEConstants::VERIFICATION][DetailEntity::BANK_ACCOUNT_NUMBER][DEConstants::STATUS] = Detail\RetryStatus::PASSED;

            $merchantDetailCore->updateNoDocOnboardingConfig($noDocData, $store);

            $this->postUpdateValidationStatus();
        }
        else
        {
            $noDocData[DEConstants::VERIFICATION][DetailEntity::BANK_ACCOUNT_NUMBER][DEConstants::RETRY_COUNT] = $noDocData[DEConstants::VERIFICATION][DetailEntity::BANK_ACCOUNT_NUMBER][DEConstants::RETRY_COUNT] + 1;

            if ($noDocData[DEConstants::VERIFICATION][DetailEntity::BANK_ACCOUNT_NUMBER][DEConstants::RETRY_COUNT] > 1)
            {
                $noDocData[DEConstants::VERIFICATION][DetailEntity::BANK_ACCOUNT_NUMBER][DEConstants::STATUS] = Detail\RetryStatus::FAILED;
            }
            $merchantDetailCore->updateNoDocOnboardingConfig($noDocData, $store);
        }

        $this->trace->info(
            TraceCode::BANK_ACCOUNT_RETRY_STATUS,
            [
                'merchant_id'      => $this->merchant->getId(),
                'noDocData'        => $noDocData,
                'bank_account_verification_status' =>  $verificationStatus
            ]
        );

    }
}
