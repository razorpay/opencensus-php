<?php

namespace RZP\Models\Merchant\Detail;

use Throwable;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\lib\FuzzyMatcher;
use RZP\Models\BankAccount;
use RZP\Exception\LogicException;
use RZP\Jobs\UpdateMerchantContext;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Notifications\Onboarding\Events;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Detail\Metric as DetailMetric;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;
use RZP\Models\FundAccount\Validation\Core as FundAccountValidationCore;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidationEntity;
use RZP\Models\FundAccount\Validation\AccountStatus as FundAccountValidationAccountStatus;

class PennyTesting extends Base\Core
{
    protected $cache;

    protected $mutex;

    protected $bankAccount;

    public function __construct()
    {
        parent::__construct();

        $this->cache = $this->app['cache'];

        $this->mutex = $this->app['api.mutex'];
    }

    public function setBankAccount($bankAccount)
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    /**
     * Make penny testing attempt
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $fromMerchant
     *
     * @return FundAccountValidationEntity
     * @throws Throwable
     */
    public function attempt(Entity $merchantDetails, Merchant\Entity $fromMerchant, $reason = Constants::PENNY_TESTING_REASON_ONBOARDING)
    {
        $getFundAccountPayloadFunction = 'getFundAccountPayloadFor' . studly_case($reason);

        $input = $this->$getFundAccountPayloadFunction($merchantDetails);

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
    private function getFundAccountPayloadForOnboarding(Entity $merchantDetails): array
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
                Entity::MERCHANT_ID                 => $merchantDetails->getMerchantId(),
                Constants::PENNY_TESTING_REASON     => Constants::PENNY_TESTING_REASON_ONBOARDING,
            ],
        ];

        return $input;
    }

    protected function getFundAccountPayloadForBankAccountUpdate(Entity $merchantDetails)
    {
        $ifsc = $this->bankAccount->getIfscCode();

        $bankAccountNumber = $this->bankAccount->getAccountNumber();

        $bankAccountName = $this->bankAccount->getBeneficiaryName();

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
                Entity::MERCHANT_ID                 => $merchantDetails->getMerchantId(),
                Constants::PENNY_TESTING_REASON     => Constants::PENNY_TESTING_REASON_BANK_ACCOUNT_UPDATE,
            ],
        ];

        return $input;
    }

    /**
     * @param FundAccountValidationEntity $validationEntity
     *
     * @throws Throwable
     */
    public function handlePennyTestingEvent(FundAccountValidation $validationEntity)
    {
        $input = $this->getBankAccountVerificationPayload($validationEntity);

        (new Validator())->validateInput("pennyTestingEventPayload", $input);

        $this->trace->info(TraceCode::MERCHANT_PENNY_TESTING_EVENT_PAYLOAD, [
            "data" => $input
        ]);

        $merchantId = $input[Constants::MERCHANT_ID];

        $this->mutex->acquireAndRelease(
            $merchantId,
            function() use ($input, $merchantId) {

                $this->repo->transactionOnLiveAndTest(function() use ($input, $merchantId) {

                    [$merchant, $merchantDetails] = (New Merchant\Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

                    $reason = studly_case($input[Constants::PENNY_TESTING_REASON]);

                    $handler = 'handlePennyTestingEventFor' . $reason;

                    return $this->$handler($input, $merchant, $merchantDetails);
                });
            });
    }

    protected function handlePennyTestingEventForOnboarding(array $input, Merchant\Entity $merchant, Entity $merchantDetails)
    {
        $shouldVerifyPennyTestingResult = $this->verifyPennyTestingResults($merchantDetails);

        if ($shouldVerifyPennyTestingResult === true)
        {
            $this->updateAndReturnBankDetailVerificationStatus($input, $merchant, $merchantDetails);

            $isPennyTestingRetryRequired = $this->isPennyTestingRetryRequired($merchantDetails, $input);

            if ($isPennyTestingRetryRequired === true)
            {
                $this->retryPennyTesting($merchantDetails);
            }
            else
            {
                $this->updateMerchantContext($merchantDetails, $merchant);
            }
        }

    }

    protected function handlePennyTestingEventForBankAccountUpdate(array $input, Merchant\Entity $merchant, Entity $merchantDetails)
    {
        $status = $this->updateAndReturnBankDetailVerificationStatus($input, $merchant, $merchantDetails);

        $nameValidationData = $this->validateNameForBankAccount($input, $merchantDetails);

        $isNameMatched = $nameValidationData[Constants::IS_NAME_MATCHED] ?? false;

        $pennyTestAndFuzzyMatchResult = [
            Constants::ACCOUNT_STATUS  => $input[Constants::ACCOUNT_STATUS],
            Constants::REGISTERED_NAME => $input[Constants::REGISTERED_NAME],
            Constants::IS_NAME_MATCHED => $isNameMatched,
        ];

        (new BankAccount\Core)->handlePennyTestingEventForBankAccountUpdate($input, $merchant, $status, $pennyTestAndFuzzyMatchResult);
    }


    /**
     * Updates bank detail verification status according to fuzzy match results
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     */
    protected function updateAndReturnBankDetailVerificationStatus(array $input, Merchant\Entity $merchant, Entity $merchantDetails)
    {
        try
        {
            $nameValidationData = $this->validateNameForBankAccount($input, $merchantDetails);

            $bankAccountValidationStatus = $this->getBankDetailVerificationStatus($input, $nameValidationData);

            $this->setBankDetailsVerificationStatusAndUpdatedAt($merchantDetails, $bankAccountValidationStatus);

            $this->sendPennyTestingAndPushEvent($merchant,
                                                $merchantDetails,
                                                $nameValidationData,
                                                $input);

            $this->repo->merchant->saveOrFail($merchant);
            $this->repo->merchant_detail->saveOrFail($merchantDetails);

            return $bankAccountValidationStatus;
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e);
        }

    }


    /**
     * Updates merchant context depending upon penny testing results .
     *
     * 1. If penny testing is verified then update merchant status to activated or verified depending upon poa and poi
     * status
     * 2. If penny testing is failed then updates merchant status to needs_clarification and ask for cancelled cheque
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     *
     * @throws Throwable
     */
    public function updateMerchantContext(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        $isSystemBasedNeedsClarificationEnabled = (new MerchantCore())->isRazorxExperimentEnable(
            $merchantId,
            RazorxTreatment::SYSTEM_BASED_NEEDS_CLARIFICATION);

        if ($isSystemBasedNeedsClarificationEnabled === true)
        {
            UpdateMerchantContext::dispatch(Mode::LIVE, $merchantId);

            return;
        }

        $detailCore = new Core();

        switch ($merchantDetails->getBankDetailsVerificationStatus())
        {
            case BankDetailsVerificationStatus::VERIFIED:

                $newActivationStatus = $detailCore->getApplicableActivationStatus($merchantDetails);

                break;

            case BankDetailsVerificationStatus::FAILED:
            case BankDetailsVerificationStatus::NOT_MATCHED:
            case BankDetailsVerificationStatus::INCORRECT_DETAILS:

                $newActivationStatus = Status::NEEDS_CLARIFICATION;

                $additionalDetails = [
                    Merchant\Document\Type::CANCELLED_CHEQUE => [[
                        Merchant\Constants::REASON_TYPE => Merchant\Constants::PREDEFINED_REASON_TYPE,
                        Merchant\Constants::FIELD_TYPE  => Merchant\Constants::DOCUMENT,
                        Merchant\Constants::REASON_CODE => NeedsClarificationReasonsList::UNABLE_TO_VALIDATE_ACC_NUMBER,
                    ]],
                ];

                $clarificationCore = New Merchant\Detail\NeedsClarification\Core();

                $existingKycClarificationReason = $merchantDetails->getKycClarificationReasons() ?? [];

                $kycClarification = $clarificationCore->mergeKycClarificationReasons(
                    $existingKycClarificationReason,
                    null,
                    $additionalDetails);

                $merchantDetails->setKycClarificationReasons($kycClarification);

                $unregistered    = BusinessType::isUnregisteredBusiness($merchantDetails->getBusinessType());
                $promoCodeActive = $detailCore->isPromoCodeActive($merchantDetails->getMerchantId());

                if ($unregistered === true && $promoCodeActive === true)
                {
                    $detailCore->sendOnboardingJourneySms(
                        $merchantDetails, SmsTemplates::PROMO_PENNY_TESTING_FAILURE);
                }
                else
                {
                    $isWhatsappEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
                        RazorxTreatment::WHATSAPP_NOTIFICATIONS);

                    if($isWhatsappEnabled === true)
                    {
                        $args = [
                            'merchant'  => $merchant
                        ];
                        (new OnboardingNotificationHandler($args))->sendForEvent(Events::PENNY_TESTING_FAILURE);
                    }
                    else
                    {
                        $detailCore->sendOnboardingJourneySms(
                            $merchantDetails, SmsTemplates::PENNY_TESTING_FAILURE);
                    }
                }

                break;
            default:
                throw  new LogicException("Unhandled bank detail verification status");
        }

        //
        // in case of Unregistered business if poa is successfully verified then merchant status will be in under review
        // And in case of penny testing failure user should be able to upload cancelled check
        //
        if ($newActivationStatus !== $merchantDetails->getActivationStatus())
        {
            $activationStatusData = [
                Entity::ACTIVATION_STATUS => $newActivationStatus
            ];

            $detailCore->updateActivationStatus($merchant, $activationStatusData, $merchant);
        }

        $this->repo->merchant->saveOrFail($merchant);

        $this->repo->merchant_detail->saveOrFail($merchantDetails);
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


        $pennyTestingReason = Constants::PENNY_TESTING_REASON_ONBOARDING;

        if (isset($validationEntityNotes[Constants::PENNY_TESTING_REASON]) === true)
        {
            $pennyTestingReason = $validationEntityNotes[Constants::PENNY_TESTING_REASON];
        }

        $payload = [
            Constants::MERCHANT_ID          => $merchantId,
            Constants::ACCOUNT_STATUS       => $validationEntity->getAccountStatus(),
            Constants::REGISTERED_NAME      => $validationEntity->getRegisteredName() ?? "",
            Constants::PENNY_TESTING_REASON => $pennyTestingReason,
        ];

        return $payload;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     * @param array           $nameValidationData
     * @param array           $input
     */
    protected function sendPennyTestingAndPushEvent(Merchant\Entity $merchant,
                                                    Entity $merchantDetails,
                                                    array $nameValidationData,
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
            Constants::PENNY_TESTING_REASON                          => $input[Constants::PENNY_TESTING_REASON] ?? '',
            Constants::PENNY_TESTING_FUZZY_MATCH_PERCENTAGE_WITH_PAN => $nameValidationData[Constants::PENNY_TESTING_FUZZY_MATCH_PERCENTAGE_WITH_PAN],
            Constants::PENNY_TESTING_FUZZY_MATCH_TYPE_FOR_PAN        => $nameValidationData[Constants::PENNY_TESTING_FUZZY_MATCH_TYPE_FOR_PAN],
            Constants::BANK_VERIFICATION_THRESHOLD_FOR_PAN           => BankDetailsVerificationStatus::BANK_DETAIL_VERIFICATION_THRESHOLD_FOR_PAN,
            Constants::PENNY_TESTING_FUZZY_MATCH_BASE                => $nameValidationData[Constants::PENNY_TESTING_FUZZY_MATCH_BASE] ?? '',
            Constants::PENNY_TESTING_FUZZY_MATCH_ATTRIBUTE_TYPE      => $nameValidationData[Constants::PENNY_TESTING_FUZZY_MATCH_ATTRIBUTE_TYPE] ?? '',
        ];

        $this->trace->count(DetailMetric::PENNY_TESTING_STATUS_TOTAL,
                            [
                                Constants::BANK_DETAILS_VERIFICATION_STATUS => $merchantDetails->getBankDetailsVerificationStatus(),
                                Constants::BUSINESS_TYPE                    => $merchantDetails->getBusinessType() ?? "",
                            ]);

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_SAVE_MODIFICATIONS_SUCCESS, $merchant, null, $eventAttributesForKycModification);

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_PENNY_TESTING_SUCCESS_RATE, $merchant, null, $eventPropertiesForPennyTesting);
    }

    /**
     * @param array $input
     * @param array $nameValidationData
     *
     * @return string
     */
    protected function getBankDetailVerificationStatus(array $input, array $nameValidationData): string
    {
        $accountStatus = $input[Constants::ACCOUNT_STATUS] ?? '';

        $isAccountNameMatched = $nameValidationData[Constants::IS_NAME_MATCHED] ?? false;

        $this->trace->info(TraceCode::MERCHANT_BANK_DETAIL_STATUS_AFTER_PENNY_TESTING,
                           [
                               Constants::ACCOUNT_STATUS  => $accountStatus,
                               Constants::IS_NAME_MATCHED => $isAccountNameMatched,
                           ]);

        //
        // If account status is not active then mark it as incorrect details
        //
        if ($accountStatus !== FundAccountValidationAccountStatus::ACTIVE)
        {
            return BankDetailsVerificationStatus::INCORRECT_DETAILS;
        }

        //
        // If account status is active and name does not match then mark it as not matched
        //
        if ($isAccountNameMatched === false)
        {
            return BankDetailsVerificationStatus::NOT_MATCHED;
        }

        return BankDetailsVerificationStatus::VERIFIED;
    }

    private function validateNameForBankAccount(array $input, Entity $merchantDetails): array
    {
        $fuzzyMatcher = new FuzzyMatcher(BankDetailsVerificationStatus::BANK_DETAIL_VERIFICATION_THRESHOLD_FOR_PAN, FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH);

        $allowedMerchantAttributesDetails = $this->getAllowedMerchantAttributesDetails($merchantDetails);

        $validationData = [];

        foreach ($allowedMerchantAttributesDetails as $attributeType => $attribute)
        {
            $isAttributeMatched = $fuzzyMatcher->isMatch($attribute, $input[Constants::REGISTERED_NAME], $matchPercentage);

            $validationData = [
                Constants::PENNY_TESTING_FUZZY_MATCH_PERCENTAGE_WITH_PAN => $matchPercentage,
                Constants::PENNY_TESTING_FUZZY_MATCH_TYPE_FOR_PAN        => $fuzzyMatcher->getMatchType(),
                Constants::IS_NAME_MATCHED                               => $isAttributeMatched,
                Constants::PENNY_TESTING_FUZZY_MATCH_BASE                => $attribute,
                Constants::PENNY_TESTING_FUZZY_MATCH_ATTRIBUTE_TYPE      => $attributeType
            ];

            if ($isAttributeMatched === true)
            {
                return $validationData;
            }
        }

        return $validationData;
    }

    /**
     * @param Entity $merchantDetails
     * @param array  $input
     *
     * @return bool
     */
    protected function isPennyTestingRetryRequired(Entity $merchantDetails, array $input): bool
    {
        if ((strtolower($input[DetailConstants::REGISTERED_NAME]) === DetailConstants::UNREGISTERED) or
            (strtolower($input[DetailConstants::ACCOUNT_STATUS]) === DetailConstants::INVALID))
        {
            return $this->isPennyTestingAttemptLessThenMaxAttempt($merchantDetails);
        }

        return false;
    }

    /**
     * @param Entity $merchantDetails
     *
     * @return bool
     */
    public function isPennyTestingAttemptLessThenMaxAttempt(Entity $merchantDetails)
    {
        $attemptCount = $this->getPennyTestingAttempts($merchantDetails);

        return $attemptCount < DetailConstants::PENNY_TESTING_MAX_ATTEMPT;
    }

    /**
     * @param Entity $merchantDetails
     *
     * @throws Throwable
     */
    protected function retryPennyTesting(Entity $merchantDetails)
    {
        $this->trace->count(DetailMetric::PENNY_TESTING_RETRY_COUNT,
                            [
                                Constants::BANK_DETAILS_VERIFICATION_STATUS => $merchantDetails->getBankDetailsVerificationStatus(),
                                Constants::BUSINESS_TYPE                    => $merchantDetails->getBusinessType() ?? "",
                            ]);

        $this->trace->info(TraceCode::MERCHANT_PENNY_TESTING_RETRY, [
            Entity::MERCHANT_ID                         => $merchantDetails->getId(),
            Constants::BANK_DETAILS_VERIFICATION_STATUS => $merchantDetails->getBankDetailsVerificationStatus()
        ]);

        $this->triggerPennyTesting($merchantDetails);

        $this->repo->merchant_detail->saveOrFail($merchantDetails);
    }

    /**
     * @param Entity $merchantDetails
     *
     * @throws Throwable
     */
    public function triggerPennyTesting(Entity $merchantDetails, $reason = Constants::PENNY_TESTING_REASON_ONBOARDING): void
    {
        $fromMerchant = $this->repo->merchant->findOrFailPublic(Merchant\Preferences::MID_ONBOARDING_PENNY_TESTING);

        $this->setBankDetailsVerificationStatusAndUpdatedAt($merchantDetails, BankDetailsVerificationStatus::INITIATED);

        $this->trace->count(DetailMetric::PENNY_TESTING_STATUS_TOTAL,
                            [
                                Constants::BUSINESS_TYPE                    => $merchantDetails->getBusinessType() ?? "",
                                Constants::BANK_DETAILS_VERIFICATION_STATUS => $merchantDetails->getBankDetailsVerificationStatus()
                            ]);

        $this->increasePennyTestingAttempt($merchantDetails);

        $fundAccountValidation = $this->attempt($merchantDetails, $fromMerchant, $reason);

        $merchantDetails->setFundAccountValidationId($fundAccountValidation->getId());
    }

    /**
     * if merchant status is already changed to activation or need_clarification,
     * we need not to trigger penny testing
     *
     * @param Entity $merchantDetails
     *
     * @return bool
     */
    public function shouldPerformPennyTesting(Entity $merchantDetails): bool
    {
        $isPennyTestingRequired = in_array($merchantDetails->getActivationStatus(), [Status::ACTIVATED, Status::NEEDS_CLARIFICATION]) === false;

        return $isPennyTestingRequired;
    }

    /**
     * if we need not to trigger penny testing, and bank_details_verification_status is initiated state,
     * just update status as failed.
     *
     * @param Entity $merchantDetails
     *
     * @return bool
     */
    public function verifyPennyTestingResults(Entity $merchantDetails): bool
    {
        $shouldPerformPennyTesting = $this->shouldPerformPennyTesting($merchantDetails);

        if (($shouldPerformPennyTesting == false) and
            ($merchantDetails->getBankDetailsVerificationStatus() === BankDetailsVerificationStatus::INITIATED))
        {
            $this->trace->info(TraceCode::MERCHANT_PENNY_TESTING_NOT_REQUIRED, [
                Constants::ACCOUNT_STATUS                   => $merchantDetails->getActivationStatus(),
                Constants::BANK_DETAILS_VERIFICATION_STATUS => $merchantDetails->getBankDetailsVerificationStatus()
            ]);

            $this->setBankDetailsVerificationStatusAndUpdatedAt($merchantDetails, BankDetailsVerificationStatus::FAILED);

            $this->repo->merchant_detail->saveOrFail($merchantDetails);
        }

        return $shouldPerformPennyTesting;
    }

    /**
     * @param Entity $merchantDetails
     * @param        $bankDetailsVerificationStatus
     */
    public function setBankDetailsVerificationStatusAndUpdatedAt(Entity $merchantDetails, $bankDetailsVerificationStatus)
    {
        $merchantDetails->setBankDetailsVerificationStatus($bankDetailsVerificationStatus);

        $merchantDetails->setPennyTestingUpdatedAt(time());
    }

    /**
     * @param Entity $merchantDetails
     *
     * @throws Throwable
     */
    protected function increasePennyTestingAttempt(Entity $merchantDetails)
    {
        $pennyTestingAttempt = $this->getPennyTestingAttempts($merchantDetails);

        $this->updatePennyTestingAttempts($merchantDetails, $pennyTestingAttempt + 1);
    }

    /**
     * @param string $merchantId
     *
     * @return string
     */
    public function getPennyTestingAttemptRedisKey(string $merchantId): string
    {
        return DetailConstants::PENNY_TESTING_ATTEMPT_COUNT_REDIS_KEY_PREFIX . $merchantId;
    }

    /**
     * @param Entity $merchantDetails
     *
     * @return int
     */
    public function getPennyTestingAttempts(Entity $merchantDetails): int
    {
        $pennyTestingAttemptRedisKey = $this->getPennyTestingAttemptRedisKey($merchantDetails->getId());

        $pennyTestingCount = $this->cache->get($pennyTestingAttemptRedisKey) ?? 0;

        return $pennyTestingCount;
    }

    /**
     * @param Entity $merchantDetails
     * @param int    $pennyTestingAttempt
     *
     * @throws Throwable
     */
    protected function updatePennyTestingAttempts(Entity $merchantDetails, int $pennyTestingAttempt)
    {
        $pennyTestingAttemptRedisKey = $this->getPennyTestingAttemptRedisKey($merchantDetails->getId());

        $this->cache->put($pennyTestingAttemptRedisKey, $pennyTestingAttempt, Constants::PENNY_TESTING_ATTEMPT_COUNT_TTL_IN_SEC);
    }

    /**
     * Returns merchant attributes details that can be matched in case of penny testing
     * Here The Order of Array Matters as BVS verification Rules and Enrichments are configured in such a way that
     * First name should be PROMOTER_PAN_NAME and second should be second be COMPANY_PAN_NAME
     *
     * @param Entity $merchantDetails
     *
     * @return array
     */
    public function getAllowedMerchantAttributesDetails(Entity $merchantDetails): array
    {
        if ($merchantDetails->merchant->isNoDocOnboardingEnabled() === true)
        {
            switch ($merchantDetails->getBusinessType())
            {
                case BusinessType::NOT_YET_REGISTERED:
                case BusinessType::PROPRIETORSHIP:
                    return [
                        Constants::PROMOTER_PAN_NAME => $merchantDetails->getPromoterPanName(),
                        Constants::COMPANY_PAN_NAME => $merchantDetails->getBusinessName()
                    ];

                default:
                    return [
                        Constants::COMPANY_PAN_NAME  => $merchantDetails->getBusinessName()
                    ];
            }
        }

        //
        // Promoter PAN name is not available for linked accounts created
        // without no doc KYC. Hence, sending business name directly.
        //
        if (($merchantDetails->merchant->isLinkedAccount() === true) and
            ($merchantDetails->merchant->parent->isRouteNoDocKycEnabled() === false))
        {
            switch ($merchantDetails->getBusinessType())
            {

                case BusinessType::NOT_YET_REGISTERED:
                case BusinessType::INDIVIDUAL:
                {
                    return [
                        Constants::COMPANY_PAN_NAME  => $merchantDetails->getBankAccountName()
                    ];
                }
                default:
                {
                    return [
                        Constants::COMPANY_PAN_NAME  => $merchantDetails->getBusinessName()
                    ];
                }
            }
        }
        else if (($merchantDetails->merchant->isLinkedAccount() === true) and
                 ($merchantDetails->merchant->parent->isRouteNoDocKycEnabled() === true))
        {
            switch ($merchantDetails->getBusinessType())
            {
                case BusinessType::NOT_YET_REGISTERED:
                case BusinessType::INDIVIDUAL:
                {
                    return [
                        Constants::PROMOTER_PAN_NAME => $merchantDetails->getPromoterPanName(),
                    ];
                }

                case BusinessType::PUBLIC_LIMITED:
                case BusinessType::PRIVATE_LIMITED:
                case BusinessType::LLP:
                case BusinessType::PARTNERSHIP:
                case BusinessType::TRUST:
                case BusinessType::NGO:
                case BusinessType::SOCIETY:
                {
                    return [
                        Constants::COMPANY_PAN_NAME => $merchantDetails->getBusinessName(),
                    ];
                }

                case BusinessType::PROPRIETORSHIP:
                {
                    return [
                        Constants::PROMOTER_PAN_NAME    => $merchantDetails->getPromoterPanName(),
                        Constants::COMPANY_PAN_NAME     => $merchantDetails->getBusinessName(),
                    ];
                }

                default :
                {
                    throw new LogicException('Invalid business type for linked account creation.');
                }
            }
        }

        switch ($merchantDetails->getBusinessType())
        {
            case BusinessType::NOT_YET_REGISTERED:
            case BusinessType::INDIVIDUAL:

                return [
                    Constants::PROMOTER_PAN_NAME => $merchantDetails->getPromoterPanName()
                ];

            case BusinessType::PRIVATE_LIMITED:
            case BusinessType::PUBLIC_LIMITED:
            case BusinessType::PARTNERSHIP:
            case BusinessType::LLP:
            case BusinessType::NGO:
            case BusinessType::SOCIETY:
            case BusinessType::TRUST:

                return [
                    Constants::COMPANY_PAN_NAME  => $merchantDetails->getBusinessName()
                ];

            default :

                return [
                    Constants::PROMOTER_PAN_NAME => $merchantDetails->getPromoterPanName(),
                    Constants::COMPANY_PAN_NAME  => $merchantDetails->getBusinessName()
                ];
        }
    }
}
