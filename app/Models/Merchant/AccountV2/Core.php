<?php

namespace RZP\Models\Merchant\AccountV2;

use Request;
use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Account\Entity;
use RZP\Models\Merchant\Account\Constants;
use RZP\Constants\HyperTrace;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Detail\NeedsClarification;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use RZP\Models\Merchant\Escalations\Constants as EscalationConstants;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class Core extends Merchant\Core
{
    public function createAccountV2(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        $this->trace->info(TraceCode::ACCOUNT_CREATION_V2_REQUEST, ['input' => $input,]);

        $accountCoreV1 = new Merchant\Account\Core();

        $accountCoreV1->validatePartnerAccess($partner);

        (new Validator)->validateInput('create_account', $input);

        $account = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_ENTITIES], function () use ($input, $partner) {

        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner)
        {
            $subMerchant = $this->createSubmerchantAndAssociatedEntities($partner, $input);

            return $subMerchant;
        });

            return $account;
        });

        Tracer::inspan(['name' => HyperTrace::ACCOUNT_V2_INVALIDATE_CACHE], function () use ($accountCoreV1, $account) {
        // since response from Stork during affected owners cache invalidation can come even before the above DB transaction
        // completion, send cache invalidation request again. Jira - https://razorpay.atlassian.net/browse/PRTS-1085
        $accountCoreV1->invalidateAffectedOwnersCache($account->getId());
        });

        $merchantDetails = $account->merchantDetail;
        $dimensions = $this->getDimensionsForAccountV2Metrics($merchantDetails, $partner);

        $this->trace->count(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $dimensions);

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
        $accountCoreV1 = new Merchant\Account\Core();

        $accountCoreV1->validatePartnerAccess($partner, $accountId);

        Entity::verifyIdAndStripSign($accountId);

        $this->validateAccountSuspension($accountId);

        $subMerchantDetails = $this->repo->merchant_detail->findOrFailPublic($accountId);

        if(empty($subMerchantDetails) === false && $subMerchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            (new Validator)->validateInput('edit_account', $input);
        }
        $account = $this->repo->transactionOnLiveAndTest(function () use ($input, $partner, $accountId, $subMerchantDetails)
        {
            $subMerchant = Tracer::inspan(['name' => HyperTrace::FILL_SUBMERCHANT_DETAILS], function () use ($input, $accountId) {
                $subMerchant = $this->fillSubMerchant($accountId, $input);
                $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);
                return $subMerchant;
            });

            $this->upsertMerchantEmails($subMerchant, $input);

            AutoUpdateMerchantProducts::dispatch(Product\Status::ACCOUNT_SOURCE, $subMerchant, $subMerchantDetails);

            return $subMerchant;
        });

        $dimensions = $this->getDimensionsForAccountV2Metrics($subMerchantDetails, $partner);

        $this->trace->count(Metric::ACCOUNT_V2_EDIT_SUCCESS_TOTAL, $dimensions);

        return $account;
    }

    protected function createSubmerchantAndAssociatedEntities(Merchant\Entity $partner, array $input): Merchant\Entity
    {
        Request::instance()->request->add([User\Entity::SKIP_CAPTCHA_VALIDATION => true]);

        $this->repo->assertTransactionActive();

        $subMerchantCreateInput = InputHelper::getSubMerchantCreateInput($input);

        // this creates only test balance
        $subMerchantArray = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_SERVICE], function () use ($subMerchantCreateInput, $partner) {

            return (new Merchant\Service)->createSubMerchant($subMerchantCreateInput, $partner, PartnerConstants::ADD_ACCOUNT, true);
        });

        $subMerchantId    = Entity::verifyIdAndStripSign($subMerchantArray[Entity::ID]);

        $subMerchant = Tracer::inspan(['name' => HyperTrace::FILL_SUBMERCHANT_DETAILS], function () use ($input, $subMerchantId) {

            $subMerchant = $this->fillSubMerchant($subMerchantId, $input);
            $subMerchant = $this->fillSubMerchantDetails($subMerchant, $input);
            return $subMerchant;
        });

        $this->upsertMerchantEmails($subMerchant, $input);

        return $subMerchant;
    }

    protected function fillSubMerchant(string $subMerchantId, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        $subMerchant = $this->repo->merchant->findOrFailPublic($subMerchantId);

        $noDocOnboarding = $input[Feature\Constants::NO_DOC_ONBOARDING] ?? false;

        if($noDocOnboarding == true)
        {
            if ($this->merchant->isFeatureEnabled(Feature\Constants::SUBM_NO_DOC_ONBOARDING) === true)
            {
                $this->addNoDocOnboardingFeature($subMerchantId);

                $this->trace->info(TraceCode::NO_DOC_ONBOARDING_ENABLED_FOR_SUBMERCHANT,[
                    'merchant_id'   => $subMerchantId,
                ]);
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBM_NO_DOC_ONBOARDING_NOT_ENABLED_FOR_PARTNER
                );
            }
        }

        $subMerchantInput = InputHelper::getSubMerchantInput($input);

        $merchantCore = new Merchant\Core;

        $merchantCore->editConfig($subMerchant, $subMerchantInput);

        return $subMerchant;
    }

    protected function fillSubMerchantDetails(Merchant\Entity $subMerchant, array $input): Merchant\Entity
    {
        $this->repo->assertTransactionActive();

        $detailInput = InputHelper::getSubMerchantDetailInput($input);

        $merchantDetailsCore = new Detail\Core;

        Tracer::inspan(['name' => HyperTrace::VALIDATE_NC_RESPONDED_IF_APPLICABLE], function () use ($subMerchant, $detailInput) {

            (new Validator())->validateNeedsClarificationRespondedIfApplicable($subMerchant, $detailInput);
        });

        $merchantDetailsCore->saveMerchantDetails($detailInput, $subMerchant);

        $this->updateUserIfApplicable($detailInput, $subMerchant->getEmail());

        $this->updateNCFieldsAcknowledgedIfApplicable($detailInput, $subMerchant);

        return $subMerchant;
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

    public function updateNCFieldsAcknowledgedIfApplicable(array $input, Merchant\Entity $subMerchant)
    {
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

            $payload = [Detail\Entity::CONTACT_MOBILE => $input[Detail\Entity::CONTACT_MOBILE]];

            (new User\Core)->edit($subMerchantUser, $payload);

        }
    }

    private function getDimensionsForAccountV2Metrics(Detail\Entity $merchantDetails, Merchant\Entity $partner): array
    {
        $dimensions = [
            'partner_type'              => $partner->getPartnerType(),
            'submerchant_business_type' => $merchantDetails->getBusinessType()
        ];

        return $dimensions;
    }

    /**
     * This function checks if a sub-merchant has exhausted the GMV limit for no-doc onboarding
     *
     * @param string $merchantId
     *
     * @return bool
     */
    public function isNoDocOnboardingGmvLimitExhausted(string $merchantId): bool
    {
        $escalations = $this->repo->merchant_onboarding_escalations->fetchEscalationForThresholdAndMilestone($merchantId,
            EscalationConstants::HARD_LIMIT_NO_DOC, EscalationConstants::HARD_LIMIT_KYC_PENDING_THRESHOLD);

        if (empty($escalations) === false)
        {
            return true;
        }

        return false;
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
        $isNoDocGmvLimitExhausted = $this->isNoDocOnboardingGmvLimitExhausted($merchant->getId());

        if ($merchant->isNoDocOnboardingEnabled() === true and $isNoDocGmvLimitExhausted === true)
        {
            return true;
        }

        return false;
    }
}
