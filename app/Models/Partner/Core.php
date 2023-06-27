<?php

namespace RZP\Models\Partner;

use App;
use Razorpay\OAuth;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Exception;
use RZP\Trace\Tracer;
use Illuminate\Support\Str;
use RZP\Jobs\PartnerMigrationAuditJob;
use RZP\Jobs\BulkMigrateResellerToAggregatorJob;
use RZP\Models\Base\PublicCollection;
use RZP\Jobs\MigrateResellerToPurePlatformPartnerJob;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApplicationsEntity;
use Razorpay\OAuth\Application as OAuthApp;
use RZP\Models\User\Role;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant as Merchant;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Error\ErrorCode;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Metric;
use RZP\Models\Partner\Metric as PartnerMetrics;
use RZP\Models\Merchant\Detail;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\AutoKyc;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Constants;
use RZP\Models\Partner\Activation;
use RZP\lib\ConditionParser\Parser;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Pricing\Calculator\Tax\IN\Utils as TaxUtils;
use RZP\Exception\BadRequestException;
use RZP\Jobs\PartnerActivationMigration;
use RZP\Models\Merchant\Detail\ValidationFields;
use RZP\Jobs\SendPartnerWeeklyActivationSummary;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Mail\Merchant\PartnerWeeklyActivationSummary;
use RZP\Exception\BadRequestValidationFailureException;
use Throwable;

class Core extends Detail\Core
{
    /**
     * @var OAuth\Application\Repository
     */
    protected $appRepo;

    /**
     * @var Activation\Core
     */
    private $activationCore;

    /**
     * @var Merchant\Core
     */
    protected $merchantCore;

    /**
     * @var Merchant\MerchantApplications\Core
     */
    private $merchantAppCore;

    /**
     * Elfin: Url shortening service
     */
    protected $elfin;

