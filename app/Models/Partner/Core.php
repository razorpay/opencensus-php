<?php

namespace RZP\Models\Partner;

use App;
use Request;
use Throwable;
use Lib\PhoneBook;
use Carbon\Carbon;
use RZP\Exception;
use Razorpay\OAuth;
use RZP\Constants\Mode;
use phpseclib\Crypt\AES;
use RZP\Constants\Product;
use RZP\Http\RequestHeader;
use Illuminate\Support\Str;
use RZP\Constants\Environment;
use RZP\Gateway\Base\AESCrypto;
use RZP\Models\FileStore\Format;
use Illuminate\Http\UploadedFile;
use RZP\Models\Pricing\DefaultPlan;
use RZP\Encryption\AesGcmEncryption;
use Razorpay\OAuth\Client as OAuthClient;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Detail\Status;
use RZP\Jobs\PartnerMigrationAuditJob;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Jobs\BulkMigrateResellerToAggregatorJob;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Jobs\MigratePurePlatformToResellerPartnerJob;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Jobs\MigrateResellerToPurePlatformPartnerJob;
use Razorpay\OAuth\Application as OAuthApp;
use RZP\Models\User\Role;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant as Merchant;
use RZP\Models\DeviceDetail;
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
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Merchant\Constants;
use RZP\Models\Partner\Activation;
use RZP\lib\ConditionParser\Parser;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Feature\Core as FeatureCore;
use RZP\Models\Merchant\Acs\ImplicitJoinHelper;
use RZP\Models\EntityOrigin\Constants as EOConstants;
use RZP\Models\Pricing\Calculator\Tax\IN\Utils as TaxUtils;
use RZP\Exception\BadRequestException;
use RZP\Jobs\PartnerActivationMigration;
use RZP\Models\Merchant\Detail\ValidationFields;
use RZP\Jobs\SendPartnerWeeklyActivationSummary;
use RZP\Models\Workflow\Action as WorkflowAction;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Mail\Merchant\PartnerWeeklyActivationSummary;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Services\Dcs\Configurations\Constants as DcsConfigConst;
use RZP\Models\Merchant\MerchantApplications\Repository as ApplicationRepo;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApplicationsEntity;

class Core extends Detail\Core
{
    use FileHandlerTrait;

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

    /**
     * @param Merchant\Entity $partner
     * @param string          $externalId
     *
     * @return void
     * @throws BadRequestException
     * @throws Exception\BaseException
     */
    public function validateExternalIdForPartnerSubmerchant(Merchant\Entity $partner, string $externalId): void
    {
        $merchantCore = new Merchant\Core;

        $appIds = $merchantCore->getPartnerApplicationIds($partner);

        $this->trace->info(TraceCode::PARTNER_FETCH_SUBMERCHANTS,
                           [
                               'partner_id'  => $partner->getId(),
                               'app_ids'     => $appIds,
                               'external_id' => $externalId,
                           ]);

        if ((new Merchant\Acs\AsvRouter\AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__))
        {
            $merchantIdsForExternalId = $this->repo->merchant->findMerchantIdsByExternalIds($externalId);
            $subMerchantIds = $this->repo->merchant_access_map->filterSubMerchantsIdsMappedToAppId($appIds, $merchantIdsForExternalId);

            if (empty($subMerchantIds) === false)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_DUPLICATE_EXTERNAL_ID,
                    Merchant\Entity::EXTERNAL_ID,
                    [
                        'partner_id' => $partner->getId(),
                        'merchants'  => $subMerchantIds,
                    ]);
            }

