<?php

namespace RZP\Models\Merchant\AccountV2;

use Request;
use RZP\Exception;
use RZP\Models\User;
use RZP\Trace\Tracer;
use RZP\Models\Feature;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Product;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant\Account\Entity;
use RZP\Models\Merchant\Account\Constants;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Detail\NeedsClarification;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use RZP\Models\Partner\Config\Constants as ConfigConstants;
use RZP\Models\Merchant\Escalations\Constants as EscalationConstants;

class Core extends Merchant\Core
{
    public function createAccountV2(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        $this->trace->info(TraceCode::ACCOUNT_CREATION_V2_REQUEST, ['input' => $input,]);

        $accountCoreV1 = new Merchant\Account\Core();

        $accountType = $input['type'] ?? null;

        $accountCoreV1->validatePartnerAccess($partner,null, $accountType);

        (new Validator)->validateInput('create_account', $input);

        $this->executeTosAcceptanceExperiment($input, $partner);

        // Calling downstream validation to be in sync with them. https://razorpay.slack.com/archives/C021KESTRLH/p1647518134264949
        $subMerchantInput = InputHelper::getSubMerchantInput($input);
        $detailInput = InputHelper::getSubMerchantDetailInput($input);

        (new Merchant\Validator())->validateInput('edit_config', $subMerchantInput);
        (new Detail\Validator())->validateInput('edit', $detailInput);

        $account = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_ENTITIES], function () use ($input, $partner) {

        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner)
        {
            $subMerchant = $this->createSubmerchantAndAssociatedEntities($partner, $input);

            unset($input[Constants::IS_IGNORE_TOS_ACCEPTANCE]);
            return $subMerchant;
        });

            return $account;
        });

        $merchantDetails = $account->merchantDetail;
        $dimensions = $this->getDimensionsForAccountV2Metrics($merchantDetails, $partner);

        $this->trace->count(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $dimensions);

        if($account->isLinkedAccount() === true)
        {
            $traceCode = ($account->isRouteNoDocKycEnabledForParentMerchant() === true) ? TraceCode::LINKED_ACCOUNT_CREATED_VIA_PUBLIC_API_NO_DOC_KYC :
                                                                         TraceCode::LINKED_ACCOUNT_CREATED_VIA_PUBLIC_API;

            $this->trace->info($traceCode, [
                'parent_mid'        =>  $account->parent->getId(),
                'linked_account_id' => $account->getId()
            ]);
        }

        return $account;
    }

    public function fetchAccountV2(string $accountId)
    {
        $timeStarted = millitime();

        $accountCoreV1 = new Merchant\Account\Core();

        $accountCoreV1->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($accountId);

        $relations = ['merchantDetail', 'features', 'emails'];

        $account = $this->repo
            ->merchant
            ->findOrFailPublicWithRelations($accountId, $relations);

        $merchantDetails = $account->merchantDetail;

        $dimensions = $this->getDimensionsForAccountV2Metrics($merchantDetails, $this->merchant);

        $this->trace->count(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $dimensions);

        $this->trace->histogram(Metric::ACCOUNT_V2_FETCH_TIME_MS, millitime() - $timeStarted, $dimensions);

        return $account;
    }

    public function editAccountV2(Merchant\Entity $partner, string $accountId, array $input)
    {
        $functionStartTime = microtime(true);

        $accountCoreV1 = new Merchant\Account\Core();

        $accountCoreV1->validatePartnerAccess($partner, $accountId);

        Entity::verifyIdAndStripSign($accountId);

        $this->validateAccountSuspension($accountId);

        $subMerchantDetails = $this->repo->merchant_detail->findOrFailPublic($accountId);

        if(empty($subMerchantDetails) === false && $subMerchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            (new Validator)->validateInput('edit_account', $input);
        }

        $validationDuration = (microtime(true) - $functionStartTime) * 1000;

        $this->executeTosAcceptanceExperiment($input, $partner);
        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner, $accountId, $subMerchantDetails)
        {
            $subMerchant = Tracer::inspan(['name' => HyperTrace::FILL_SUBMERCHANT_DETAILS], function () use ($input, $accountId) {
                $subMerchant = $this->fillSubMerchant($accountId, $input);
                $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);

                unset($input[Constants::IS_IGNORE_TOS_ACCEPTANCE]);
                return $subMerchant;
            });

            $this->upsertMerchantEmails($subMerchant, $input);

            AutoUpdateMerchantProducts::dispatch(Product\Status::ACCOUNT_SOURCE, $accountId);

            return $subMerchant;
        });

        $dimensions = $this->getDimensionsForAccountV2Metrics($subMerchantDetails, $partner);

        $this->trace->count(Metric::ACCOUNT_V2_EDIT_SUCCESS_TOTAL, $dimensions);

        $this->trace->info(TraceCode::ACCOUNT_V2_OVERALL_UPDATE_LATENCY, [
            'merchant_id'         => $accountId,
            'validation_duration' => $validationDuration,
            'over_all_duration'   => (microtime(true) - $functionStartTime) * 1000,
            'start_time'          => $functionStartTime
        ]);

        return $account;
    }

    protected function createSubmerchantAndAssociatedEntities(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        Request::instance()->request->add([User\Entity::SKIP_CAPTCHA_VALIDATION => true]);

        $this->repo->assertTransactionActive();

        $subMerchantCreateInput = InputHelper::getSubMerchantCreateInput($input);

        // this creates only test balance
        $subMerchantArray = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_SERVICE], function () use ($subMerchantCreateInput, $partner) {

            return (new Merchant\Service)->createSubMerchant($subMerchantCreateInput, $partner, PartnerConstants::ADD_ACCOUNT_V2_ONBOARDING_API, true);
        });

        $subMerchantId = Entity::verifyIdAndSilentlyStripSign($subMerchantArray[Entity::ID]);

        $subMerchant = Tracer::inspan(['name' => HyperTrace::FILL_SUBMERCHANT_DETAILS], function () use ($input, $subMerchantId) {

            $subMerchant = $this->fillSubMerchant($subMerchantId, $input);
            $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);
            return $subMerchant;
        });

        $this->addInstantActivationTagIfApplicable($subMerchant, $input);

        $this->upsertMerchantEmails($subMerchant, $input);

        return $subMerchant;
    }

    protected function fillSubMerchant(string $subMerchantId, array $input): Merchant\Entity
    {
        $startTime = microtime(true);

        $this->repo->assertTransactionActive();

        $subMerchant = $this->repo->merchant->findOrFailPublic($subMerchantId);

        $noDocOnboarding = $input[Feature\Constants::NO_DOC_ONBOARDING] ?? false;

        if($noDocOnboarding == true)
        {
            if ($this->merchant->isFeatureEnabled(Feature\Constants::SUBM_NO_DOC_ONBOARDING) === true)
            {
                $this->addNoDocOnboardingFeature($subMerchantId);

                $mCore = (new MerchantCore());

                $mCore->appendTag($subMerchant, DetailConstants::NO_DOC_ONBOARDING_TAG);

                $this->trace->info(TraceCode::NO_DOC_ONBOARDING_ENABLED_FOR_SUBMERCHANT,[
                    'merchant_id'   => $subMerchantId,
                ]);
            }
            else
            {
                $ex = new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBM_NO_DOC_ONBOARDING_NOT_ENABLED_FOR_PARTNER
                );

                $this->app['diag']->trackOnboardingEvent(EventCode::NO_DOC_SUBMERCHANT_ONBOARDING_FAILED, $this->merchant, $ex);

                throw $ex;
            }
        }

        $subMerchantInput = InputHelper::getSubMerchantInput($input);

        $merchantCore = new Merchant\Core;

        // Updating merchant if the submerchant input is non-empty
        // The editConfig internally does update of merchant
        // Incase if the merchant is locked for update by another thread, unnecessarily the current thread keeps waiting until lock is released
        if (empty($subMerchantInput) === false)
        {
            $merchantCore->editConfig($subMerchant, $subMerchantInput);
        }

        $this->trace->info(TraceCode::ACCOUNT_V2_MERCHANT_UPDATE_LATENCY, [
            'merchant_id' => $subMerchant->getId(),
            'start_time'  => $startTime,
            'duration'    => (microtime(true) - $startTime) * 1000
        ]);

        return $subMerchant;
    }

    protected function fillSubMerchantDetails(Merchant\Entity $subMerchant, array $input): Merchant\Entity
    {
        $startTime = microtime(true);

        $this->repo->assertTransactionActive();

        $detailInput = InputHelper::getSubMerchantDetailInput($input);

        $merchantDetailsCore = new Detail\Core;

        Tracer::inspan(['name' => HyperTrace::VALIDATE_NC_RESPONDED_IF_APPLICABLE], function () use ($subMerchant, $detailInput) {

            (new Validator())->validateNeedsClarificationRespondedIfApplicable($subMerchant, $detailInput);
        });

        (new Validator())->validateOptionalFieldSubmissionInActivatedKycPendingState($subMerchant, $detailInput);

        Tracer::inspan(['name' => HyperTrace::SAVE_MERCHANT_DETAILS], function () use ($merchantDetailsCore, $detailInput, $subMerchant) {

            $merchantDetailsCore->saveMerchantDetails($detailInput, $subMerchant);
        });

        $this->updateUserIfApplicable($detailInput, $subMerchant->getEmail());

        $this->updateNCFieldsAcknowledgedIfApplicable($detailInput, $subMerchant);

        $this->trace->info(TraceCode::ACCOUNT_V2_MERCHANT_UPDATE_LATENCY, [
            'merchant_id' => $subMerchant->getId(),
            'start_time'  => $startTime,
            'duration'    => (microtime(true) - $startTime) * 1000
        ]);

        return $subMerchant;
    }

    protected function executeTosAcceptanceExperiment(&$input, Merchant\Entity $partner)
    {
        $partnerId = $partner->getId();
        $isIgnoreTosAcceptance = $this->app->razorx->getTreatment(
            $partner->getId(),
            Merchant\RazorxTreatment::IGNORE_TOS_ACCEPTANCE,
            $this->mode
        );

        $input[Constants::IS_IGNORE_TOS_ACCEPTANCE] = $isIgnoreTosAcceptance;
    }

    /**
     * Add no_doc_onboarding feature to sub-merchant which will enable it to onboard
     * without any documents. This will activate the sub-merchant with a certain GMV limit.
     *
     * @param string $subMerchantId
     *
     * @return void
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    private function addNoDocOnboardingFeature(string $subMerchantId)
    {
        $featureName = Feature\Constants::NO_DOC_ONBOARDING;

        $featureParams = [
            Feature\Entity::ENTITY_ID   => $subMerchantId,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAME        => $featureName,
        ];

        (new Feature\Core())->create($featureParams, true);
    }

    public function removeNoDocOnboardingFeature(string $subMerchantId)
    {
        $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(Merchant\Constants::MERCHANT,
            $subMerchantId, Feature\Constants::NO_DOC_ONBOARDING);

        (new Feature\Core())->delete($feature, true);
    }

    /**
     * Add no_doc_limit_breached tag to the sub-merchant when no-doc onboarding GMV limit is breached.
     *
     * @param Merchant\Entity $subMerchant
     *
     * @return void
     */
    public function addNoDocLimitBreachedTag(Merchant\Entity $subMerchant)
    {
        $existingTags = $subMerchant->tagNames();

        if (in_array(Constants::NO_DOC_LIMIT_BREACHED, array_map('strtolower', $existingTags)) === false)
        {
            (new Merchant\Core())->appendTag($subMerchant, Constants::NO_DOC_LIMIT_BREACHED);
        }
    }

    /**
     * Add no_doc_partially_activated tag to the sub-merchant when no-doc onboarded merchant reaches activated_kyc_pending state
     *
     * @param Merchant\Entity $subMerchant
     *
     * @return void
     */
    public function addNoDocPartiallyActivatedTag(Merchant\Entity $subMerchant)
    {
        $existingTags = $subMerchant->tagNames();

        if (in_array(Constants::NO_DOC_PARTIALLY_ACTIVATED, array_map('strtolower', $existingTags)) === false)
        {
            (new Merchant\Core())->appendTag($subMerchant, Constants::NO_DOC_PARTIALLY_ACTIVATED);
        }
    }

    /**
     * Add the instant_activation_subm tag to whitelist sub-merchant for instant activation flow
     * if the sub-m is whitelisted for the no-doc and partner is enabled with the INSTANT_ACTIVATION_V2_API feature.
     * @param Merchant\Entity $submerchant
     * @param array $input
     */
    public function addInstantActivationTagIfApplicable(Merchant\Entity $submerchant, array $input)
    {
        $noDocOnboarding = $input[Feature\Constants::NO_DOC_ONBOARDING] ?? false;

        if($noDocOnboarding == true and $this->merchant->isFeatureEnabled(Feature\Constants::SUBM_NO_DOC_ONBOARDING) === true)
        {
            // instant activation won't be enabled for merchants which are whitelisted for no-doc
            return ;
        }

        $properties = [
            'id' => $this->merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.partners_excluded_from_instant_act_v2_api_exp_id')
        ];

        $isExpEnable = (new Merchant\Core())->isSplitzExperimentEnable($properties,'enable');

        if($isExpEnable === true)
        {
            //we will be restricting existing partners for now, making it general release for newly onboarded partners.
            return;
        }

        (new Merchant\Core())->appendTag($submerchant, Constants::INSTANT_ACTIVATION_SUBM);

        $this->trace->info(TraceCode::INSTANT_ACTIVATION_ONBOARDING_API_TAG_APPENDED,[
            'sub-merchant_id'   => $submerchant->getId(),
            'tag_name'          => Constants::INSTANT_ACTIVATION_SUBM
        ]);
        $dimension = $this->getDimensionsForAccountV2Metrics($submerchant->merchantDetail, $this->merchant);

        $this->trace->count(Metric::ACCOUNT_V2_MERCHANT_SIGNUP_INSTANT_ACTIVATION, $dimension);
    }

    public function isInstantActivationTagEnabled($merchantId): bool
    {
        $tags = (new Merchant\Service())->getTags($merchantId);

        return (in_array('Instant_activation_subm', $tags) === true);
    }

    public function updateNCFieldsAcknowledgedIfApplicable(array $input, Merchant\Entity $subMerchant)
    {
        $startTime = microtime(true);

        $subMerchantDetails = $subMerchant->merchantDetail;

        if (empty($subMerchantDetails) === true || $subMerchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            return;
        }

        $needsClarificationCore = new NeedsClarification\Core();

        foreach($input as $field => $value)
        {
            $needsClarificationCore->updateNCFieldAcknowledged($field, $subMerchantDetails);
        }

        $this->trace->info(TraceCode::ACCOUNT_V2_MERCHANT_NC_FIELDS_ACKNOWLEDGED_LATENCY, [
            'start_time'  => $startTime,
            'merchant_id' => $subMerchant->getId(),
            'duration'    => (microtime(true) - $startTime) * 1000,
        ]);
    }

    protected function upsertMerchantEmails(Merchant\Entity $subMerchant, array $input)
    {
        if (isset($input[Constants::CONTACT_INFO]) === false)
        {
            return;
        }

        $fieldNames = [
            Constants::SUPPORT,
            Constants::CHARGEBACK,
            Constants::REFUND,
            Constants::DISPUTE,
        ];

        $emailCore = new Merchant\Email\Core;

        foreach ($fieldNames as $fieldName)
        {
            if (array_key_exists($fieldName, $input[Constants::CONTACT_INFO]) === true)
            {
                $emailInput = $input[Constants::CONTACT_INFO][$fieldName];

                $emailInput[Constants::TYPE] = $fieldName;

                if (isset($emailInput[Constants::POLICY_URL]) === true)
                {
                    $policyUrl = $emailInput[Constants::POLICY_URL];

                    $emailInput[Constants::URL] = $policyUrl;

                    unset($emailInput[Constants::POLICY_URL]);
                }

                $emailCore->upsert($subMerchant, $emailInput);
            }
        }
    }

    public function validateAccountSuspension(string $accountId)
    {
        $subMerchant = $this->repo->merchant->findOrFailPublic($accountId);

        if ($subMerchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_SUSPENDED,  null);
        }
    }

    private function updateUserIfApplicable(array $input, string $subMerchantEmail): void
    {
        if(isset($input[Detail\Entity::CONTACT_MOBILE]) === true)
        {
            $subMerchantUser = $this->repo->user->getUserFromEmail($subMerchantEmail);

            // In case of Linked Accounts Submerchant user can be null sometimes.
            // Submerchant user is onlu created when dashboard_access is given.
            if (empty($subMerchantUser) === false)
            {
                $payload = [Detail\Entity::CONTACT_MOBILE => $input[Detail\Entity::CONTACT_MOBILE]];

                (new User\Core)->edit($subMerchantUser, $payload);
            }
        }
    }

    private function getDimensionsForAccountV2Metrics(Detail\Entity $merchantDetails, Merchant\Entity $partner): array
    {
        $dimensions = [
            'partner_type'              => $partner->getPartnerType(),
            'account_type'              => ($merchantDetails->merchant->isLinkedAccount() === true) ? Type::ROUTE : Type::STANDARD,
            'submerchant_business_type' => $merchantDetails->getBusinessType()
        ];

        return $dimensions;
    }

    /**
     * This function checks if a sub-merchant has exhausted the GMV limit for no-doc onboarding
     *
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public function isNoDocOnboardingGmvLimitExhausted(Merchant\Entity $merchant): bool
    {
        $threshold = $this->getGmvLimitForNoDocMerchant($merchant);

        $escalations = $this->repo->merchant_onboarding_escalations->fetchLiveEscalationForThresholdAndMilestone($merchant->getId(),
            EscalationConstants::HARD_LIMIT_NO_DOC, $threshold);

        if (empty($escalations) === false)
        {
            return true;
        }

        return false;
    }

    /**
     * This function fetches the gmv limit of a no-doc onboarded sub-merchant based upon below rules:
     * 1. If sub-merchant's gst is not verified(2-way onboarded), then gmv limit = 50k
     * 2. If sub-merchant's gst is verified(3-way onboarded), then gmv limit:
     *    a. Gmv limit set in sub_merchant_config of sub-merchant's partner's managed application,
     *    b. If not set, then default gmv limit = 5 lakhs
     *
     * @param Merchant\Entity $merchant
     *
     * @return int
     */
    public function getGmvLimitForNoDocMerchant(Merchant\Entity $merchant): int
    {
        $merchantDetail = $merchant->merchantDetail;

        $threshold = EscalationConstants::HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY;

        if($merchantDetail->getGstinVerificationStatus() === Detail\Constants::VERIFIED)
        {
            $threshold = EscalationConstants::HARD_LIMIT_KYC_PENDING_THRESHOLD_3_WAY;

            $accessMaps = $this->repo
                ->merchant_access_map
                ->getMappingByApplicationType($merchant->getId(), Merchant\MerchantApplications\Entity::MANAGED);

            $accessMap = $accessMaps->first();

            if(empty($accessMap) === false)
            {
                $partnerAppId = $accessMap['entity_id'];

                $applicationConfig = $this->repo->partner_config->getApplicationConfig($partnerAppId);

                if(empty($applicationConfig) === false)
                {
                    $submerchantConfig = $applicationConfig->getSubMerchantConfig();

                    //If GMV limit is set in test mode without workflow, the config fetched will be of type string and it needs type conversion to array to be processed further
                    if(is_string($submerchantConfig) === true)
                    {
                        $submerchantConfig = json_decode($submerchantConfig, true);
                    }

                    if(empty($submerchantConfig) === false and
                        array_key_exists(ConfigConstants::GMV_LIMIT,$submerchantConfig) === true and
                        empty($submerchantConfig[ConfigConstants::GMV_LIMIT]) === false)
                    {
                        foreach ($submerchantConfig[ConfigConstants::GMV_LIMIT] as $gmvLimit)
                        {
                            if($gmvLimit[ConfigConstants::SET_FOR] === ConfigConstants::NO_DOC_SUBMERCHANTS)
                            {
                                $threshold = $gmvLimit[ConfigConstants::VALUE];

                                break;
                            }
                        }
                    }
                }
            }
        }
        return $threshold;
    }

    /**
     * This function checks if a sub-merchant has no-doc onboarding feature enabled and
     * has exhausted the GMV limit for no-doc onboarding
     *
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public function isNoDocEnabledAndGmvLimitExhausted(Merchant\Entity $merchant): bool
    {
        if($merchant->isNoDocOnboardingFeatureEnabled() === false)
        {
            return false;
        }

        $isNoDocGmvLimitExhausted = $this->isNoDocOnboardingGmvLimitExhausted($merchant);

        if ($isNoDocGmvLimitExhausted === true)
        {
            return true;
        }

        return false;
    }

    public function isSubmNoDocOnboardingEnabledForMid(string $merchantId): bool
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        return ($merchant->isFeatureEnabled(Feature\Constants::SUBM_NO_DOC_ONBOARDING) === true);
    }

    public function triggerWebhookForNoDocGmvLimitBreach(Merchant\Entity $merchant, array $params)
    {
        $data = $this->getNoDocGmvLimitWarnData($merchant, $params);

        $eventPayload = [
            ApiEventSubscriber::MAIN        => $merchant,
            ApiEventSubscriber::WITH        => $data,
            ApiEventSubscriber::MERCHANT_ID => $merchant->getId()
        ];

        $this->app['events']->dispatch('api.account.no_doc_onboarding_gmv_limit_warning', $eventPayload);
    }

    private function getNoDocGmvLimitWarnData(Merchant\Entity $merchant, array $params = [])
    {
        $merchantId = $merchant->getId();

        if(empty($params) === true or empty($params['threshold']) === true
            or empty($params['current_gmv']) === true or empty($params['milestone']) === true)
        {
            throw new Exception\RuntimeException('Data sent to trigger webhook for no doc gmv breach warning is not sufficient', [
                'merchant_id'  => $merchantId,
                'parameters'   => $params
            ]);
        }

        $message = null;

        $threshold = $params['threshold'];

        $currentGmv = $params['current_gmv'];

        switch ($params['milestone'])
        {
            case EscalationConstants::NO_DOC_P90_GMV:
            case EscalationConstants::NO_DOC_P91_GMV:
                switch ($merchant->merchantDetail->getActivationStatus())
                {
                    case Detail\Status::UNDER_REVIEW :
                        $message = "You can accept payments upto INR " .max(($threshold - $currentGmv)/100, 0). ". You can continue to accept payments without any limits post full account activation.";
                        break;
                    case Detail\Status::NEEDS_CLARIFICATION :
                        $message = "You can accept payments upto INR " .max(($threshold - $currentGmv)/100, 0). ". In order to remove this limit, kindly provide responses to outstanding clarifications for submitted KYC documents.";
                        break;
                    case Detail\Status::ACTIVATED_KYC_PENDING :
                        $message = "You can accept payments upto INR " .max(($threshold - $currentGmv)/100, 0). ". In order to remove this limit, kindly submit the KYC documents.";
                        break;
                }
                break;
            case EscalationConstants::HARD_LIMIT_NO_DOC:
                switch ($merchant->merchantDetail->getActivationStatus())
                {
                    case Detail\Status::UNDER_REVIEW :
                        $message = "You have breached the GMV limit. You can continue to accept payments after full account activation.";
                        break;
                    case Detail\Status::NEEDS_CLARIFICATION :
                        $message = "You have breached the GMV limit. In order to remove this limit, kindly submit the KYC documents.";
                        break;
                }
        }

        return [
            'acc_id'        => $merchantId,
            'gmv_limit'     => $threshold/100,
            'current_gmv'   => $currentGmv/100,
            'message'       => $message,
            'live'          => $merchant->isLive(),
            'funds_on_hold' => $merchant->isFundsOnHold()
        ];
    }
}