    public function __construct()
    {
        parent::__construct();

        $this->appRepo          = new OAuth\Application\Repository;

        $this->activationCore   = new Activation\Core;

        $this->merchantCore     = new Merchant\Core();

        $this->merchantAppCore  = new Merchant\MerchantApplications\Core();

        $this->elfin            = $this->app['elfin'];
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

    public function isFullyManagedSubMerchant(Merchant\Entity $subMerchant): bool
    {
        $fullyManagedSubMerchant = false;

        $partner = $subMerchant->getNonPurePlatformPartner();

        if (empty($partner) === false && $partner->getPartnerType() === Merchant\Constants::FULLY_MANAGED)
        {
            $fullyManagedSubMerchant = true;
        }

        return $fullyManagedSubMerchant;
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
        //
        // - Block sms notifications to linked accounts always
        //
        if($merchant->isLinkedAccount() === true)
        {
            return true;
        }
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
        return (($merchant->isFeatureEnabled((FeatureConstants::BLOCK_ONBOARDING_SMS) === true)
                 or ($partner->isFeatureEnabled(FeatureConstants::BLOCK_ONBOARDING_SMS) === true)));
    }

    /**
     * @param string $merchantId
     *
     * @return bool
     */
    public function isSubMerchantNotificationBlocked(string $merchantId): bool
    {
        //
        // - skip notifications to linked accounts always
        //
        $merchant = $this->repo->merchant->find($merchantId);

        if($merchant->isLinkedAccount() === true)
        {
            return true;
        }
        $partners = (new Merchant\Core())->fetchAffiliatedPartners($merchantId);

        $partner = $partners->first();

        if ($partner === null)
        {
            return false;
        }

        return ($partner->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true);
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
     *
     * @return mixed
     * @throws LogicException
     * @throws \Throwable
     */
    public function processPartnerActivation(array $input, Detail\Entity $merchantDetails, Merchant\Entity $merchant)
    {
        return $this->mutex->acquireAndRelease(
            $merchant->getId(),
            function() use ($input, $merchantDetails, $merchant) {

                return $this->repo->transactionOnLiveAndTest(function() use (
                    $input,
                    $merchantDetails,
                    $merchant
                ) {
                    $partnerActivation = $this->getPartnerActivation($merchant);

                    $this->repo->partner_activation->lockForUpdate($merchant->getId());

                    $kycClarificationReasons = $this->getUpdatedPartnerKycClarificationReasons($input, $merchant->getId());

                    if (empty($kycClarificationReasons) === false)
                    {
                        $partnerActivation->setKycClarificationReasons($kycClarificationReasons);

                        $this->repo->saveOrFail($partnerActivation);
                    }

                    $oldPartnerActivationStatus = $partnerActivation->getActivationStatus();

                    $response = $this->createPartnerResponse($merchantDetails);

                    if ($this->canSubmit($input, $response[E::PARTNER_ACTIVATION]) === true)
                    {
                        $this->trace->count(PartnerMetrics::PARTNERS_KYC_SUBMITTED_TOTAL);

                        $this->submitPartnerActivationForm($merchant, $merchantDetails,$partnerActivation,$input);

                        $response = $this->createPartnerResponse($merchantDetails);

                        $newPartnerActivationStatus = $response[E::PARTNER_ACTIVATION][Activation\Entity::ACTIVATION_STATUS];

                        if($this->isNcResponded($oldPartnerActivationStatus, $newPartnerActivationStatus))
                        {
                            $this->triggerActivationWorkflowForNCResponded($merchant, $merchantDetails, $partnerActivation);
                        }
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
     *
     * @param Merchant\Entity   $merchant
     * @param Entity            $merchantDetails
     * @param Activation\Entity $partnerActivation
     * @param array|null        $input
     * @param string            $source
     *
     * @return array
     * @throws Exception\InvalidPermissionException
     * @throws LogicException
     * @throws \Throwable
     */
    public function submitPartnerActivationForm(Merchant\Entity $merchant, Entity $merchantDetails,
                                                Activation\Entity $partnerActivation, ?array $input, string $source = Constants::PARTNER)
    {
        $activationStatus = $this->getApplicablePartnerActivationStatus($merchantDetails, $partnerActivation);

        $this->markPartnerKycSubmittedAndLock($partnerActivation);

        if ($source === Constants::PARTNER)
        {
            $this->attemptPennyTesting($merchantDetails, $merchant,false,$input);

            $this->triggerValidationRequests($merchant, $merchantDetails);
        }

        $input = [Activation\Entity::ACTIVATION_STATUS => $activationStatus];

        try
        {

            if ($source === Constants::MERCHANT) {
                $this->app['workflow']
                    ->setPermission(Permission\Name::EDIT_ACTIVATE_PARTNER)
                    ->setRouteName(Activation\Constants::ACTIVATION_ROUTE_NAME)
                    ->setController(Activation\Constants::PARTNER_CONTROLLER)
                    ->setRouteParams(['id' => $merchant->getId()])
                    ->setInput([\RZP\Models\Partner\Activation\Entity::ACTIVATION_STATUS => $input[Entity::ACTIVATION_STATUS]]);
            }

            $this->activationCore->updatePartnerActivationStatus($merchant, $partnerActivation, $merchant, $input);
        }
        catch (Exception\EarlyWorkflowResponse $e)
        {
            $this->trace->info(TraceCode::PARTNER_ACTIVATION_SUBMITTED,
                               [
                                   'merchant_id' => $merchant->getId()
                               ]);

            $workflowActionData = json_decode($e->getMessage(), true);
            $this->app['workflow']->saveActionIfTransactionFailed($workflowActionData);
        }
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
            $partnerActivation = $this->activationCore->createOrFetchPartnerActivationForMerchant($merchant, false);
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
     * This function would fetch the updated kyc clarification reason for each field that has been added.
     * Marks newer clarification reasons as current clarification reason (i.e. latest)
     *
     * @param array $input
     * @param string $merchantId
     * @param string|null $source
     *
     * @return array|mixed
     */
    public function getUpdatedPartnerKycClarificationReasons(array $input, string $merchantId, string $source = null)
    {
        $partnerActivation         = $this->repo->partner_activation->findOrFailPublic($merchantId);
        $existingKycClarifications = $partnerActivation->getKycClarificationReasons() ?? [];
        $existingReasons           = $existingKycClarifications[Entity::CLARIFICATION_REASONS] ?? null;
        $existingAdditionalDetails = $existingKycClarifications[Entity::ADDITIONAL_DETAILS] ?? null;

        $newKycClarifications      = $input[Entity::KYC_CLARIFICATION_REASONS] ?? [];
        $newAdditionalDetails      = $newKycClarifications[Entity::ADDITIONAL_DETAILS] ?? null;
        $newReasons                = $newKycClarifications[Entity::CLARIFICATION_REASONS] ?? null;

        if ((empty($newReasons) === true) and
            (empty($newAdditionalDetails) === true))
        {
            return $existingKycClarifications;
        }

        $statusChangeLogs = $partnerActivation->getActivationStatusChangeLog();

        $ncCount = $this->getStatusChangeCount($statusChangeLogs, Activation\Constants::UNDER_REVIEW);
        $clarificationReasons = $this->getClarificationReasons($existingReasons, $newReasons, $ncCount, $source);
        $additionalDetails = $this->getClarificationReasons($existingAdditionalDetails, $newAdditionalDetails, $ncCount, $source);

        return [
            Entity::CLARIFICATION_REASONS => $clarificationReasons,
            Entity::ADDITIONAL_DETAILS    => $additionalDetails,
            Merchant\Constants::NC_COUNT  => $ncCount
        ];
    }

    public function fetchCommonFieldsFromMerchantKycClarificationReasons(array $input, Merchant\Entity $merchant): ?array
    {
        $kycClarifications      = $input[Entity::KYC_CLARIFICATION_REASONS] ?? [];
        $clarificationReasons   = $kycClarifications[Entity::CLARIFICATION_REASONS] ?? null;

        if (empty($clarificationReasons) === true)
        {
            return null;
        }

        $merchantDetail = $merchant->merchantDetail;

        $commonFields = Detail\Constants::COMMON_FIELDS_WITH_PARTNER_ACTIVATION[Constants::DEFAULT];

        if (isset(Detail\Constants::COMMON_FIELDS_WITH_PARTNER_ACTIVATION[$merchantDetail->getBusinessType()]) === true)
        {
            $commonFields = Detail\Constants::COMMON_FIELDS_WITH_PARTNER_ACTIVATION[$merchantDetail->getBusinessType()];
        }

        $partnerClarificationReasons = [];

        foreach ($clarificationReasons as $key => $values)
        {
            if (in_array($key, $commonFields, true) === true)
            {
                $commonFieldValues = $this->fetchValuesFromCommonFieldBasedOnSender($values);

                if (empty($commonFieldValues) === false)
                {
                    $partnerClarificationReasons[$key] = $commonFieldValues;
                }
            }
        }

        return empty($partnerClarificationReasons) ? null : [Entity::CLARIFICATION_REASONS => $partnerClarificationReasons];
    }

    private function fetchValuesFromCommonFieldBasedOnSender(array $values): ?array
    {
        if ($this->getSender(null) === E::MERCHANT)
        {
            return $values;
        }
        else if ($this->getSender(null) === E::ADMIN)
        {
            $currentValues = [];

            foreach ($values as $value)
            {
                if ((isset($value[Constants::IS_CURRENT]) === true) and ($value[Constants::IS_CURRENT] === true))
                {
                    array_push($currentValues, $value);
                }
            }

            return $currentValues;
        }

        return null;
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
     *
     * @param Entity $merchantDetails
     * @return string
     */
    public function getApplicablePartnerActivationStatus(Entity $merchantDetails, Activation\Entity $partnerActivation): string
    {
        $isAutoKycDone = $this->isPartnerKycDone($merchantDetails);

        $partnerActivationStatus = $partnerActivation->getActivationStatus();

        if (($isAutoKycDone === true) and (empty($partnerActivationStatus) or ($partnerActivationStatus === Detail\Status::UNDER_REVIEW)))
        {
            return Activation\Constants::ACTIVATED;
        }

        return Activation\Constants::UNDER_REVIEW;
    }

    /**
     * This function would validate the partner requirements are validated or not and returns a boolean flag accordingly
     *
     * @param Entity $merchantDetails
     *
     * @return bool
     */
    public function isPartnerKycDone(Detail\Entity $merchantDetails): bool
    {
        $businessType = $merchantDetails->getBusinessType();

        if (empty($businessType) === true)
        {
            return false;
        }

        $conditions = AutoKyc\Constants::PARTNER_KYC_VERIFICATION_CONDITIONS[Constants::DEFAULT];

        if (isset(AutoKyc\Constants::PARTNER_KYC_VERIFICATION_CONDITIONS[$businessType]) === true)
        {
            $conditions = AutoKyc\Constants::PARTNER_KYC_VERIFICATION_CONDITIONS[$businessType];
        }

        return (new Parser)->parse($conditions, function ($key, $condition) use ($merchantDetails)
        {
            $entity = $condition[AutoKyc\Constants::ENTITY];
            $in = $condition[AutoKyc\Constants::IN];

            // since GST is optional for partner activation, if it is not provided in the input then its verification
            // status should be null
            if (($key === Entity::GSTIN_VERIFICATION_STATUS) and (empty($merchantDetails->getGstin()) === true))
            {
                $in = [null];
            }

            switch ($entity)
            {
                case E::MERCHANT_DETAIL:
                    return $this->verifyMerchantDetailCondition($merchantDetails, $key, $in);
                case E::STAKEHOLDER:
                    return $this->verifyStakeHolderCondition($merchantDetails, $key, $in);
                case E::MERCHANT_VERIFICATION_DETAIL:
                    return $this->verifyBusinessVerificationCondition($merchantDetails, $key, $in);
            }
        });
    }

    private function triggerActivationWorkflowForNCResponded(Merchant\Entity $merchant, Detail\Entity $merchantDetails, Activation\Entity $partnerActivation)
    {
        $statusChangeLogs = $partnerActivation->getActivationStatusChangeLog();

        // agent who marked NC will be the maker of activation workflow
        $maker = $this->getNcMarkedAgent($statusChangeLogs);

        if (empty($maker))
        {
            return;
        }

        $input = [Entity::ACTIVATION_STATUS => Activation\Constants::ACTIVATED];

        // The reason routeName and Controller is set here because
        // the workflow being triggered is associated with the different route.
        $this->app['workflow']
            ->setPermission(Permission\Name::EDIT_ACTIVATE_PARTNER)
            ->setRouteName(Activation\Constants::ACTIVATION_ROUTE_NAME)
            ->setController(Activation\Constants::PARTNER_CONTROLLER)
            ->setWorkflowMaker($maker)
            ->setMakerFromAuth(false)
            ->setRouteParams([Entity::ID => $merchant->getId()])
            ->setInput($input);

        try
        {
            $this->activationCore->updatePartnerActivationStatus($merchant, $partnerActivation, $maker, $input);
        }
        catch (Exception\EarlyWorkflowResponse $e)
        {
            // Catching exception because we do not want to abort the code flow
            $workflowActionData = json_decode($e->getMessage(), true);
            $this->app['workflow']->saveActionIfTransactionFailed($workflowActionData);
        }
    }

    public function dispatchPartnerWeeklyActivationSummaryMails(?int $limit, ?string $afterId, $mock) : array
    {
        $numBatches     = 0;
        $partnerCount   = 0;
        $dispatchedIds  = [];
        $pageSize       = PartnerConstants::WEEKLY_ACTIVATION_SUMMARY_JOB_PAGE_SIZE;
        $batchSize      = PartnerConstants::WEEKLY_ACTIVATION_SUMMARY_JOB_BATCH_SIZE;
        $limit = $limit ?? PartnerConstants::WEEKLY_ACTIVATION_SUMMARY_PARTNER_LIMIT;

        $this->trace->info(TraceCode::WEEKLY_ACTIVATION_SUMMARY_DISPATCH_START,
        [
            'limit' => $limit,
            'afterId' => $afterId,
            'mock' => $mock,
        ]);

        while ($partnerCount < $limit)
        {
            $aggregatorPartners = $this->repo->merchant->fetchAggregatorPartners($pageSize, $afterId);

            if ($aggregatorPartners->isEmpty() === true)
            {
                break;
            }

            $afterId = $aggregatorPartners->last()->getId();

            $aggregatorPartners = $aggregatorPartners->getIds();

            $merchantIdsChunks = array_chunk($aggregatorPartners, $batchSize);

            foreach ($merchantIdsChunks as $merchantBatch)
            {
                if($partnerCount >= $limit)
                    break;

                $leftPartnerCount = $limit - $partnerCount;
                if($leftPartnerCount < $batchSize)
                    $merchantBatch = array_slice($merchantBatch, 0, $leftPartnerCount);

                if($mock === false)
                    SendPartnerWeeklyActivationSummary::dispatch($this->mode, $merchantBatch);

                $dispatchedIds = array_merge($dispatchedIds, $merchantBatch);
                $partnerCount += count($merchantBatch);
                $numBatches++;
            }
        }

        $resp = [
            'mode'          => $this->mode,
            'numBatches'    => $numBatches,
            'mock'          => $mock,
            'limit'         => $limit,
            'afterId'       => $afterId,
            'partnerCount'  => $partnerCount,
            'dispatchedIds' => $dispatchedIds
        ];

        $this->trace->info(TraceCode::WEEKLY_ACTIVATION_SUMMARY_DISPATCH_END, $resp);

        return $resp;
    }

    public function getPayloadForPartnerWeeklyActivationSummaryEmail(Merchant\Entity $partnerMerchant, array $filteredMerchantIds) : array
    {
        $merchantCountCap    = PartnerConstants::WEEKLY_ACTIVATION_SUMMARY_MERCHANT_COUNT_CAP;

        $countKYCNotInitiatedInTwoMonths = $this->repo->merchant_detail->countSubmerchantsWithKYCNotInitiatedInPastDays($partnerMerchant->getId(), 60);

        $isMerchantCountCapped = count($filteredMerchantIds) >= $merchantCountCap;
        if ($isMerchantCountCapped)
        {
            $filteredMerchantIds = array_slice($filteredMerchantIds, 0, $merchantCountCap);
        }

        $submerchants = $this->repo->merchant->findMany($filteredMerchantIds);

        $clarificationCore    = new Detail\NeedsClarification\Core();
        $activationStatusRows = [];
        foreach ($submerchants as $submerchant)
        {
            $activationStatus = $submerchant->merchantDetail->getActivationStatus();
            $activationStatusLabel = PartnerConstants::$subMActivationStatusLabels[$activationStatus];

            if (is_null($activationStatus))
            {
                continue;
            }

            $clarificationReasons = $clarificationCore->getFormattedKycClarificationReasons(
                $submerchant->merchantDetail->getKycClarificationReasons()
            );

            $activationStatusRows[$submerchant->getId()] = [
                'merchant_id'             => $submerchant->getId(),
                'merchant_name'           => $submerchant->getName(),
                'activation_status'       => $activationStatus,
                'activation_status_label' => $activationStatusLabel,
                'clarification_reasons'   => $clarificationReasons
            ];
        };

        $data = [
            'partner_email'                   => $partnerMerchant->getEmail(),
            'activationStatusRows'            => $activationStatusRows,
            'countKYCNotInitiatedInTwoMonths' => $countKYCNotInitiatedInTwoMonths,
            'isMerchantCountCapped'           => $isMerchantCountCapped
        ];

        return $data;
    }

    public function getSubmerchantIdsForWeeklyActivationSummaryEmail(string $partnerMerchantId): array
    {
        $merchantCountCap    = PartnerConstants::WEEKLY_ACTIVATION_SUMMARY_MERCHANT_COUNT_CAP;

        $merchantIdsInTerminalStateInSevenDays = $this->repo->merchant->getSubmerchantIdsInTerminalStateInPastDays($partnerMerchantId, 7, $merchantCountCap);

        $merchantIdsInstantlyActivatedOrNC     = $this->repo->merchant_detail->getSubmerchantIdsByActivationStatus($partnerMerchantId, [Detail\Status::INSTANTLY_ACTIVATED, Detail\Status::NEEDS_CLARIFICATION], $merchantCountCap);

        $merchantIdsUnderReviewInSevenDays     = $this->repo->merchant_detail->getSubmerchantIdsWithKYCSubmittedUnderReviewInPastDays($partnerMerchantId, 7, $merchantCountCap);

        $merchantIds = array_merge($merchantIdsInTerminalStateInSevenDays, $merchantIdsInstantlyActivatedOrNC, $merchantIdsUnderReviewInSevenDays);

        $merchantIds = array_unique($merchantIds);

        return $merchantIds;
    }

    /**
     * @param $partnerMerchantId
     */
    public function sendPartnerWeeklyActivationSummaryEmails(string $partnerMerchantId): void
    {
        $this->trace->info(TraceCode::WEEKLY_ACTIVATION_SUMMARY_START,
        [
            'partner_merchant_id' => $partnerMerchantId,
        ]);

        $partnerMerchant = $this->repo->merchant->findorFailPublic($partnerMerchantId);

        $notificationBlocked = $partnerMerchant->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM);

        if ($notificationBlocked === true || $partnerMerchant->getEmail() === null)
        {
            return;
        }

        $filteredMerchantIds = $this->getSubmerchantIdsForWeeklyActivationSummaryEmail($partnerMerchantId);

        if(count($filteredMerchantIds) === 0){
            return;
        }

        $data = $this->getPayloadForPartnerWeeklyActivationSummaryEmail($partnerMerchant, $filteredMerchantIds);

        $org = $partnerMerchant->org ?: $this->repo->org->getRazorpayOrg();

        $email = new PartnerWeeklyActivationSummary($data, $org->toArray());

        Mail::queue($email);

        $this->trace->info(TraceCode::WEEKLY_ACTIVATION_SUMMARY_END,
        [
            'partner_merchant_id' => $partnerMerchantId,
            'filtered_merchant_ids' => $filteredMerchantIds
        ]);
    }

    /**
     * Bulk migrates reseller partners to aggregator partners in bulk via running jobs in batch
     *
     * @param   $input  array[
     *                          'data' => [ 'merchant_id' => string, 'new_auth_create' => bool ],
     *                          'batch_size' => Int
     *                      ]       An associative array containing data to be set in
     *                              the input instance variable that is required to run the job in batches
     *
     * @return void
     */
    public function bulkMigrateResellerToAggregatorPartner(array $input)
    {
        $traceInfo = ['params' => $input];

        $this->trace->info(TraceCode::BULK_MIGRATE_RESELLER_TO_AGGREGATOR_REQUEST, $traceInfo);

        $batches = array_chunk($input['data'], $input['batch_size']);
        $actorDetails = $this->getActorDetails();
        foreach ($batches as $batch)
        {
            BulkMigrateResellerToAggregatorJob::dispatch($batch,$actorDetails);
        }

        $this->trace->info(TraceCode::BULK_MIGRATE_RESELLER_TO_AGGREGATOR_SUCCESS, $traceInfo);
    }

    /**
     * Acquires mutex lock on reseller partner's merchantID and migrates to aggregator partner
     *
     * @param array $input [ "merchant_id" => string, "new_auth_create" => bool ]
     * @param array $actorDetails details of the user who made the migration.
     *
     * @return  bool
     * @throws LogicException It will throw an error when updating of partner mapping fails.
     * @throws Throwable It will throw an error when updating of partner mapping fails.
     */
    public function migrateResellerToAggregatorPartner(array $input, array $actorDetails = []) : bool
    {
        (new Validator())->validateInput('resellerToAggregatorMigration', $input);

        $merchantId = $input['merchant_id'];
        $newAuthCreate = $input['new_auth_create'];
        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Constants::RESELLER_TO_AGGREGATOR_UPDATE.$merchantId;

        if(empty($actorDetails) == true)
        {
            $actorDetails = $this->getActorDetails();
        }

        return $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchantId, $newAuthCreate, $actorDetails)
            {
                return $this->updateResellerToAggregator($merchantId, $newAuthCreate, $actorDetails);
            },
            Constants::RESELLER_TO_AGGREGATOR_UPDATE_LOCK_TIME_OUT,
            ErrorCode::BAD_REQUEST_RESELLER_TO_AGGREGATOR_MIGRATION_IN_PROGRESS
        );
    }

    /**
     * Validates partner's existing details and creates supporting entities as required
     *
     * @param string $merchantId    The partner.
     * @param bool   $newAuthCreate Whether to use new auth or old auth of partner.
     * @param array  $actorDetails  details of the user who made the migration.
     *
     * @return  bool
     *
     * @throws LogicException
     * @throws Throwable
     */
    private function updateResellerToAggregator(string $merchantId, bool $newAuthCreate, array $actorDetails) : bool
    {
        $merchant = $this->fetchResellerPartner(
            $merchantId,
            TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_INVALID_PARTNER,
            Metric::RESELLER_TO_AGGREGATOR_MIGRATION_FAILURE
        );
        if ($merchant === null) {
            return false;
        }
        $oldPartnerType = $merchant->getPartnerType();

        $result = null;
        if ($newAuthCreate)
        {
            $this->trace->info(TraceCode::MIGRATE_RESELLER_TO_AGGREGATOR_REQUEST_WITH_NEW_AUTH);
            $result = $this->validateAndCreateSupportingEntitiesWithNewAuth($merchant);
        }
        else
        {
            $this->trace->info(TraceCode::MIGRATE_RESELLER_TO_AGGREGATOR_REQUEST_WITH_OLD_AUTH);
            $result = $this->validateAndCreateSupportingEntitiesWithOldAuth($merchant);
        }

        if ($result === true)
        {
            $this->trace->info(TraceCode::MIGRATE_RESELLER_TO_AGGREGATOR_SUCCESS, ['merchant_id' => $merchant->getId()]);
            $this->trace->count(Metric::RESELLER_TO_AGGREGATOR_MIGRATION_SUCCESS, ['newAuthCreate' => $newAuthCreate]);
            PartnerMigrationAuditJob::dispatch($merchantId, $actorDetails, $oldPartnerType);
        }
        else
        {
            $this->trace->info(
                TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_ERROR,
                ['merchant_id' => $merchant->getId()]
            );
        }
        return $result;
    }

    protected function fetchResellerPartner($merchantId, $traceCode, $metricCode): ?Merchant\Entity
    {
        $merchant = $this->repo->merchant->find($merchantId);
        if ($merchant === null || $merchant->isResellerPartner() === false)
        {
            $this->trace->info($traceCode, ['merchant_id' => $merchantId]);
            $this->trace->count($metricCode, [ 'code' => $traceCode ]);

            return null;
        }

        return $merchant;
    }

    public function auditPartnerMigration( string $merchantId, array $actorDetails,string $oldPartnerType)
    {
        $partner = $this->repo->merchant->findOrFail($merchantId);

        $params = [
            'partner_id'       => $partner->getId(),
            'status'           => "migrated",
            'old_partner_type' => $oldPartnerType,
            'new_partner_type' => $partner->getPartnerType(),
            'audit_log'        => $actorDetails
        ];

        $partnershipsResponse = $this->app->partnerships->createPartnerMigrationAudit($params);

        if($partnershipsResponse['status_code'] == 200)
        {
            $this->trace->count(Metric::PARTNER_MIGRATION_REQUEST_CREATED);
            $this->trace->info(TraceCode::PRTS_PARTNER_MIGRATION_REQUEST_SUCCESS, ['merchant_id' => $partner->getId()]);
        }
        else
        {
            $this->trace->error(TraceCode::PRTS_PARTNER_MIGRATION_REQUEST_ERROR, $partnershipsResponse['response']);
            throw new Exception\ServerErrorException(
            'Error completing the request',
            ErrorCode::SERVER_ERROR_PARTNERSHIPS_FAILURE);
        }
    }

    /**
     * Validates existing entities on live and test DB,
     * and creates supporting entities to migrate reseller to aggregator with new Auth.
     * @param   Merchant\Entity     $merchant
     *
     * @return  bool
     *
     * @throws  Exception\LogicException
     * @throws  Throwable
     */
    private function validateAndCreateSupportingEntitiesWithNewAuth(Merchant\Entity $merchant) : bool
    {
        try
        {
            $applications = $this->repo->merchant_application->fetchMerchantAppInSyncOrFail($merchant->getId());
            if (count($applications) > 1)
            {
                $this->trace->info(
                    TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_INVALID_APPLICATIONS,
                    [ 'applications' => $applications ]
                );
                return false;
            }
            $existingAppId = $applications[0]->getApplicationId();

            list($defaultConfig, $accessMaps, $subMs) = $this->validateAndFetchPartnerEntities(
                $existingAppId, $merchant
            );

            return $this->createAndUpdateSupportingEntitiesForNewAuth(
                $merchant, $existingAppId, $defaultConfig, $accessMaps, $subMs
            );
        }
        catch (Exception\LogicException $e)
        {
            $this->trace->error(TraceCode::RESELLER_TO_AGGREGATOR_DATA_MISMATCH);
            $this->trace->count(
                Metric::RESELLER_TO_AGGREGATOR_MIGRATION_FAILURE,
                ['code' => TraceCode::RESELLER_TO_AGGREGATOR_DATA_MISMATCH]
            );
            throw $e;
        }
    }

    /**
     * Validates existing entities on live and test DB,
     * and creates supporting entities to migrate reseller to aggregator with old Auth.
     * @param   Merchant\Entity     $merchant
     *
     * @return  bool
     *
     * @throws  LogicException
     * @throws  Throwable
     */
    private function validateAndCreateSupportingEntitiesWithOldAuth(Merchant\Entity $merchant) : bool
    {
        try
        {
            list($existingApps, $deletedApps) = $this->fetchMerchantAppForAggrTurnedReseller(
                $merchant->getId()
            );

            if ((new Validator())->validateMerchantAppForAggrTurnedReseller($existingApps, $deletedApps) === false)
            {
                return false;
            }
            $existingAppIds = $existingApps->pluck(MerchantApplicationsEntity::APPLICATION_ID)->toArray();

            $deletedManagedAppId = $deletedApps->firstWhere(
                MerchantApplicationsEntity::TYPE, MerchantApplicationsEntity::MANAGED
            )->getApplicationId();
            $deletedReferredAppId = $deletedApps->firstWhere(
                MerchantApplicationsEntity::TYPE, MerchantApplicationsEntity::REFERRED
            )->getApplicationId();

            list($defaultConfig, $accessMaps, $subMs) = $this->validateAndFetchPartnerEntities($existingAppIds[0], $merchant);

            return $this->createAndUpdateSupportingEntitiesForOldAuth(
                $merchant, $existingAppIds, [ $deletedManagedAppId, $deletedReferredAppId ],
                $defaultConfig, $accessMaps, $subMs
            );
        }
        catch (Exception\LogicException $e)
        {
            $this->trace->error(TraceCode::RESELLER_TO_AGGREGATOR_DATA_MISMATCH);
            $this->trace->count(
                Metric::RESELLER_TO_AGGREGATOR_MIGRATION_FAILURE,
                ['code' => TraceCode::RESELLER_TO_AGGREGATOR_DATA_MISMATCH]
            );
            throw $e;
        }
    }

    /**
     * Validates partner related entities (partner configs, access maps, and sub-merchants) on live and test DB,
     * and fetches them.
     * @param   string              $existingAppId      the application ID of partner
     * @param   Merchant\Entity     $merchant           the reseller partner's MerchantID
     *
     * @return  array       An associative array containing default partner configs, access maps, and sub-merchants
     *
     * @throws  LogicException
     */
    private function validateAndFetchPartnerEntities(string $existingAppId, Merchant\Entity $merchant) : array
    {
        $configs = $this->repo->partner_config->fetchAllConfigsInSyncOrFail([$existingAppId]);
        $defaultConfig = $this->filterDefaultConfig($configs);
        $accessMaps = $this->repo->merchant_access_map->fetchAccessMapsInSyncOrFail(
            $existingAppId, $merchant->getId()
        );
        $subMs = $this->repo->merchant->getSubMerchantsForPartnerAndAppInSyncOrFail($existingAppId, $merchant->getId());
        $subMUsers = $this->repo->merchant_user->fetchMerchantUsersByMerchantIdsInSyncOrFail(
            $subMs->pluck('id')->toArray(), [Role::OWNER]
        );

        return [ $defaultConfig, $accessMaps, $subMs ];
    }

    /**
     * Fetches merchant applications for reseller partner who was once an Aggregator.
     * @param   string      $merchantId
     *
     * @return  array       An associative array containing existing Applications and the deleted Applications of partner
     * @throws  LogicException
     */
    private function fetchMerchantAppForAggrTurnedReseller(string $merchantId) : array
    {
        $applications = $this->repo->merchant_application->fetchMerchantAppInSyncOrFail(
            $merchantId, [], true
        );

        $existingApplications = $applications->whereNull(MerchantApplicationsEntity::DELETED_AT);
        $deletedApplications = $applications->whereNotNull(MerchantApplicationsEntity::DELETED_AT);

        return [ $existingApplications, $deletedApplications ];
    }

    /**
     * Creates new application for aggregator.
     * Updates partner configs for application.
     * Assigns submerchants dashboard access to aggregator partners.
     * Deletes old OAuth and Merchant application for reseller partner.
     *
     * @param   Merchant\Entity         $partner       the reseller partner
     * @param   string                  $existingAppId  the existing referred application ID
     * @param   PartnerConfig\Entity    $defaultConfig  the default partner config for the referred app
     * @param   PublicCollection        $accessMaps     the existing access maps for sub-merchants
     * @param   PublicCollection        $subMerchants   the sub-merchants of reseller partner
     *
     * @return  bool
     *
     * @throws  LogicException
     * @throws  Throwable
     */
    private function createAndUpdateSupportingEntitiesForNewAuth(
        Merchant\Entity $partner, string $existingAppId, PartnerConfig\Entity $defaultConfig,
        PublicCollection $accessMaps, PublicCollection $subMerchants
    ): bool
    {
        $newManagedAppId = $this->merchantCore->createPartnerApp($partner, [])[OAuthApp\Entity::ID];
        $newReferredAppId = $this->merchantCore->createPartnerApp(
            $partner, [OAuthApp\Entity::NAME => Merchant\Entity::REFERRED_APPLICATION]
        )[OAuthApp\Entity::ID];

        try
        {
            $this->repo->transactionOnLiveAndTest(function () use (
                $partner, $existingAppId, $newManagedAppId, $newReferredAppId, $defaultConfig, $accessMaps, $subMerchants
            )
            {
                $this->updatePartnerEntities(
                    $partner, $existingAppId, $newManagedAppId, $newReferredAppId,
                    $defaultConfig, $accessMaps, $subMerchants, true
                );
                app('authservice')->deleteApplication($existingAppId, $partner->getId());
            });

            $this->trace->info(
                TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_PARTNER_SUCCESS,
                ['merchant_id' => $partner->getId()]
            );
        } catch (Throwable $e)
        {
            app('authservice')->deleteApplication($newManagedAppId, $partner->getId(), false);
            app('authservice')->deleteApplication($newReferredAppId, $partner->getId(), false);

            $this->trace->error(
                TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_ERROR,
                [ 'error' => $e ]
            );
            $this->trace->count(
                Metric::RESELLER_TO_AGGREGATOR_MIGRATION_FAILURE,
                ['code' => TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_ERROR]
            );

            throw $e;
        }
        return true;
    }

    /**
     * Creates and updates the supporting entities for Reseller to Aggregator migration with old auth restoration.
     * - deletes existing Auth service application and merchant application
     * - restores the deleted Auth applications and merchant applications
     * - updates the partner entities
     *
     * @param   Merchant\Entity     $partner       the Reseller partner
     * @param   array               $existingAppIds    the existing Referred merchant application
     * @param   array               $deletedAppIds    the deleted managed and referred merchant applications when reseller was aggregator
     * @param   Config\Entity       $defaultConfig  the default partner config of partner
     * @param   PublicCollection    $accessMaps     the access maps of partner
     * @param   PublicCollection    $subMerchants   the sub-merchants of the reseller partner
     *
     * @return  bool        Returns true when the creation and update of supporting entities for migration is successful.
     * @throws  Throwable   Throws exception if anything fails.
     *                      Also rolls back the auth service changes in catch block.
     */
    private function createAndUpdateSupportingEntitiesForOldAuth(
        Merchant\Entity $partner, array $existingAppIds, array $deletedAppIds,
        PartnerConfig\Entity $defaultConfig, PublicCollection $accessMaps, PublicCollection $subMerchants
    ): bool
    {
        app('authservice')->restoreApplication($partner->getId(), $deletedAppIds, $existingAppIds);

        try
        {
            $this->repo->transactionOnLiveAndTest(function () use (
                $partner, $existingAppIds, $deletedAppIds, $defaultConfig, $accessMaps, $subMerchants
            )
            {
                $this->updatePartnerEntities(
                    $partner, $existingAppIds[0], $deletedAppIds[0], $deletedAppIds[1],
                    $defaultConfig, $accessMaps, $subMerchants, false
                );

                $this->merchantAppCore->deleteMultipleApplications($existingAppIds);
            });

            $this->trace->info(
                TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_PARTNER_SUCCESS,
                ['merchant_id' => $partner->getId()]
            );
        } catch (Throwable $e)
        {
            // This is to restore the Auth Service changes if any DB change fails
            app('authservice')->restoreApplication($partner->getId(), $existingAppIds, $deletedAppIds);

            $this->trace->error(
                TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_ERROR,
                [ 'error' => $e ]
            );
            $this->trace->count(
                Metric::RESELLER_TO_AGGREGATOR_MIGRATION_FAILURE,
                ['code' => TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_ERROR]
            );

            throw $e;
        }
        return true;
    }

    /**
     * Updates the partner entities for migrating aggregator-turned reseller back to aggregator type.
     * @param   Merchant\Entity     $partner                the partner merchant
     * @param   string              $existingAppId          the existing application ID of Reseller partner
     * @param   string              $managedAppId           the managed application ID when partner was Aggregator
     * @param   string              $referredAppId          the referred application ID when partner was Aggregator
     * @param   Config\Entity       $defaultConfig          the default partner config of partner
     * @param   PublicCollection    $accessMaps             the access maps of partner
     * @param   PublicCollection    $subMerchants           the sub-merchants of the partner
     * @param   bool                $createReferredConfig   whether to create referred config for aggregator
     *
     * @return  void
     * @throws  LogicException
     */
    private function updatePartnerEntities(
        Merchant\Entity $partner, string $existingAppId, string $managedAppId, string $referredAppId,
        PartnerConfig\Entity $defaultConfig, PublicCollection $accessMaps, PublicCollection $subMerchants,
        bool $createReferredConfig
    )
    {
        $this->merchantCore->createMerchantApplication(
            $partner, $managedAppId, MerchantApplicationsEntity::MANAGED
        );
        $this->merchantCore->createMerchantApplication(
            $partner, $referredAppId, MerchantApplicationsEntity::REFERRED
        );

        if ($createReferredConfig)
        {
            $this->createPartnerConfigFromExistingConfig($partner, $defaultConfig, $referredAppId);
        }

        $this->trace->info(TraceCode::RESELLER_TO_AGGREGATOR_APPLICATION_CREATED, [
                'old_application_id' => $existingAppId,
                'new_application_ids' => [$managedAppId, $referredAppId]
            ]
        );

        (new PartnerConfig\Core())->updateApplicationsForPartnerConfigs($existingAppId, $managedAppId);
        (new Merchant\AccessMap\Core())->updateApplications($accessMaps, $managedAppId, MerchantApplicationsEntity::MANAGED);
        if (empty($subMerchants) === false)
        {
            $this->assignDashboardAccessForSubmerchants($partner, $subMerchants);
        }

        $partner->setPartnerType(Constants::AGGREGATOR);
        $this->repo->merchant->saveOrFail($partner);
    }

    private function filterDefaultConfig($configs)
    {
        return $configs->where(PartnerConfig\Entity::ENTITY_TYPE, 'application')
            ->whereNull(PartnerConfig\Entity::ORIGIN_ID)
            ->whereNull(PartnerConfig\Entity::ORIGIN_ID)
            ->first();
    }

    /**
     * This function creates partner's MerchantUser entries for subMerchants based on product is Primary or Banking.
     *
     * @param   Merchant\Entity     $partner
     * @param   PublicCollection    $subMerchants
     *
     * @return  void
     */
    private function assignDashboardAccessForSubmerchants(
        Merchant\Entity $partner, PublicCollection $subMerchants
    )
    {
        foreach ($subMerchants as $subMerchant)
        {
            if (
                ($subMerchant->primaryOwner(Product::PRIMARY) !== null) and
                ($this->merchantCore->isPartnerUserAddedToSubMUser(
                    $partner, $subMerchant, Product::PRIMARY, [Role::OWNER]) === false)
            )
            {
                // Attaches partners's user to the submerchant account with owner role
                $this->merchantCore->attachSubMerchantUser(
                    $partner->primaryOwner()->getId(), $subMerchant, Product::PRIMARY
                );
            }
            if (
                ($subMerchant->primaryOwner(Product::BANKING) !== null) and
                ($this->merchantCore->isPartnerUserAddedToSubMUser(
                        $partner, $subMerchant, Product::BANKING, [Role::OWNER, Role::VIEW_ONLY]
                    ) === false)
            )
            {
                // Attaches partners's user to the submerchant Banking account with view_only role
                $this->merchantCore->attachSubMerchantUser(
                    $partner->primaryOwner()->getId(), $subMerchant, Product::BANKING, Role::VIEW_ONLY
                );
            }
        }
    }

    /**
     * This function creates OAuth application and merchant application for given merchant and appType.
     *
     * @param   Merchant\Entity $merchant
     * @param   array           $appInput
     * @param   string          $appType
     *
     * @return  string
     */
    private function createPartnerAndMerchantApplication(Merchant\Entity $merchant, array $appInput, string $appType) : string
    {
        $app = $this->merchantCore->createPartnerApp($merchant, $appInput);

        $this->merchantCore->createMerchantApplication($merchant, $app[OAuthApp\Entity::ID], $appType);

        return $app[OAuthApp\Entity::ID];
    }

    /**
     * This function clones existing config and creates new ones for given appId and merchant.
     *
     * @param   Merchant\Entity         $merchant
     * @param   PartnerConfig\Entity    $existingConfig // Existing config to clone from
     * @param   string                  $appId // ApplicationId to update the partner config
     *
     * @return  void
     */
    private function createPartnerConfigFromExistingConfig(
        Merchant\Entity $merchant, PartnerConfig\Entity $existingConfig, string $appId
    )
    {
        $config = (new PartnerConfig\Core())->getClonedPartnerConfig($existingConfig, []);
        $application = (new OAuthApp\Repository())->findOrFail($appId);
        $this->merchantCore->createPartnerConfig($application, $merchant, $config);
    }

    /**
     * The function will return true when a reseller partner fills merchant KYC form.
     *
     * @param   Merchant\Entity     $merchant   The partner merchant entity
     *
     * @return  bool
     *
     */
    public function isResellerPartnerWithMerchantKyc(Merchant\Entity $merchant) : bool
    {
        $activationStatus     = $merchant->merchantDetail->getActivationStatus();
        $partnerType          = $merchant->getPartnerType();

        if (($partnerType === Merchant\Constants::RESELLER) and (empty($activationStatus) === false))
        {
            return true;
        }
        return false;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function migrateResellerToPurePlatformPartner(array $input): array
    {
        $actorDetails = $this->getActorDetails();

        MigrateResellerToPurePlatformPartnerJob::dispatch($input['merchant_id'],$actorDetails);

        return ['triggered' => 'true', 'input' => $input];
    }

    /**
     * @param array $merchantIds
     * @param array $requiredEntities [merchant,merchant_details,tax_components,partner_activation,commission_balance]
     *
     * @return array
     */
    public function fetchPartnerRelatedEntitiesForPRTS(array $merchantIds, array $requiredEntities): array
    {
        $result    = [];
        $relations = [];
        foreach($requiredEntities as $entity)
        {
            if(in_array($entity, ["tax_components","merchant"], true)  == false)
            {
                $relations[] = Str::camel($entity);
            }
        }
        $merchants =  $this->repo->merchant->findManyWithRelations($merchantIds, $relations);
        foreach ($merchants as $merchant) {
            if (empty($merchant) == false and $merchant->isPartner())
            {
                $result[] = $this->buildPartnershipResponseForMerchant($merchant, $requiredEntities);
            }
        }
        return $result;
    }

    public function buildPartnershipResponseForMerchant(Merchant\Entity $merchant, array $entities): array
    {
        $output =[];
        foreach($entities as $entity) {
            switch ($entity) {
                case "merchant":
                    $output["merchant"] = $this->buildMerchantArray($merchant);
                    break;
                case "partner_activation":
                    $output["partner_activation"] = $this->buildPartnerActivationArray($merchant);
                    break;
                case "commission_balance":
                    $output["commission_balance"]= $this->buildCommissionBalanceArray($merchant);
                    break;
                case "tax_components":
                    $output["tax_components"] = $this->buildTaxComponentArray($merchant);
                    break;
                case "merchant_detail":
                    $output["merchant_details"] = $this->buildMerchantDetailsArray($merchant);
            }
        }
        return $output;
    }

    private function buildMerchantDetailsArray(Merchant\Entity $merchant): array
    {
        $merDetail = $merchant->merchantDetail;
        if ($merDetail == null) {
            return [];
        }
        return [
            Entity::ID        => $merDetail->getContactMobile(),
            Entity::GSTIN             => $merDetail->getGstin(),
            Entity::PROMOTER_PAN      => $merDetail->getPromoterPan(),
            Entity::COMPANY_PAN       => $merDetail->getPan(),
            Entity::ACTIVATION_STATUS => $merDetail->getActivationStatus(),
            PartnerConstants::ADDRESS => $merDetail->getBusinessRegisteredAddressAsText()
        ];
    }

    private function buildTaxComponentArray(Merchant\Entity $merchant): array
    {
        $taxComponent = [];
        $taxes = TaxUtils::getTaxComponents($merchant);
        foreach ($taxes as $name => $rate) {
            $taxComponent[] = PartnerConstants::$taxComponentNameMap[$name];
        }
        return $taxComponent;
    }

    private function buildCommissionBalanceArray(Merchant\Entity $merchant): array
    {
        $commBalance = $merchant->commissionBalance;
        if ($commBalance == null) {
            return [];
        }
        return [ Balance\Entity::BALANCE_ID => $commBalance->getId() ];
    }

    private function buildPartnerActivationArray(Merchant\Entity $merchant): array
    {
        $partnerActivation = $merchant->partnerActivation;
        if ($partnerActivation == null) {
            return [];
        }
        return [Activation\Entity::ACTIVATION_STATUS => $partnerActivation->getActivationStatus() ];
    }

    private function buildMerchantArray(Merchant\Entity $merchant): array
    {
        return [
            Merchant\Entity::ID           => $merchant->getId(),
            Merchant\Entity::NAME         => $merchant->getName(),
            Merchant\Entity::PARTNER_TYPE => $merchant->getPartnerType(),
            Merchant\Entity::CREATED_AT   => $merchant->getCreatedAt(),
            PartnerConstants::COUNTRY     => $merchant->getCountry(),
            Merchant\Entity::EMAIL        => $merchant->getEmail(),
            Merchant\Entity::ORG_ID       => $merchant->getOrgId()
        ];
    }
}
