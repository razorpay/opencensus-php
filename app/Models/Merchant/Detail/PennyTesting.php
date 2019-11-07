<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;
use RZP\Models\FundAccount\Validation\Core as FundAccountValidationCore;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidationEntity;
use RZP\Models\FundAccount\Validation\AccountStatus as FundAccountValidationAccountStatus;

class PennyTesting extends Base\Core
{

    const OCR_MATCH_PERCENTAGE = 'ocr_match_percentage';

    /**
     * Make penny testing attempt
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $fromMerchant
     *
     * @return FundAccountValidationEntity
     * @throws \Throwable
     */
    public function attempt(Entity $merchantDetails, Merchant\Entity $fromMerchant)
    {
        $input = $this->getFundAccountPayload($merchantDetails);

        $fundAccountValidation = (new FundAccountValidationCore())->create($input, $fromMerchant);

        $this->trace->info(TraceCode::MERCHANT_PENNY_TESTING_ATTEMPT, $fundAccountValidation->toArrayPublic());

        return $fundAccountValidation;
    }

    /**
     * Generates payload for fund account validation
     *
     * @param Entity $merchantDetails
     *
     * @return array
     */
    private function getFundAccountPayload(Entity $merchantDetails): array
    {
        $ifsc = $merchantDetails->getIfsc();

        $bankAccountNumber = $merchantDetails->getBankAccountNumber();

        $bankAccountName = $merchantDetails->getBankAccountName();

        $input = [
            FundAccountValidation::FUND_ACCOUNT => [
                FundAccountEntity::ACCOUNT_TYPE => FundAccountEntity::BANK_ACCOUNT,
                FundAccountEntity::DETAILS      => [
                    BankAccountEntity::ACCOUNT_NUMBER => $bankAccountNumber,
                    BankAccountEntity::NAME           => $bankAccountName,
                    BankAccountEntity::IFSC           => $ifsc,
                ],
            ],
            FundAccountValidation::CURRENCY     => 'INR',
            FundAccountValidation::NOTES        => [
                Entity::MERCHANT_ID => $merchantDetails->getMerchantId(),
            ],
        ];

        return $input;
    }

    /**
     * @param FundAccountValidationEntity $validationEntity
     *
     * @throws \Throwable
     */
    public function handlePennyTestingEvent(FundAccountValidation $validationEntity)
    {
        $input = $this->getBankAccountVerificationPayload($validationEntity);

        (new Validator())->validateInput("pennyTestingEventPayload", $input);

        $this->trace->info(TraceCode::MERCHANT_PENNY_TESTING_EVENT_PAYLOAD, [
            "data" => $input
        ]);

        $merchant = $this->repo->merchant->findOrFailPublic($input[Constants::MERCHANT_ID]);

        $merchantDetails = $merchant->merchantDetail;

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $merchantDetails, $input) {

            $this->updateBankDetailVerificationStatus($input, $merchantDetails);

            $this->updateMerchantContext($merchantDetails, $merchant);

            $this->repo->merchant_detail->saveOrFail($merchantDetails);

            $this->repo->merchant->saveOrFail($merchant);
        });
    }

    /**
     * @param array $input
     * @param       $merchantDetails
     */
    protected function updateBankDetailVerificationStatus(array $input, Entity $merchantDetails): void
    {
        $ocrMatchingPercentage = get_similar_text_percent($merchantDetails->getPromoterPanName(), $input[Constants::REGISTERED_NAME]);

        $bankAccountValidationStatus = BankDetailsVerificationStatus::FAILED;

        if ($this->isValidBankAccount($input[Constants::ACCOUNT_STATUS], $ocrMatchingPercentage) === true)
        {
            $bankAccountValidationStatus = BankDetailsVerificationStatus::VERIFIED;
        }

        $this->trace->info(TraceCode::MERCHANT_BANK_DETAIL_STATUS_AFTER_PENNY_TESTING, [
            Entity::BANK_DETAILS_VERIFICATION_STATUS => $bankAccountValidationStatus,
            self::OCR_MATCH_PERCENTAGE               => $ocrMatchingPercentage
        ]);

        $merchantDetails->setBankDetailsVerificationStatus($bankAccountValidationStatus);
    }

    protected function updateMerchantContext(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        $detailCore = new Core();

        $newActivationStatus = Status::UNDER_REVIEW;

        if (($merchantDetails->isPoaVerified() === true) and
            ($merchantDetails->isBankDetailStatusVerified() === true))
        {
            $newActivationStatus = Status::ACTIVATED;
        }

        //
        // in case of Unregistered business if poa is successfully verified then merchant status will be in under review
        // And in case of penny testing failure also $newActivationStatus will be under review
        //
        if ($newActivationStatus !== $merchantDetails->getActivationStatus())
        {
            $activationStatusData = [
                Entity::ACTIVATION_STATUS => $newActivationStatus
            ];

            $detailCore->updateActivationStatus($merchant, $activationStatusData, $merchant);
        }
    }

    /**
     * Searches merchant_id in notes and account status in results webhook response
     *
     * @param FundAccountValidationEntity $validationEntity
     *
     * @return array
     */
    public function getBankAccountVerificationPayload(FundAccountValidation $validationEntity): array
    {
        $validationEntityNotes = $validationEntity->getNotes();

        $merchantId = null;

        if (isset($validationEntityNotes[Entity::MERCHANT_ID]) === true)
        {
            $merchantId = $validationEntityNotes[Entity::MERCHANT_ID];
        }

        $payload = [
            Constants::MERCHANT_ID     => $merchantId,
            Constants::ACCOUNT_STATUS  => $validationEntity->getAccountStatus(),
            Constants::REGISTERED_NAME => $validationEntity->getRegisteredName() ?? "",
        ];

        return $payload;
    }

    /**
     * @param $accountStatus
     * @param $ocrMatchingPercentage
     *
     * @return bool
     */
    private function isValidBankAccount($accountStatus, $ocrMatchingPercentage): bool
    {
        return ($accountStatus === FundAccountValidationAccountStatus::ACTIVE) and
               ($ocrMatchingPercentage >= BankDetailsVerificationStatus::BANK_DETAIL_VERIFICATION_THRESHOLD);
    }
}