            return;
        }

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
     * Submit partner activation form while submitting merchant activation form if applicable
     * Case 1: Partner activation status is -> [under review, activated, rejected] or merchant is not partner
     *       - Do not submit partner activation form
     * Case 2: Partner activation is under needs clarification
     *       - Get KYC clarification reasons for common fields and update partner KYC clarification reasons and then
     *         submit the partner activation form
     * Case 3: Partner activation form is not submitted (null)
     *       - Only submit the partner activation form
     *
     * @param Merchant\Entity $merchant
     * @param array|null      $input
     *
     * @throws \Throwable
     */
    public function submitPartnerActivationFormIfApplicable(Merchant\Entity $merchant, ?array $input)
    {
        try
        {
            $partnerActivation = $this->getPartnerActivation($merchant);

            $partnerActivationStatus = empty($partnerActivation) ? null : $partnerActivation->getActivationStatus();

            $excludedActivationStatus = [Status::ACTIVATED, Status::UNDER_REVIEW, Status::REJECTED];

            if (empty($partnerActivation) or (in_array($partnerActivationStatus, $excludedActivationStatus, true) === true))
            {
                $this->trace->info(TraceCode::PARTNER_ACTIVATION_AUTO_FORM_SUBMIT, [
                    'merchant_id'                => $merchant->getId(),
                    'message'                    => 'Auto submitted partner form skipped',
                    'partner_activation_status'  => $partnerActivationStatus ?? "",
                ]);

                return;
            }

            if ($partnerActivationStatus === Status::NEEDS_CLARIFICATION)
            {
                $merchantDetails = $merchant->merchantDetail;

                $merchantDetails->getValidator()->validatePartnerActivationStatus($merchant);

                $input[DetailEntity::KYC_CLARIFICATION_REASONS] = $this->fetchCommonFieldsFromMerchantKycClarificationReasons(
                    $input, $merchant);
            }
            else
            {
                unset($input[DetailEntity::KYC_CLARIFICATION_REASONS]);
            }

            $kycClarificationReasons = $this->getUpdatedPartnerKycClarificationReasons($input, $merchant->getId());

            if (empty($kycClarificationReasons) === false)
            {
                $partnerActivation->setKycClarificationReasons($kycClarificationReasons);
            }

            $this->submitPartnerActivationForm($merchant, $merchant->merchantDetail, $partnerActivation, $input, Constants::MERCHANT);

            $this->trace->info(TraceCode::PARTNER_ACTIVATION_AUTO_FORM_SUBMIT, [
                'merchant_id'                => $merchant->getId(),
                'message'                    => 'Auto submitted partner form success'
            ]);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::PARTNER_ACTIVATION_AUTO_FORM_SUBMIT_FAILURE, [
                'merchant_id'                => $merchant->getId(),
                'message'                    => 'Auto submitted partner form failed'
            ]);
        }

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

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_FORM_SUBMIT, [
            'merchant_id'                => $merchant->getId(),
            'old_activation_status'      => $partnerActivation->getActivationStatus() ?? "",
            'new_activation_status'      => $activationStatus,
        ]);

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
        $response[E::PARTNER_ACTIVATION]['isAutoKycDone'] = $this->isPartnerKycDone($merchantDetails);
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

        $applicableStatus = Activation\Constants::UNDER_REVIEW;

        $currentPartnerActivationStatus = $partnerActivation->getActivationStatus();

        if (($isAutoKycDone === true) and (empty($currentPartnerActivationStatus) or ($currentPartnerActivationStatus === Detail\Status::UNDER_REVIEW)))
        {
            $applicableStatus = Activation\Constants::ACTIVATED;
        }

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_APPLICABLE_STATUS,
                           ['applicable_status' => $applicableStatus,
                            'auto_kyc_done'     => $isAutoKycDone,
                            'current_status'    => $currentPartnerActivationStatus ?? ""]);

        return $applicableStatus;
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

        $isAutoKycDone =  (new Parser)->parse($conditions, function ($key, $condition) use ($merchantDetails)
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

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_AUTO_KYC,
                           [
                               'is_auto_kyc_done' => $isAutoKycDone,
                               'merchant_id'      => $merchantDetails->getMerchantId(),
                           ]);

        return $isAutoKycDone;

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

        $input = [
            Entity::ACTIVATION_STATUS              => Activation\Constants::ACTIVATED,
            Activation\Constants::TRIGGER_WORKFLOW => true
        ];

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
            $this->trace->info(TraceCode::PARTNER_NC_RESPONDED_WORKFLOW_ERROR,
                               [
                                   'merchant_id' => $merchant->getId(),
                               ]);
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

    public function getPayloadForPartnerWeeklyActivationSummaryEmail(Merchant\Entity $partnerMerchant, array $filteredMerchantIds, string $variant = null) : array
    {
        $merchantCountCap    = PartnerConstants::WEEKLY_ACTIVATION_SUMMARY_MERCHANT_COUNT_CAP;

        $countKYCNotInitiatedInTwoMonths = $this->repo->merchant_detail
            ->countSubmerchantsWithKYCNotInitiatedInPastDays($partnerMerchant->getId(), 60, $variant);

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
            $merchantDetail         = $submerchant->merchantDetail;
            $activationStatus       = $merchantDetail->getActivationStatus();
            $activationStatusLabel  = PartnerConstants::$subMActivationStatusLabels[$activationStatus];

            if (is_null($activationStatus))
            {
                continue;
            }

            $clarificationReasons = $clarificationCore->getFormattedKycClarificationReasons(
                $merchantDetail->getKycClarificationReasons()
            );

            $activationStatusRows[$submerchant->getId()] = [
                'merchant_id'             => $submerchant->getId(),
                'merchant_name'           => $submerchant->getName(),
                'activation_status'       => $activationStatus,
                'activation_status_label' => $activationStatusLabel,
                'clarification_reasons'   => $clarificationReasons
            ];
        };

        return [
            'partner_email'                   => $partnerMerchant->getEmail(),
            'activationStatusRows'            => $activationStatusRows,
            'countKYCNotInitiatedInTwoMonths' => $countKYCNotInitiatedInTwoMonths,
            'isMerchantCountCapped'           => $isMerchantCountCapped
        ];
    }

    public function getSubmerchantIdsForWeeklyActivationSummaryEmail(string $partnerMerchantId, string $variant = null): array
    {
        $merchantCountCap    = PartnerConstants::WEEKLY_ACTIVATION_SUMMARY_MERCHANT_COUNT_CAP;

        $merchantIdsInTerminalStateInSevenDays = $this->repo->merchant->getSubmerchantIdsInTerminalStateInPastDays(
            $partnerMerchantId,
            7,
            $merchantCountCap,
            $variant,
        );
        $merchantIdsInstantlyActivatedOrNC     = $this->repo->merchant_detail->getSubmerchantIdsByActivationStatus(
            $partnerMerchantId,
            [Detail\Status::INSTANTLY_ACTIVATED, Detail\Status::NEEDS_CLARIFICATION],
            $merchantCountCap,
            $variant,
        );
        $merchantIdsUnderReviewInSevenDays     = $this->repo->merchant_detail->getSubmerchantIdsWithKYCSubmittedUnderReviewInPastDays(
            $partnerMerchantId,
            7,
            $merchantCountCap,
            $variant,
        );

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

        $properties     = [
            'id'            => $partnerMerchantId,
            'experiment_id' => $this->app['config']->get('app.partner_weekly_activation_summary_datalake_exp_id'),
        ];
        $response       = $this->app['splitzService']->evaluateRequest($properties);
        $variant        = $response['response']['variant']['name'] ?? null;

        $filteredMerchantIds = $this->getSubmerchantIdsForWeeklyActivationSummaryEmail($partnerMerchantId, $variant);

        if(count($filteredMerchantIds) === 0){
            return;
        }

        $data = $this->getPayloadForPartnerWeeklyActivationSummaryEmail($partnerMerchant, $filteredMerchantIds, $variant);

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

    /**
     * @throws \Exception
     */
    protected function fetchResellerPartner($merchantId, $traceCode, $metricCode): ?Merchant\Entity
    {
        $merchant = $this->repo->merchant->find($merchantId);
        if ($merchant === null || $merchant->isResellerPartner() === false)
        {
            $this->trace->info($traceCode, ['merchant_id' => $merchantId]);
            $this->trace->count($metricCode, [ 'success' => false, 'code' => $traceCode ]);

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
            [$existingAppId], $merchant->getId()
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
            $this->repo->transactionOnLiveAndTestAndAsv(function () use (
                $partner, $existingAppId, $newManagedAppId, $newReferredAppId, $defaultConfig, $accessMaps, $subMerchants
            )
            {
                $this->updatePartnerEntities(
                    $partner, $existingAppId, $newManagedAppId, $newReferredAppId,
                    $defaultConfig, $accessMaps, $subMerchants, true
                );
                app('authservice')->deleteApplication($existingAppId, $partner->getId());
            });

            $this->updateSignupCampaignForPartner($partner);

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
            $this->repo->transactionOnLiveAndTestAndAsv(function () use (
                $partner, $existingAppIds, $deletedAppIds, $defaultConfig, $accessMaps, $subMerchants
            )
            {
                $this->updatePartnerEntities(
                    $partner, $existingAppIds[0], $deletedAppIds[0], $deletedAppIds[1],
                    $defaultConfig, $accessMaps, $subMerchants, false
                );

                $this->merchantAppCore->deleteMultipleApplications($existingAppIds);
            });

            $this->updateSignupCampaignForPartner($partner);

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
            $subMPrimaryOwner = $subMerchant->primaryOwner(Product::PRIMARY);
            $isPartnerUserAddedToSubMUser = $this->merchantCore->isPartnerUserAddedToSubMUser(
                $partner, $subMerchant, Product::PRIMARY, [Role::OWNER]
            );
            if (
                ($subMPrimaryOwner !== null) and
                ($subMPrimaryOwner->getEmail() === $subMerchant->getEmail()) and
                ($isPartnerUserAddedToSubMUser === false)
            )
            {
                // Attaches partners's user to the submerchant account with owner role
                $this->merchantCore->attachSubMerchantUser(
                    $partner->primaryOwner()->getId(), $subMerchant, Product::PRIMARY
                );
            }

            $subMBankingPrimaryOwner = $subMerchant->primaryOwner(Product::BANKING);
            $isPartnerUserAddedToSubMUser = $this->merchantCore->isPartnerUserAddedToSubMUser(
                $partner, $subMerchant, Product::BANKING, [Role::OWNER, Role::VIEW_ONLY]
            );
            if (
                ($subMBankingPrimaryOwner !== null) and
                ($subMBankingPrimaryOwner->getEmail() === $subMerchant->getEmail()) and
                ($isPartnerUserAddedToSubMUser === false)
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

    public function getPartnerDefaultConfig(Merchant\Entity $partner): array
    {
        if($partner->isPurePlatformPartner())
        {
            $defaultConfig = (new PartnerConfig\Core)->fetchConfigForPlatformPartner($partner);
            if(empty($defaultConfig) == false)
            {
                $defaultConfig                                      =  $defaultConfig->toBuildArray();
                $defaultConfig[PartnerConfig\Constants::PARTNER_ID] = $partner->getId();
                return $defaultConfig;
            }
        }
        $env = ($this->app->isProduction()) ? Environment::PRODUCTION : Environment::DEV;
        $defaultPlanId = DefaultPlan::DEFAULT_PARTNERS_PRICING_PLANS[
            $partner->getCountry()
        ][ $env ][ DefaultPlan::SUBMERCHANT_PRICING_OF_ONBOARDED_PARTNERS_KEY ];
        $implicitPlanId  = DefaultPlan::DEFAULT_PARTNERS_PRICING_PLANS[
            $partner->getCountry()
        ][ $env ][ DefaultPlan::PARTNER_COMMISSION_PLAN_ID_KEY ];
        return [
            PartnerConfig\Entity::DEFAULT_PLAN_ID       => $defaultPlanId,
            PartnerConfig\Entity::IMPLICIT_PLAN_ID      => $implicitPlanId,
            PartnerConfig\Entity::COMMISSIONS_ENABLED   => true,
            PartnerConfig\Constants::PARTNER_ID         => $partner->getId(),
        ];
    }

    public function deleteWebhooksForApplication(string $ownerId)
    {
        (new Stork('live'))->deleteWebhooksByOwnerId($ownerId);

        (new Stork('test'))->deleteWebhooksByOwnerId($ownerId);
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

        MigrateResellerToPurePlatformPartnerJob::dispatch($input['merchant_id'], $actorDetails);

        return ['triggered' => 'true', 'input' => $input];
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function migratePurePlatformToResellerPartner(array $input): array
    {
        $actorDetails = $this->getActorDetails();

        MigratePurePlatformToResellerPartnerJob::dispatch($input['merchant_id'], $actorDetails);

        return ['triggered' => 'true', 'input' => $input];
    }

    /**
     * checks if the exp to sync partner entities to prts is enabled
     * @param string $appId
     *
     * @return bool
     * @throws \Exception
     */
    public function isPartnerEntitySyncExpEnabled(string $appId) : bool
    {
        $properties = [
            'id'            => $appId,
            'experiment_id' => app('config')->get('app.partner_entities_partnership_service_sync'),
        ];

        return $this->merchantCore->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * @param array $merchantIds
     * @param array $requiredEntities [merchant,merchant_details,tax_components,partner_activation,commission_balance]
     *
     */
    public function fetchPartnerRelatedEntitiesForPRTS(array $merchantIds, array $requiredEntities)
    {
        $result    = [];
        $relations = [];
        foreach($requiredEntities as $entity)
        {
            if(in_array($entity, ["tax_components","merchant", "merchant_detail"], true)  == false)
            {
                $relations[] = Str::camel($entity);
            }
        }
        $this->trace->info(TraceCode::PRTS_MERCHANT_ENTITIES_FETCH, [
            'merchantIds'=> $merchantIds,
            'entities'   => $requiredEntities,
            'relations'  => $relations
        ]);

        if ((new Merchant\Acs\AsvRouter\AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__)) {
            $merchants = $this->repo->merchant->findMerchantsByIds($merchantIds);
            $merchants->load($relations);
        } else
        {
            $merchants =  $this->repo->merchant->findManyWithRelations($merchantIds, $relations);
        }

        foreach ($merchants as $merchant) {
            if (empty($merchant) == false)
            {
                $result[] = $this->buildPartnershipResponseForMerchant($merchant, $requiredEntities);
            }
        }
        return empty($result) ? '' : $result;
    }

    public function updateNcOptOutForPartner(array $input)
    {
        $featureName   = PartnerConstants::NEEDS_CLARIFICATION_OPT_OUT_FEATURE[$input['channel']];
        $partnerId     = $input['partner_id'];
        $subMerchantId = $input['submerchant_id'];

        try
        {
            $dcsConfigService = new DcsConfigService();
            $response =  $dcsConfigService->fetchConfiguration(DcsConfigConst::NcOptOutConfiguration ,$partnerId, [$featureName] , Mode::LIVE);

            if (empty($response[$featureName]) === false and $response[$featureName][$subMerchantId] === true)
            {
                return;
            }

            $featureMap = [
                $subMerchantId => true
            ];

            foreach ($response[$featureName] as $key => $value)
            {
                $featureMap[$key] = $value;
            }

            $map[$featureName] = $featureMap;

            $this->trace->info(TraceCode::NC_OPT_OUT_EDIT_DCS_REQUEST,
                [
                    'feature_map' => $map,
                ]);

            $dcsConfigService->createConfiguration(DcsConfigConst::NcOptOutConfiguration, $partnerId, $map, Mode::LIVE);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::NC_OPT_OUT_DCS_REQUEST_EXCEPTION,
                [
                   'data' => $input,
                ]
            );
        }
    }

    public function isMerchantOptedOutNcNotifications(string $partnerId, string $subMerchantId, string $channel): bool
    {
        $featureName   = PartnerConstants::NEEDS_CLARIFICATION_OPT_OUT_FEATURE[$channel];

        $response = null;

        try
        {
            $dcsConfigService = new DcsConfigService();
            $response =  $dcsConfigService->fetchConfiguration(DcsConfigConst::NcOptOutConfiguration, $partnerId, [$featureName] , Mode::LIVE);


            if (empty($response[$featureName]) === false and $response[$featureName][$subMerchantId] === true)
            {
                return true;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::NC_OPT_OUT_DCS_REQUEST_EXCEPTION,
                [
                    'channel'          => $channel,
                    'partnerId'        => $partnerId,
                    'subMerchantId'    => $subMerchantId,
                ]
            );
        }
        return false;
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

    public function updateSignupCampaignForPartner(Merchant\Entity $partner)
    {
        $primaryOwner = $partner->primaryOwner();
        $existingDeviceDetails = $this->repo->user_device_detail->fetchByMerchantIdAndUserId($partner->getId(), $primaryOwner['id']);
        if(empty($existingDeviceDetails) === true)
        {
            $ddInput = [
                DeviceDetail\Entity::MERCHANT_ID => $partner->getId(),
                DeviceDetail\Entity::USER_ID => $primaryOwner['id'],
                DeviceDetail\Entity::SIGNUP_CAMPAIGN => DeviceDetail\Constants::EASY_ONBOARDING,
            ];

            (new DeviceDetail\Core)->createDeviceDetail($ddInput);
        }
    }


    /**
     * This function is used to add extra properties to the events that we are sending from api monolith to lumberjack/segment
     *
     * @param Merchant\Entity|null $merchant
     *
     * @return array
     */
    public function getPartnerDomainProperties(?Merchant\Entity $merchant): array
    {
        if(empty($merchant) == true)
        {
            return [];
        }

        $partnerDomainProperties = [];

        try
        {
            $partnerDomainProperties['isSubmerchant'] = (new AccessMap\Core())->isSubMerchant($merchant->getId());
            // the following condition would mean that the requester is partner and the ba merchant is subM
            // The below details will be set in BusinessAuth for partner_auth / oauth / proxy_auth with subM header
            if ($this->app['basicauth']->isPartnerImpersonationRequest())
            {
                $partnerDomainProperties['partnerImpersonation'] = true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);
            $this->trace->count(PartnerMetrics::PARTNER_DOMAIN_BUILD_EVENT_PROPERTIES_FAILURE);
        }

        return $partnerDomainProperties;
    }

    private function buildMerchantDetailsArray(Merchant\Entity $merchant)
    {
        $merDetail = $merchant->merchantDetail;
        if ($merDetail == null) {
            return null;
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

    private function buildCommissionBalanceArray(Merchant\Entity $merchant)
    {
        $commBalance = $merchant->commissionBalance;
        if ($commBalance == null) {
            return null;
        }
        return [ Balance\Entity::BALANCE_ID => $commBalance->getId() ];
    }

    private function buildPartnerActivationArray(Merchant\Entity $merchant)
    {
        $partnerActivation = $merchant->partnerActivation;
        if ($partnerActivation == null) {
            return null;
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
            Merchant\Entity::ORG_ID       => $merchant->getOrgId(),
            Merchant\Entity::HOLD_FUNDS   => $merchant->isFundsOnHold(),
        ];
    }

    public function isTransactionIsolationExpEnabledForSubmerchant(string $merchantId, string $eventName) : bool
    {
        $experimentName = PartnerConstants::$transactionIsolationEventToExperimentMap[$eventName];

        $experimentId = $this->app['config']->get($experimentName);

        $partnerIds = $this->repo->merchant_access_map->fetchEntityOwnerIdsForSubmerchant($merchantId, true)->toArray();

        if (empty($partnerIds))
        {
            return false;
        }

        $properties = [];

        foreach ($partnerIds as $partnerId)
        {
            $properties[] = [
                'id'            => $partnerId,
                'experiment_id' => $experimentId
            ];
        }

        try
        {
            $responses = $this->app['splitzService']->bulkCallsToSplitz($properties);

            foreach ($responses as $response)
            {
                $variant = $response['variant']['name'] ?? null;

                if ($variant === 'enable')
                {
                    return true;
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::TRANSACTION_ISOLATION_SPLITZ_ERROR, [
                'message' => $e->getMessage(),
            ]);

            $this->trace->count(PartnerMetrics::TRANSACTION_ISOLATION_SPLITZ_FAILURE);
        }

        return false;
    }

    public function getApplicationDetailsForPayment(string $paymentId)
    {
        $response = [];
        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);

            $entityOrigin = optional($payment)->entityOrigin;

            $origin     = optional($entityOrigin)->origin;
            $originType = optional($origin)->getEntityName();

            if ($originType === EOConstants::APPLICATION)
            {
                $application = (new ApplicationRepo())->fetchMerchantApplicationByAppIdAndType($origin->getId(),
                    MerchantApplicationsEntity::OAUTH);

                if (empty($application) === false)
                {
                    $response =  [
                        'id'            => $origin->getId(),
                        'merchant_id'   => $origin->getMerchantId(),
                        'name'          => $origin->getName(),
                    ];
                }
            }

        }
        catch (\Throwable $e)
        {
            // Should not fail even if the origin extraction from public key is failed.
            $this->trace->critical(TraceCode::FETCH_APP_NAME_FROM_PAYMENT_EXCEPTION,
                [
                    'payment_id'        => $paymentId,
                    'message'           => $e->getMessage(),
                    'stack_trace'       => $e->getTraceAsString(),
                ]
            );
        }
        return $response;
    }

    public function isPOSEnabledForPartner(string $partnerId): bool
    {
        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $this->app['config']->get('app.pos_partnership_experiment_id'),
        ];

        $isExpEnabled = $this->merchantCore->isSplitzExperimentEnable($properties, 'enable');

        $this->trace->info(
            TraceCode::PARTNER_POS_EXPERIMENT,
            [
                "properties" => $properties,
                "enabled"    => $isExpEnabled,
            ]
        );

        return $isExpEnabled;
    }
    public function isPOSEnabled(): bool
    {
        $properties = [
            'experiment_id' => $this->app['config']->get('app.pos_enabled_experiment_id'),
        ];

        $isExpEnabled = $this->merchantCore->isSplitzExperimentEnable($properties, 'variant');

        $this->trace->info(
            TraceCode::POS_ENABLE_EXPERIMENT,
            [
                "properties" => $properties,
                "enabled"    => $isExpEnabled,
            ]
        );

        return $isExpEnabled;
    }

    public function getSubmerchantDetails(array $input): ?array
    {
        $subMerchantDetails = null;

        $contactNumber = $this->getSubMerchantContactUsingOnboardingSignatureIfApplicable($input);

        if($contactNumber !== null)
        {
            $subMerchantDetails["contact_number"] = $contactNumber;
        }

        $this->trace->info(TraceCode::FETCH_SUBMERCHANT_DETAILS_ONBOARDING_PREFILL, [
            'is_contact_details_empty' => empty($subMerchantDetails)
        ]);

        return $subMerchantDetails;
    }

    /**
     * This function takes application Id, client Id & Onboarding signature within $input.
     * It decrypts the onboarding signature and takes out sub-merchant Id. It then fetches its contact number
     * to be passed in response. This feature is meant to Prefill contact number on Phantom dashboard during login.
     *
     * @param array $input
     *
     * @return string|null
     */
    protected function getSubMerchantContactUsingOnboardingSignatureIfApplicable(array $input): ?string
    {
        $onboardingSignature = Request::header(RequestHeader::X_ONBOARDING_SIGNATURE) ?? null;

        /*
         * Below checks :
         * 1. The application_id must be present to check submerchant mapping.
         * 2. client_id must exist to decrypt signature via its secret.
         * 3. $onboardingSignature must exist to actually validate timestamp & get submerchant Id.
         * 4. Fetching sub-merchant details via Onboarding signature is required using Public Auth.
         *
         * */
        if (empty($input[Constants::APPLICATION_ID]) === true
            || empty($input[PartnerConstants::CLIENT_ID]) === true
            || empty($onboardingSignature) === true
            || $this->app['request.ctx']->isDashboardGuest() === false
            || $this->isContactMobilePrefillExpEnabled($input[Constants::APPLICATION_ID]) === false)
        {
            return null;
        }

        try
        {
            $client = (new OAuthClient\Repository)->getClientEntity($input[PartnerConstants::CLIENT_ID]);

            if (empty($client))
            {
                return null;
            }

            $merchantId = $this->getSubmerchantIdFromOnboardingSignature($client->getSecret(), $onboardingSignature);

            $this->trace->info(TraceCode::ONBOARDING_SIGNATURE_VALID_FOR_LOGIN, [
                'application_id' => $input[Constants::APPLICATION_ID],
                'merchant_id'    => $merchantId,
                'step'           => 'Phantom prefill contact number'
            ]);

            if($merchantId !== null)
            {
                $isSubmerchant = (new Merchant\AccessMap\Core())->isMerchantMappedToApplication($merchantId, $input[Constants::APPLICATION_ID]);

                if ($isSubmerchant === true)
                {
                    $subMerchant = $this->repo->merchant->findOrFailPublic($merchantId);

                    $merchantDetail = $subMerchant->merchantDetail;

                    $number = new PhoneBook($merchantDetail->getContactMobile());

                    $phoneNumber = $number->format(PhoneBook::DOMESTIC);

                    $subMerchantUser = $this->repo->user->findByMobile($phoneNumber)->first();

                    if (!empty($subMerchantUser))
                    {
                        return $phoneNumber;
                    }
                }
            }
            return null;
        }
        catch (\Throwable $e) {
            $this->trace->info(TraceCode::ONBOARDING_SIGNATURE_VALIDATION_ERROR,
                [
                    'error' => $e->getMessage(),
                    'step'  => 'Phantom prefill contact number'
                ]);

            return null;
        }
    }

    private function isContactMobilePrefillExpEnabled(String $submerchantId) : bool
    {
        $properties = [
            'id'            => $submerchantId,
            'experiment_id' => $this->app['config']->get('app.phantom_prefill_contact_number_exp_id'),
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }

    public function getSubmerchantIdFromOnboardingSignature(string $clientSecret, string $onboardingSignature)
    {
        if (empty($onboardingSignature) || empty($clientSecret))
        {
            return null;
        }

        $data = $this->decryptSignature($onboardingSignature, $clientSecret);

        if($this->isValidOnboardingSignature($data))
        {
            return $data[PartnerConfig\Constants::SUBMERCHANT_ID];
        }

        return null;
    }

    public function isOnboardingSignatureValid(UserEntity $user, array &$input, bool $checkRateLimiting = true) : bool
    {
        if (!isset($input[PartnerConstants::ONBOARDING_SIGNATURE]) || empty($input[PartnerConstants::ONBOARDING_SIGNATURE]) ||
            !isset($input[PartnerConstants::CLIENT_ID]) || empty($input[PartnerConstants::CLIENT_ID]))
        {
            return false;
        }

        if ($this->isLoginAllowedForUnverifiedPhoneNumbers($user->getId()) === false)
        {
            return false;
        }

        $subMRateLimiter = (new PartnershipsRateLimiter(PartnerConstants::SUBMERCHANT_PREFILL_LOGIN));

        $key = $subMRateLimiter->getRateLimitRedisKey($user->getId());

        $rateLimiterUpdated = $subMRateLimiter->rateLimit($key);

        $partnerToken = $input[PartnerConstants::ONBOARDING_SIGNATURE];

        $client  = (new OAuthClient\Repository)->getClientEntity($input[PartnerConstants::CLIENT_ID]);

        if (empty($client))
        {
            return false;
        }

        $data = $this->decryptSignature($partnerToken, $client->getSecret());

        if ($this->isValidUserData($user, $data))
        {
            $isSubmerchant = (new Merchant\AccessMap\Core())->isMerchantMappedToApplication($data[PartnerConfig\Constants::SUBMERCHANT_ID], $client->getApplicationId());

            if ($isSubmerchant)
            {
                $this->trace->info(TraceCode::ONBOARDING_SIGNATURE_VALID_FOR_LOGIN, [
                    'client_id'      => $input[PartnerConstants::CLIENT_ID],
                    'submerchant_id' => $data[PartnerConfig\Constants::SUBMERCHANT_ID],
                    'rateLimiterUpdated' => $rateLimiterUpdated
                ]);
            }

            return $isSubmerchant;
        }

        unset($input[PartnerConstants::ONBOARDING_SIGNATURE]);
        unset($input[PartnerConstants::CLIENT_ID]);

        return false;
    }

    private function isValidUserData(UserEntity $user, array $data) : bool
    {
        $merchant = $user->getFirstMerchantEntity();

        if (!isset($data[PartnerConfig\Constants::SUBMERCHANT_ID]) ||
            empty($data[PartnerConfig\Constants::SUBMERCHANT_ID]) ||
            $data[PartnerConfig\Constants::SUBMERCHANT_ID] != $merchant->getId())
        {
            return false;
        }

        if (!isset($data[PartnerConstants::TIMESTAMP]) || empty($data[PartnerConstants::TIMESTAMP]))
        {
            return false;
        }

        $currentTimestamp = time();

        $differenceInSeconds = $currentTimestamp - $data[PartnerConstants::TIMESTAMP];

        return $differenceInSeconds <= PartnerConstants::ONBOARDING_SIGNATURE_EXPIRY_IN_SECONDS;
    }

    private function isValidOnboardingSignature(array $data) : bool
    {
        if (empty($data[PartnerConfig\Constants::SUBMERCHANT_ID])
            || empty($data[PartnerConstants::TIMESTAMP]))
        {
            return false;
        }

        $currentTimestamp = time();

        $differenceInSeconds = $currentTimestamp - $data[PartnerConstants::TIMESTAMP];

        return $differenceInSeconds <= PartnerConstants::ONBOARDING_SIGNATURE_EXPIRY_IN_SECONDS;
    }

    private function decryptSignature(String $encryptedData, String $secret) : array
    {
        if (empty($encryptedData) || empty($secret))
        {
            return [];
        }

        try
        {
            $iv = substr($secret, 0, 12);

            $key = substr($secret, 0, 16);

            $combined = hex2bin($encryptedData);

            $ciphertext = substr($combined, 0, -16);

            $tag = substr($combined, -16);

            $decryptedToken = openssl_decrypt($ciphertext, 'aes-128-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

            $data =  json_decode($decryptedToken, true);

            return empty($data) ? [] : $data;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::ONBOARDING_SIGNATURE_VALIDATION_ERROR, ['error' => $e->getMessage()]);
        }

        return [];
    }

    private function isLoginAllowedForUnverifiedPhoneNumbers(String $userId) : bool
    {
        $properties = [
            'id'            => $userId,
            'experiment_id' => $this->app['config']->get('app.submerchant_prefill_login_exp_id'),
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }

    public function autoApproveMerchantActivationCheckerFlow(array $input, array $splitzVariables): array
    {
        (new Validator())->validateInput('autoApproveMerchantActivationSplitzVariables', $splitzVariables);
        (new Validator())->validateInput('autoApproveMerchantActivationInput', $input);

        $filePath = $input['checkers_file'];
        $partnerId = $splitzVariables['partner_id'];
        $limit = $splitzVariables['limit'];

        $rows = $this->parseFile($filePath);

        $mids = array_unique(array_map(function ($row) {
            return $row['merchant_id'];
        }, $rows));
        if (count($mids) > $limit)
        {
            throw new Exception\LogicException(
                "Limit for list of sub-merchant MIDs have breached, current limit: {$limit}"
            );
        }

        $partner = $this->repo->merchant->findOrFailPublic($partnerId);
        $appIds = (new Merchant\MerchantApplications\Core())->getMerchantAppIds(
            $partner->getId(), [MerchantApplicationsEntity::OAUTH]
        );

        $actions = (new WorkflowAction\Core)->fetchOpenActionsOnEntitiesOperationWithPermissionList(
            $mids, 'merchant_detail', [Permission\Name::EDIT_ACTIVATE_MERCHANT]
        )->groupBy(WorkflowAction\Entity::ENTITY_ID);
        $checkerInput = [
            "approved" => 1,
            WorkflowAction\Checker\Constants::APPROVED_WITH_FEEDBACK => 0
        ];

        $success = $failed = [];

        foreach ($mids as $mid)
        {
            try
            {
                $mapping = $this->repo->merchant_access_map->findMerchantAccessMapOnEntityIds(
                    $mid, $appIds, AccessMap\Entity::APPLICATION
                );

                if ($mapping->isEmpty())
                {
                    $this->trace->debug(
                        TraceCode::PARTNER_SUBMERCHANT_NOT_MAPPED,
                        ['sub_merchant_id' => $mid, 'partner_id' => $partnerId]
                    );
                    $failed[] = $mid;
                }
                else
                {
                    if (empty($actions[$mid]))
                    {
                        $this->trace->debug(
                            TraceCode::SUBMERCHANT_ONBOARDING_ACTIVATION_WORKFLOW_NOT_FOUND,
                            ['sub_merchant_id' => $mid, 'partner_id' => $partnerId]
                        );
                        $failed[] = $mid;
                    }
                    else
                    {
                        $workflowAction = $actions[$mid]->first();
                        $this->trace->debug(
                            TraceCode::PARTNER_SUBMERCHANT_ONBOARDING_WORKFLOWS,
                            ['workflow_action' => $workflowAction->getId(), 'mid' => $mid]
                        );

                        $this->resetWorkflowSingleton();

                        $actionId = "w_action_{$workflowAction->getId()}";
                        $response = (new WorkflowAction\Checker\Service())->create($actionId, $checkerInput);
                        $this->trace->debug(
                            TraceCode::PARTNER_SUBMERCHANT_CHECKER_AUTO_APPROVAL_CREATED,
                            ['response' => $response, 'mid' => $mid]
                        );

                        $success[] = $mid;
                    }
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e, Trace::ERROR,
                    TraceCode::PARTNER_SUBMERCHANT_CHECKER_AUTO_APPROVAL_FAILED,
                    ['mid' => $mid]
                );

                $failed[] = $mid;
            }
        }

        $this->trace->debug(
            TraceCode::PARTNER_SUBMERCHANTS_ACTIVATED_IN_BULK,
            ['success' => $success, 'failed' => $failed]
        );
        return [
            'success' => $success, 'failed' => $failed
        ];
    }

    protected function parseFile(UploadedFile $file): array
    {
        $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

        switch ($ext)
        {
            case Format::XLSX:
            case Format::XLS:
                return $this->parseExcelSheets($file);
            case Format::CSV:
                return $this->parseTextFile($file, ',');
            default:
                throw  new Exception\LogicException("Extension not handled: {$ext}");
        }
    }

    private function resetWorkflowSingleton()
    {
        $app = App::getFacadeRoot();
        $app['workflow'] =  new \RZP\Services\Workflow\Service($app);
    }

    protected function getHeadings(): array
    {
        return [Constants::MERCHANT_ID];
    }
}
