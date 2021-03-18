<?php

namespace RZP\Models\Partner;

use Razorpay\OAuth;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc;
use RZP\Models\Merchant\Constants;
use RZP\Models\Partner\Activation;
use RZP\lib\ConditionParser\Parser;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\BadRequestException;
use RZP\Jobs\PartnerActivationMigration;
use RZP\Models\Merchant\Detail\ValidationFields;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Detail\Core
{
    /**
     * @var OAuth\Application\Repository
     */
    protected $appRepo;

    public function __construct()
    {
        parent::__construct();

        $this->appRepo = new OAuth\Application\Repository;
    }

    /**
     * Connects a sub-merchant to an application, and return a partner token
     *
     * @param OAuth\Application\Entity $app
     * @param Merchant\Entity          $merchant
     * @param Merchant\Entity          $subMerchant
     *
     * @return string
     * @throws BadRequestValidationFailureException
     */
    public function connectMerchant(
        OAuth\Application\Entity $app,
        Merchant\Entity $merchant,
        Merchant\Entity $subMerchant) : string
    {
        $appId = $app->getId();

        try
        {
            $token = $this->app['authservice']->createPartnerToken($appId, $merchant->getId(), $subMerchant->getId());

            $mapInput[Merchant\AccessMap\Entity::APPLICATION_ID] = $appId;
        }
        catch (\Throwable $t)
        {
            $this->trace->traceException($t);

            throw new BadRequestValidationFailureException('Token creation failed');
        }

        (new Merchant\AccessMap\Core)->addMappingForOAuthApp($merchant, $subMerchant, $mapInput);

        return $token['partner_token'];
    }

    public function isForceGreylistMerchant(Merchant\Entity $subMerchant, Merchant\Entity $partner = null)
    {
        $subMerchantDetails = (new Detail\Core())->getMerchantDetails($subMerchant);

        if (empty($partner) === true)
        {
            $partners = (new Merchant\Core)->fetchAffiliatedPartners($subMerchant->getId());

            $partner = $partners->filter(function(Merchant\Entity $partner) use ($subMerchant) {

                return ($partner->forceGreyListInternational() === true);

            })->first();
        }

        //
        // if submerchant asked for international and partner wants to force international to greylist
        //

        return ((empty($partner) === false) and
                ($subMerchantDetails->getBusinessInternational() === true) and
                ($partner->forceGreyListInternational() === true));
    }

    public function isKycHandledBYPartner(Merchant\Entity $subMerchant)
    {
        $partners = (new Merchant\Core)->fetchAffiliatedPartners($subMerchant->getId());

        $partner = $partners->filter(function(Merchant\Entity $partner) use ($subMerchant) {

            return ($partner->isKycHandledByPartner() === true);

        })->first();

        return ((empty($partner) === false) and
                ($partner->isKycHandledByPartner() === true));
    }

    public function validateExternalIdForPartnerSubmerchant(Merchant\Entity $partner, string $externalId)
    {
        $merchantCore = new Merchant\Core;

        $appIds = $merchantCore->getPartnerApplicationIds($partner);

        $this->trace->info(TraceCode::PARTNER_FETCH_SUBMERCHANTS,
                           [
                               'partner_id'  => $partner->getId(),
                               'app_ids'     => $appIds,
                               'external_id' => $externalId,
                           ]);

        $params = [
            Merchant\Entity::EXTERNAL_ID => $externalId,
        ];

        $merchants = $this->repo->merchant->fetchSubmerchantsByAppIds($appIds, $params);

        if ($merchants->isNotEmpty() === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_DUPLICATE_EXTERNAL_ID,
                Merchant\Entity::EXTERNAL_ID,
                [
                    'partner_id' => $partner->getId(),
                    'merchants'  => $merchants->pluck(Merchant\Entity::ID)->toArray(),
                ]);
        }
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public function isSmsBlockedSubmerchant(Merchant\Entity $merchant): bool
    {
        $partners = (new Merchant\Core())->fetchAffiliatedPartners($merchant->getId());

        //
        //submerchant can belong to only one aggregator or fully managed at a time
        //
        $partner = $partners->filter(function(Merchant\Entity $partner) {
            return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
        })->first();

        if (empty($partner) === true)
        {
            return false;
        }

        //
        // Is the feature flag enabled for the submerchant or the partner
        //
        return (($merchant->isFeatureEnabled((FeatureConstant::BLOCK_ONBOARDING_SMS) === true)
            or ($partner->isFeatureEnabled(FeatureConstant::BLOCK_ONBOARDING_SMS) === true)));
    }

    public function createPartnerActivationForPartners(array $input)
    {
        RuntimeManager::setTimeLimit(1800);

        if (empty($input['merchant_ids']) === false)
        {
            PartnerActivationMigration::dispatch($this->mode, $input['merchant_ids']);

            return [];
        }

        $afterId = null;

        $count = 0;

        while (true)
        {
            $repo = $this->repo;

            $merchantIds = $repo->useSlave(function () use ($afterId, $repo) {
                return $repo->merchant->findPartnersWithoutPartnerActivation(1000, $afterId);
            });

            if (empty($merchantIds) === true)
            {
                break;
            }

            $afterId = end($merchantIds);

            $count += count($merchantIds);

            PartnerActivationMigration::dispatch($this->mode, $merchantIds);
        }

        return ['count' => $count];
    }

    public function markPartnerKycSubmittedAndLock(Activation\Entity $partnerActivation)
    {
        $submittedAt = Carbon::now()->getTimestamp();

        $input = [
            'submitted'    => 1,
            'submitted_at' => $submittedAt,
            'locked'       => true,
        ];

        $partnerActivation->fill($input);

        $this->repo->saveOrFail($partnerActivation);
    }

    public function getPartnerValidationFields(Entity $merchantDetails)
    {
        $businessType = $merchantDetails->getBusinessType();

        return ValidationFields::getPartnerKycValidationFields($businessType);
    }

    /**
     * This function is similar to Merchant/Detail/Core->saveMerchantDetails
     * In this function, we would check whether all requirements are submitted and is eligible for submission.
     * If partner submits the form, details get submitted, else activation progress is returned
     *
     * @param array           $input
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     * @param Entity          $oldMerchantDetails
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException
     * @throws \Throwable
     */
    public function processPartnerActivation(array $input, Detail\Entity $merchantDetails, Merchant\Entity $merchant, Detail\Entity $oldMerchantDetails)
    {
        return $this->mutex->acquireAndRelease(
            $merchant->getId(),
            function() use ($input, $merchantDetails, $oldMerchantDetails, $merchant) {

                return $this->repo->transactionOnLiveAndTest(function() use (
                    $input,
                    $oldMerchantDetails,
                    $merchantDetails,
                    $merchant
                ) {
                    $verificationResponse = $this->getPartnerKycVerificationDetails($merchant);

                    $partnerActivation = $this->getPartnerActivation($merchant);

                    $this->repo->partner_activation->lockForUpdate($merchant->getId());

                    $response = $this->createPartnerResponse($merchantDetails);

                    if ($this->canSubmit($input, $verificationResponse) === true)
                    {
                        $response = $this->submitPartnerActivationForm($merchant, $merchantDetails, $oldMerchantDetails, $partnerActivation);

                       //TODO handle NC status change to under_review workflow
                    }

                    return $response;
                });
            },
            Activation\Constants::PARTNER_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PARTNER_ACTIVATION_OPERATION_IN_PROGRESS,
            Activation\Constants::PARTNER_MUTEX_RETRY_COUNT);

    }

    /**
     * This function is used to lock and submit the partner activation form and update the partner with relevant activation status
     * @param Merchant\Entity   $merchant
     * @param Entity            $merchantDetails
     * @param Entity            $oldMerchantDetails
     * @param Activation\Entity $partnerActivation
     *
     * @return array
     * @throws \Throwable
     */
    public function submitPartnerActivationForm(Merchant\Entity $merchant, Entity $merchantDetails, Entity $oldMerchantDetails, Activation\Entity $partnerActivation)
    {
        $activationStatus = $this->getApplicablePartnerActivationStatus($merchantDetails);

        $this->markPartnerKycSubmittedAndLock($partnerActivation);

        $this->attemptPennyTesting($merchantDetails, $merchant);

        $this->triggerValidationRequests($merchant, $merchantDetails);

        $input = [Activation\Entity::ACTIVATION_STATUS => $activationStatus];

        (new Activation\Core)->updatePartnerActivationStatus($merchant, $oldMerchantDetails, $partnerActivation, $merchant, $input);

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_SUBMITTED,
                           [
                               'merchant_id' => $merchant->getId()
                           ]);

        return $this->createPartnerResponse($merchantDetails);
    }

    /**
     * This function would check for requirements needs to be submitted by the partner based on business type.
     * Activation progress is calculated based on the number of details submitted vs total number of details required
     *
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function getPartnerKycVerificationDetails(Merchant\Entity $merchant): array
    {
        $merchantDetails = $merchant->merchantDetail;

        $validationFields = $this->getPartnerValidationFields($merchantDetails);

        $totalRequiredFieldCount = count($validationFields);

        $merchantDetailsArr = $merchantDetails->toArray();

        $requiredFields = [];

        foreach ($validationFields as $key)
        {
            if ($this->isKeyPresent($key, $merchantDetailsArr, []) === false)
            {
                $requiredFields[] = $key;
            }
        }

        $response = [];

        if (count($requiredFields) > 0)
        {
            $remainingFields = count($requiredFields);

            $response[Activation\Constants::VERIFICATION] = [
                Activation\Constants::STATUS              => Activation\Constants::DISABLED,
                Activation\Constants::DISABLE_REASON      => Activation\Constants::REQUIRED_FIELDS,
                Activation\Constants::REQUIRED_FIELDS     => $requiredFields,
                Activation\Constants::ACTIVATION_PROGRESS => 100 - intval($remainingFields * 100 / $totalRequiredFieldCount),
            ];

            $response[Activation\Constants::CAN_SUBMIT] = false;
        }
        else
        {
            $response[Activation\Constants::VERIFICATION] = [
                Activation\Constants::STATUS              => Activation\Constants::PENDING,
                Activation\Constants::ACTIVATION_PROGRESS => 100,
            ];
            $response[Activation\Constants::CAN_SUBMIT]   = true;
        }

        return $response;
    }

    public function getPartnerActivation(Merchant\Entity $merchant)
    {
        $merchant->load('partnerActivation');

        $partnerActivation = $merchant->partnerActivation;

        if (empty($partnerActivation) === true)
        {
            $partnerActivation = (new Activation\Core)->createOrFetchPartnerActivationForMerchant($merchant, false);
        }

        return $partnerActivation;
    }

    public function createPartnerResponse(Entity $merchantDetails): array
    {
        $response = $merchantDetails->toArrayPublic();

        $merchant = $merchantDetails->merchant;

        $stakeholder = $merchantDetails->stakeholder;

        $partnerActivation = $this->getPartnerActivation($merchant);

        $currentActivationState = $partnerActivation->activationState();

        $partnerRejectionReasons = [];

        if ((empty($currentActivationState) === false) and
            ($currentActivationState->name === Activation\Constants::REJECTED))
        {
            $rejectionReasons = $currentActivationState->rejectionReasons()->get();

            $partnerRejectionReasons = $rejectionReasons->toArrayPublic();
        }

        $verification = $this->getPartnerKycVerificationDetails($merchant);

        $response[Constants::MERCHANT]   = $merchant->toArrayPublic();
        $response[E::STAKEHOLDER]        = $stakeholder;
        $response['isAutoKycDone']       = $this->isPartnerKycDone($merchantDetails);
        $response[E::PARTNER_ACTIVATION] = $this->getPartnerDetails($verification, $partnerActivation, $partnerRejectionReasons);

        return $response;
    }

    /**
     * This function is used to update the partner_activation entity. i.e. used for updating Kyc_clarification_reasons for the partner
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Activation\Entity
     */
    public function editPartnerActivation(Merchant\Entity $merchant, array $input): Activation\Entity
    {
        $partnerActivation = $this->getPartnerActivation($merchant);

        $partnerActivation->edit($input);

        $kycClarificationReasons = $this->getUpdatedPartnerKycClarificationReasons($input, $merchant->getId());

        if(empty($kycClarificationReasons) === false)
        {
            $partnerActivation->setKycClarificationReasons($kycClarificationReasons);
        }

        $this->repo->saveOrFail($partnerActivation);

        return $partnerActivation;
    }

    /**
     * This function would fetch the updated kyc clarification reason for each field that has been added
     * Marks newer clarification reasons as current clarification reason(i.e. latest)
     *
     * @param array  $input
     * @param string $merchantId
     *
     * @return array|mixed
     */
    protected function getUpdatedPartnerKycClarificationReasons(array $input, string $merchantId)
    {
        $partnerActivation         = $this->repo->partner_activation->findOrFailPublic($merchantId);
        $existingKycClarifications = $partnerActivation->getKycClarificationReasons() ?? [];
        $existingReasons           = $existingKycClarifications[Entity::CLARIFICATION_REASONS] ?? null;

        $newKycClarifications      = $input[Entity::KYC_CLARIFICATION_REASONS] ?? [];
        $newAdditionalDetails      = $newKycClarifications[Entity::ADDITIONAL_DETAILS] ?? null;
        $newReasons                = $newKycClarifications[Entity::CLARIFICATION_REASONS] ?? null;

        if ((empty($newReasons) === true) and
            (empty($newAdditionalDetails) === true))
        {
            return $existingKycClarifications;
        }

        $statusChangeLogs = $partnerActivation->getActivationStatusChangeLog();

        $needsClarificationCount = $this->getStatusChangeCount($statusChangeLogs, Activation\Constants::UNDER_REVIEW);

        $clarificationReasons = $this->getClarificationReasons($existingReasons, $newReasons, $needsClarificationCount, null);

        return [
            Entity::CLARIFICATION_REASONS => $clarificationReasons,
            Entity::ADDITIONAL_DETAILS    => $newAdditionalDetails,
            Merchant\Constants::NC_COUNT  => $needsClarificationCount
        ];

    }

    private function getPartnerDetails(array $partnerVerification, Activation\Entity $partnerActivation, array $rejectionReasons)
    {
        $partnerDetails = [];

        $partnerDetails = array_merge($partnerDetails, $partnerActivation->toArrayPublic());

        $partnerDetails = array_merge($partnerDetails, $partnerVerification);

        $partnerDetails = array_merge($partnerDetails, $rejectionReasons);

        return $partnerDetails;
    }

    /**
     * This function would return the applicable activation status based on the partner KYC verification
     * If all the requirements are verified, partner gets auto activated, if not will be sent to under_review
     * @param Entity $merchantDetails
     *
     * @return string
     */
    public function getApplicablePartnerActivationStatus(Entity $merchantDetails)
    {
        $isAutoKycDone = $this->isPartnerKycDone($merchantDetails);

        if($isAutoKycDone === true)
        {
            return Activation\Constants::ACTIVATED;
        }

        return Activation\Constants::UNDER_REVIEW;
    }

    /**
     * This function would validate the partner requirements are validated or not and returns a boolean flag accordingly
     *
     * @param $merchantDetails
     *
     * @return bool
     */
    public function isPartnerKycDone($merchantDetails)
    {
        $conditions = AutoKyc\Constants::PARTNER_KYC_VERIFICATION_CONDITIONS;

        return (new Parser)->parse($conditions, function ($key, $condition) use ($merchantDetails){

            $entity = $condition[AutoKyc\Constants::ENTITY];
            $in = $condition[AutoKyc\Constants::IN];

            switch ($entity)
            {
                case E::MERCHANT_DETAIL:
                    return in_array($merchantDetails->getAttribute($key), $in, true);
                case E::STAKEHOLDER:
                    return $this->verifyStakeHolderCondition($merchantDetails, $key, $in);
            }
        });
    }
}
