<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Metric as DetailMetric;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;
use RZP\Models\FundAccount\Validation\Core as FundAccountValidationCore;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidationEntity;
use RZP\Models\FundAccount\Validation\AccountStatus as FundAccountValidationAccountStatus;

class PennyTesting extends Base\Core
{

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

            $this->updateBankDetailVerificationStatus($input, $merchant, $merchantDetails);

            $this->updateMerchantContext($merchantDetails, $merchant);

            $this->repo->merchant_detail->saveOrFail($merchantDetails);

            $this->repo->merchant->saveOrFail($merchant);
        });
    }

    /**
     * Updates bank detail verification status according to fuzzy match results
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     */
    protected function updateBankDetailVerificationStatus(array $input, Merchant\Entity $merchant, Entity $merchantDetails): void
    {
        $fuzzyMatchResults = $this->getFuzzyMatchResults($input, $merchantDetails);

        $bankAccountValidationStatus = $this->getBankDetailVerificationStatus($input, $fuzzyMatchResults);

        $merchantDetails->setBankDetailsVerificationStatus($bankAccountValidationStatus);

        $this->sendPennyTestingAndPushEvent($merchant,
                                            $merchantDetails,
                                            $fuzzyMatchResults,
                                            $input);
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
     * @param $fuzzyMatchResults
     *
     * @return bool
     */
    private function isValidBankAccount($accountStatus, array $fuzzyMatchResults): bool
    {
        $fuzzyMatchPercentWithPan         = $fuzzyMatchResults[Constants::FUZZY_MATCH_PERCENTAGE_WITH_PAN];
        $fuzzyMatchPercentWithBankAccount = $fuzzyMatchResults[Constants::FUZZY_MATCH_PERCENTAGE_WITH_BANK_ACCOUNT_NAME];

        $this->trace->info(TraceCode::MERCHANT_BANK_DETAIL_STATUS_AFTER_PENNY_TESTING,
                           [
                               Constants::ACCOUNT_STATUS                                => $accountStatus,
                               Constants::FUZZY_MATCH_PERCENTAGE_WITH_PAN               => $fuzzyMatchPercentWithPan,
                               Constants::FUZZY_MATCH_PERCENTAGE_WITH_BANK_ACCOUNT_NAME => $fuzzyMatchPercentWithBankAccount
                           ]);

        return ($accountStatus === FundAccountValidationAccountStatus::ACTIVE) and
               ($fuzzyMatchPercentWithPan >= BankDetailsVerificationStatus::BANK_DETAIL_VERIFICATION_THRESHOLD_FOR_PAN) and
               ($fuzzyMatchPercentWithBankAccount >= BankDetailsVerificationStatus::BANK_DETAIL_VERIFICATION_THRESHOLD_FOR_BANK_ACCOUNT);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     * @param array           $fuzzyMatchResults
     * @param array           $input
     */
    protected function sendPennyTestingAndPushEvent(Merchant\Entity $merchant,
                                                    Entity $merchantDetails,
                                                    array $fuzzyMatchResults,
                                                    array $input)
    {
        $eventAttributesForKycModification = [
            Constants::POA_STATUS                       => $merchantDetails->getPoaVerificationStatus(),
            Constants::BANK_DETAILS_VERIFICATION_STATUS => $merchantDetails->getBankDetailsVerificationStatus(),
        ];

        $eventPropertiesForPennyTesting = [
            Entity::PROMOTER_PAN_NAME                                => $merchantDetails->getPromoterPanName(),
            Entity::BANK_ACCOUNT_NAME                                => $merchantDetails->getBankAccountName(),
            Constants::BANK_DETAILS_VERIFICATION_STATUS              => $merchantDetails->getBankDetailsVerificationStatus(),
            Constants::ACCOUNT_STATUS                                => $input[Constants::ACCOUNT_STATUS] ?? '',
            Constants::REGISTERED_NAME                               => $input[Constants::REGISTERED_NAME] ?? '',
            Constants::FUZZY_MATCH_PERCENTAGE_WITH_PAN               => $fuzzyMatchResults[Constants::FUZZY_MATCH_PERCENTAGE_WITH_PAN],
            Constants::FUZZY_MATCH_PERCENTAGE_WITH_BANK_ACCOUNT_NAME => $fuzzyMatchResults[Constants::FUZZY_MATCH_PERCENTAGE_WITH_BANK_ACCOUNT_NAME],
            Constants::BANK_VERIFICATION_THRESHOLD_FOR_PAN           => BankDetailsVerificationStatus::BANK_DETAIL_VERIFICATION_THRESHOLD_FOR_PAN,
            Constants::BANK_VERIFICATION_THRESHOLD_FOR_BANK_ACCOUNT  => BankDetailsVerificationStatus::BANK_DETAIL_VERIFICATION_THRESHOLD_FOR_BANK_ACCOUNT,
        ];

        $this->trace->count(DetailMetric::UNREGISTERED_PENNY_TESTING_STATUS_TOTAL,
                            [
                                Constants::BANK_DETAILS_VERIFICATION_STATUS => $merchantDetails->getBankDetailsVerificationStatus()
                            ]);

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_SAVE_MODIFICATIONS_SUCCESS, $merchant, null, $eventAttributesForKycModification);

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_PENNY_TESTING_SUCCESS_RATE, $merchant, null, $eventPropertiesForPennyTesting);
    }

    /**
     * @param array  $input
     * @param Entity $merchantDetails
     *
     * @return array
     */
    protected function getFuzzyMatchResults(array $input, Entity $merchantDetails): array
    {
        $fuzzyMatchPercentWithPan = get_similar_text_percent($merchantDetails->getPromoterPanName(),
                                                             $input[Constants::REGISTERED_NAME]);

        $fuzzyMatchPercentWithBankAccount = get_similar_text_percent($merchantDetails->getPromoterPanName(),
                                                                     $merchantDetails->getBankAccountName());

        $fuzzyMatchResults = [
            Constants::FUZZY_MATCH_PERCENTAGE_WITH_BANK_ACCOUNT_NAME => $fuzzyMatchPercentWithBankAccount,
            Constants::FUZZY_MATCH_PERCENTAGE_WITH_PAN               => $fuzzyMatchPercentWithPan,
            Entity::PROMOTER_PAN_NAME                                => $merchantDetails->getPromoterPanName(),
            Entity::BANK_ACCOUNT_NAME                                => $merchantDetails->getBankAccountName(),
        ];

        return $fuzzyMatchResults;
    }

    /**
     * @param array $input
     * @param       $fuzzyMatchResults
     *
     * @return string
     */
    protected function getBankDetailVerificationStatus(array $input, $fuzzyMatchResults): string
    {
        $bankAccountValidationStatus = BankDetailsVerificationStatus::FAILED;

        $isValidBankAccount = $this->isValidBankAccount($input[Constants::ACCOUNT_STATUS],
                                                        $fuzzyMatchResults);

        if ($isValidBankAccount === true)
        {
            $bankAccountValidationStatus = BankDetailsVerificationStatus::VERIFIED;
        }

        return $bankAccountValidationStatus;
    }
}
