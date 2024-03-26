<?php

namespace RZP\Models\Partner;

use Throwable;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Constants\Environment;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Referral;
use RZP\Models\Merchant\Constants;
use RZP\Models\Partner\Activation;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Jobs\CapturePartnershipConsents;
use RZP\Jobs\SubmerchantFirstTransactionEvent;
use RZP\Models\Feature\Service as FeatureService;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Http\Controllers\PartnerPGOSProxyController;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Models\Merchant\Consent\Constants as ConsentConstant;

class Service extends Base\Service
{
    private $merchantCore;

    private $activationCore;

    private $merchantValidator;

    private $partnerActivationValidator;

    protected $partnerPGOSProxyController;

    protected $pgosProxyController;

    public function __construct()
    {
        $this->core = new Core();

        $this->merchantCore = new Merchant\Core();

        $this->activationCore = new Activation\Core();

        $this->merchantValidator = new Merchant\Validator();

        $this->partnerActivationValidator = new Activation\Validator();

        $this->partnerPGOSProxyController = new PartnerPGOSProxyController();

        $this->pgosProxyController = new MerchantOnboardingProxyController();

        parent::__construct();
    }

    /**
     * @throws Throwable
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    public function savePartnerDetailsForActivation(array $input)
    {
        $this->partnerActivationValidator->validatePartnerFormSaveAndSubmit($this->merchant);

        $this->partnerActivationValidator->validateInput('savePartnerActivation', $input);

        $merchantDetail = $this->merchant->merchantDetail;

        $merchantInput = $input;

        unset($merchantInput[Detail\Entity::SUBMIT]);
        unset($merchantInput[Detail\Entity::KYC_CLARIFICATION_REASONS]);
        if (empty($merchantInput) === false)
        {
            $merchantInput[Activation\Constants::PARTNER_KYC_FLOW] = true;

            (new Detail\Core())->saveMerchantDetails($merchantInput, $this->merchant);
        }

        $response = $this->core->processPartnerActivation($input, $merchantDetail, $this->merchant);

        $response[Detail\Constants::LOCK_COMMON_FIELDS] = (new Detail\Core())->fetchCommonFieldsToBeLocked($merchantDetail);

        if( empty($input[DEConstants::CONSENT]) === false)
        {
            $input[DEConstants::IP_ADDRESS ] = $this->app['request']->ip();
            $input[DEConstants::USER_ID]     = $this->app['request']->header(RequestHeader::X_DASHBOARD_USER_ID);
            CapturePartnershipConsents::dispatch($this->mode, $input, $this->merchant->getId(), ConsentConstant::PARTNER_ACTIVATION);
        }

        return $response;
    }

    public function getPartnerActivationDetails()
    {

        $this->merchantValidator->validateIsPartner($this->merchant);

        $merchantDetails = $this->merchant->merchantDetail;

        $response = $this->core->createPartnerResponse($merchantDetails);

        $response[Detail\Constants::LOCK_COMMON_FIELDS] = (new Detail\Core())->fetchCommonFieldsToBeLocked($merchantDetails);

        return $response;
    }

    public function updatePartnerActivationStatus(string $merchantId, array $input)
    {
        $activationCore = new Activation\Core();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->merchantValidator->validateIsPartner($merchant);

        $partnerActivation = $activationCore->createOrFetchPartnerActivationForMerchant($merchant, false);

        $admin = $this->app['basicauth']->getAdmin();

        return $this->activationCore->updatePartnerActivationStatus($merchant, $partnerActivation, $admin, $input);
    }


    public function editPartnerActivationDetails($id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->merchantValidator->validateIsPartner($merchant);

        $this->core->editPartnerActivation($merchant, $input);

        return $this->core->createPartnerResponse($merchant->merchantDetail);
    }

    public function performAction(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::PARTNER_ACTION_DATA,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->merchantValidator->validateIsPartner($merchant);

        $partnerActivation = $this->core->getPartnerActivation($merchant);

        return $this->activationCore->performAction($partnerActivation, $input);
    }

    public function bulkAssignReviewer(array $input): array
    {
        (new Detail\Validator())->validateInput('bulk_assign_reviewer', $input);

        $merchants  = $input[Detail\Entity::MERCHANTS];

        $reviewerId = $input[Detail\Entity::REVIEWER_ID];

        return $this->activationCore->bulkAssignReviewer($reviewerId, $merchants);
    }

    public function sendEventsOfPartnersWithPendingCommissionAndIncompleteKYC(): array
    {

        $partnerIdsWithIncompleteKyc = $this->repo->partner_activation->fetchPartnersWithIncompleteKyc();

        $month = Carbon::now(Timezone::IST)->month;

        foreach($partnerIdsWithIncompleteKyc as $partnerId)
        {
            $partner = $this->repo->merchant->fetchMerchantFromId($partnerId);

            $commissionBalance = $partner->commissionBalance;

            if($commissionBalance !== null and ($commissionBalance->getBalance() > 0))
            {
                $properties = [
                    'partner_id'     =>  $partnerId,
                    'product_group'  =>  $partner[Merchant\Entity::PRODUCT],
                    'month'          =>  $month,
                    'commission'     =>  $commissionBalance->getBalance()
                ];

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $partner, $properties, SegmentEvent::PARTNER_HAVE_COMMISSION);
            }

        }

        return ['success' => 'true'];
    }

    public function sendPartnerWeeklyActivationSummaryEmails(array $input)
    {
        $limit = $input['limit'] ?? null;

        $afterId = $input['afterId'] ?? null;

        $mock = $input['mock'] ?? false;

        return $this->core->dispatchPartnerWeeklyActivationSummaryMails($limit, $afterId, $mock);
    }

    public function sendSubmerchantFirstTransactionSegmentEvents(array $input)
    {
        SubmerchantFirstTransactionEvent::dispatch($this->mode, $input);

        return ['triggered' => 'true', 'input' => $input];
    }
    /**
     * bulk migrates reseller partners to aggregator partners by pushing jobs
     *
     * @param $input {
     *                  "data" => array({ "merchant_id" => <merchantID>, "new_auth_create" => <Boolean> }),
     *                  "batch_size" => <Int>
     *              }
     * @return mixed
     * @throws Throwable
     */
    public function bulkMigrateResellerToAggregatorPartner(array $input)
    {
        if ($this->isPartnerTypeBulkMigrationExpEnabled() === false)
        {
            return ['success' => true, 'errorMessage' => "Merchant is not allowed for migration"];
        }
        return $this->core()->bulkMigrateResellerToAggregatorPartner($input);
    }

    public function migrateResellerToPurePlatformPartner(array $input)
    {
        (new Validator())->validateInput('resellerToPurePlatformMigration', $input);

        if ($this->isPartnerTypeSwitchExpEnabled($input['merchant_id']) === false)
        {
            return ['success' => true, 'errorMessage' => "Partner is not allowed for partner type switch"];
        }

        return $this->core()->migrateResellerToPurePlatformPartner($input) ;
    }

    public function migratePurePlatformToResellerPartner(array $input)
    {
        (new Validator())->validateInput('purePlatformToResellerMigration', $input);

        if ($this->isPartnerTypeSwitchExpEnabled($input['merchant_id']) === false)
        {
            return ['success' => true, 'errorMessage' => "Partner is not allowed for partner type switch"];
        }

        return $this->core()->migratePurePlatformToResellerPartner($input) ;
    }

    /**
     * migrates a single reseller partner to aggregator partner
     *
     * @param $input { "merchant_id" => <merchantID>, "new_auth_create" => Boolean }
     * @return mixed
     * @throws Throwable
     */
    public function migrateResellerToAggregatorPartner($input)
    {
        if ($this->isPartnerTypeSwitchExpEnabled($input['merchant_id']) === false)
        {
            return ['success' => true, 'errorMessage' => "Merchant is not allowed for migration."];
        }

        $traceInfo = ['params' => $input];
        $this->trace->info(TraceCode::MIGRATE_RESELLER_TO_AGGREGATOR_REQUEST, $traceInfo);
        $result = null;

        try
        {
            $result = $this->core()->migrateResellerToAggregatorPartner($input);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_ERROR, $traceInfo);
            throw $e;
        }

        return ['success' => $result, 'errorMessage' => $result !== true ? "Invalid merchant for migration" :null];
    }

    /**
     * Checks whether partner merchant is allowed to switch partner type.
     *
     * @param string $merchantId
     *
     * @return bool
     */
    public function isPartnerTypeSwitchExpEnabled(string $merchantId, bool $toConsiderPartnerOnboardingTs = false): bool
    {
        $requestData = [ 'mid' => $merchantId ];
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.partner_type_switch_exp_id'),
            'request_data'  => json_encode($requestData),
        ];

        $partnerOnboardingTs = null;
        if ($toConsiderPartnerOnboardingTs)
        {
            $docType = Constants::PARTNERSHIP . '_' . Constants::TERMS;

            // fetching from live slave connection as consents are not stored in test db.
            $partnerConsent = $this->repo->merchant_consents->getConsentForMerchantIdAndConsentTypeFromSlaveLive($merchantId, [ $docType ]);

            if (empty($partnerConsent))
            {
//              Partner consent will be absent for Partners that were onboarded before Jan 2023
                return $this->merchantCore->isSplitzExperimentEnable($properties, 'enable');
            }

            $partnerOnboardingTs = $partnerConsent->getCreatedAt();
        }


        if ($partnerOnboardingTs !== null)
        {
            $requestData = array_merge($requestData, ['partner_onboarding_ts' => $partnerOnboardingTs]);
        }
        else
        {
            $requestData = array_merge($requestData, ['flow' => 'admin']);
        }

        $properties['request_data'] = json_encode($requestData);

        return $this->merchantCore->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * Checks whether merchant from partner auth is allowed to run this migration.
     *
     * @return bool
     */
    private function isPartnerTypeBulkMigrationExpEnabled(string $merchantId = null): bool
    {
        if ($this->auth->isAdminAuth() === false)
        {
            $merchantId = $this->auth->isPartnerAuth() ? $this->auth->getPartnerMerchantId() : $this->auth->getMerchantId();
        }

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.partner_type_bulk_migration_exp_id'),
        ];

        return $this->merchantCore->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * This function is used to regenerate the referral links to partners in bulk. This is a one time usage function.
     *
     * @param array $input - list of partners to which referral links to be migrated to new format
     *
     * @return array -
     *             success_ids - list of valid partners whose referral link is regenerated
     *             failure_ids - list of invalid ids provided in the input and no validation error is thrown
     * @throws BadRequestException - in case of unauthorized merchant access, throw exception
     */
    public function regeneratePartnerReferralLinks(array $input): array
    {
        (new Validator())->validateInput('regenerateReferralLink', $input);

        $properties = [
            'id'            => $this->merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.partner_regenerate_referrals_links_exp_id'),
        ];

        $expEnabled = $this->merchantCore->isSplitzExperimentEnable($properties, 'enable',
                                                                    TraceCode::REGENERATE_REFERRAL_LINKS_SPLITZ_ERROR);

        if ($expEnabled === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                                          null, $input,
                                          "Not authorized to regenerate partner referral links");
        }

        $partnerIds = $input['partner_ids'];

        $partners = $this->repo->merchant->findMany($partnerIds);

        (new Referral\Core())->regenerate($partners);

        $successIds = $partners->pluck('id')->toArray();

        $failureIds = array_values(array_diff($partnerIds, $successIds));

        return [
            'success_ids' => $successIds,
            'failure_ids' => $failureIds
        ];
    }

    /**
     * creates a new migration request in partnership service
     *
     * @param array $input
     *
     * @return bool[]
     * @throws Exception\ServerErrorException
     */
    public function raisePartnerMigrationRequest(array $input): array
    {
        (new Validator())->validateInput('raisePartnerMigrationRequest', $input);

        $partner = $this->merchant;
        if ($this->isPartnerTypeSwitchExpEnabled($partner->getId(), true) === false)
        {
            return ['success' => false, 'errorMessage' => "Partner is not allowed for partner type switch"];
        }

        $params = [
            'partner_id'       => $partner->getId(),
            'status'           => "requested",
            'old_partner_type' => $partner->getPartnerType(),
            'audit_log'        => [
                'actor_id'     => $this->auth->getUser()->getId(),
                'actor_type'   => $this->auth->getUser()->getEntityName(),
                'actor_email'  => $this->auth->getUser()->getEmail()
            ],
            'freshdesk_params' => [
                'phone_no'     => $input['phone_no'],
                'website_url'  => $input['website_url'],
                'description'  => $input['other_info'],
                'name'         => $partner->getName(),
                'email'        => $partner->getEmail()
            ]
        ];
        $partnershipsResponse = $this->app->partnerships->createPartnerMigrationAudit($params);

        if($partnershipsResponse['status_code'] == 200)
        {
            $this->trace->count(Metric::PARTNER_MIGRATION_REQUEST_CREATED);

            $this->captureConsents($input['terms'], $partner->getId());

            return ['success' => true];
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
     * @param array  $terms consent sent from frontend
     * @param string $merchantId
     *
     * @return void
     */
    private function captureConsents(array $terms, string $merchantId)
    {
        if ($terms['consent'] === true)
        {
            $mode = ($this->app['env'] === Environment::TESTING) ? Mode::TEST : Mode::LIVE;
            $input[DEConstants::IP_ADDRESS ] = $this->app['request']->ip();
            $input[DEConstants::USER_ID]     = $this->auth->getUser()->getId();
            $input[DEConstants::DOCUMENTS_DETAIL] = [
                [
                    DEConstants::TYPE => Constants::TERMS,
                    DEConstants::URL  => $terms['url'],
                ]
            ];

            CapturePartnershipConsents::dispatch($mode, $input, $merchantId, ConsentConstant::PARTNER_TYPE_SWITCH);
        }
    }
    public function getPartnerSalesPOC($merchantId = null)
    {
        if (empty($merchantId) === true)
        {
            $merchantId = $this->merchant->getId();
        }

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.partnerships_sales_poc_experiment_id'),
        ];

        $isEnabled =  (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');

        $response = [];

        if($isEnabled)
        {
            $response = $this->app['salesforce']->getPartnershipSalesPOCForMerchantId($merchantId);

            $selfServePartner = $this->isSelfServePartner($response);

            if($selfServePartner)
            {
                $response = [];
            }

        }

        return ['items'   =>  $response];
    }

    /**
     * fetches all the required entities for partnership service
     * @param array $input
     *
     */
    public function fetchPartnerRelatedEntitiesForPRTS(array $input)
    {
        $merchantIds = explode(',',$input['ids']);
        $requiredEntities = explode(',',$input['expand']);

        return $this->core->fetchPartnerRelatedEntitiesForPRTS($merchantIds, $requiredEntities);

    }
    public function isMarketplaceTransferExpEnabled(?Merchant\Entity $partner): bool
    {
        if (empty($partner) === true)
        {
            return false;
        }

        $properties = [
            'id'            => $partner->getId(),
            'experiment_id' => $this->app['config']->get('app.partnerships_for_marketplace_transfer_experiment_id'),
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }

    public function isSubmerchantPaymentManualSettlementExpEnabled(?Merchant\Entity $partner): bool
    {
        if (empty($partner) === true)
        {
            return false;
        }

        $properties = [
            'id'            => $partner->getId(),
            'experiment_id' => $this->app['config']->get('app.submerchant_payment_manual_settlement_experiment_id'),
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * @throws BadRequestException
     */
    public function isFeatureEnabledForPartner(string $featureKey, ?Merchant\Entity $partner, ?string $oauthAppId = null): bool
    {
        if (empty($partner) === true)
        {
            return false;
        }

        // experiment coupled with the feature flag
        $expEnabled = true;

        switch ($featureKey)
        {
            case FeatureConstants::ROUTE_PARTNERSHIPS:
                // this feature is coupled with marketplace transfer exp. TODO: remove this exp after 100% ramp-up
                $expEnabled = $this->isMarketplaceTransferExpEnabled($partner);
                $isFeatureEnabled = $expEnabled && $partner->isRoutePartnershipsEnabled();
                break;

            case FeatureConstants::SUBM_MANUAL_SETTLEMENT:
                // this feature is coupled with sub-merchant manual settlement exp. TODO: remove this exp after 100% ramp-up
                $expEnabled = $this->isSubmerchantPaymentManualSettlementExpEnabled($partner);
                $isFeatureEnabled = $expEnabled && $partner->isSubmerchantManualSettlementEnabled();
                break;

            // add more cases for other features if needed

            default:
                $isFeatureEnabled = $partner->isFeatureEnabled($featureKey);
        }

        // if feature is not enabled for pure platform partner then check if it is enabled for the OAuth app (if passed)
        if ($isFeatureEnabled === false &&
            $expEnabled === true &&
            empty($oauthAppId) === false &&
            $partner->isPurePlatformPartner() === true &&
            in_array($featureKey, FeatureConstants::PARTNER_AND_APP_LEVEL_FEATURES) === true)
        {
            return $this->isFeatureEnabledForOAuthApp($featureKey, $oauthAppId);
        }

        return $isFeatureEnabled;
    }

    /**
     * @throws BadRequestException
     */
    public function isFeatureEnabledForOAuthApp(string $featureKey, string $oauthAppId): bool
    {
        $isFeatureEnabled = (new FeatureService())->checkFeatureEnabled(FeatureConstants::APPLICATION, $oauthAppId, $featureKey)[FeatureConstants::STATUS];

        return is_bool($isFeatureEnabled) ? $isFeatureEnabled : false;
    }

    private function isSelfServePartner(array $sfResponse): bool
    {
        $isSelfServe = false;

        if (empty($sfResponse) == false && $sfResponse["Enabler_POC__r"]["Name"] === PartnerConstants::PARTNER_SELF_SERVE)
        {
            $isSelfServe = true;
        }

        return $isSelfServe;
    }

    public function updateNcOptOutForPartner(array $input)
    {
        $this->core->updateNcOptOutForPartner($input);

        return ['success' => true];
    }

    public function getApplicationsDetailsForPayment(array $input)
    {
        (new Validator())->validateInput('get_app_name', $input);

        $appDetails = $this->core->getApplicationDetailsForPayment($input['payment_id']);

        return ['application' => $appDetails];
    }

    public function fetchEarningsSectionStatusForPartner(array $input)
    {
        $partner = $this->repo->merchant->findOrFailPublic($input['id']);

        $commissionEnabledConfigs = (new Config\Core())->fetchAllEnabledConfigGroupsByPartner($partner);

        $isEarningsEnabled = (empty($commissionEnabledConfigs) === false) ;

        return ['enable_earnings' => $isEarningsEnabled];
    }

    public function createPOSDeviceConfig(string $partnerId, array $input): array
    {
        return $this->handlePOSDeviceConfigRequest($partnerId, $input, PartnerPGOSProxyController::PARTNER_POS_DEVICE_CONFIG_CREATE);
    }

    public function updatePOSDeviceConfig(string $partnerId, array $input): array
    {
        return $this->handlePOSDeviceConfigRequest($partnerId, $input, PartnerPGOSProxyController::PARTNER_POS_DEVICE_CONFIG_UPDATE);
    }

    /**
     * @throws \Exception
     */
    public function posGetSubmDefaultDeviceConfig(string $subMerchantId): array
    {
        Merchant\Account\Entity::verifyIdAndSilentlyStripSign($subMerchantId);
        $subMerchant = $this->repo->merchant->findOrFail($subMerchantId);

        $partner = $this->fetchPartner();
        $this->merchantValidator->validateIsPartner($partner);
        (new Validator())->validatePartnerSubMerchantMapping($partner, $subMerchant);

        // 10000razorpay - this is default RZP merchant id
        $input = [
            PartnerConstants::MERCHANT_ID => PartnerConstants::DEFAULT_MERCHANT_ID
        ];

        // routing condition is ignored as all POS requests have to be driven thorugh PGOS
        $response = $this->pgosProxyController->handlePGOSProxyRequests('merchant_fetch_device_config', $input, $subMerchant);

        $this->trace->info(
            TraceCode::SUB_MERCHANT_DEVICE_CONFIG,
            [
                'response'    => $response,
            ]
        );

        return $response;
    }

    public function posFetchSubmAllDeviceOrder(string $subMerchantId)
    {
        Merchant\Account\Entity::verifyIdAndSilentlyStripSign($subMerchantId);
        $subMerchant = $this->repo->merchant->findOrFail($subMerchantId);

        $partner = $this->fetchPartner();
        $this->merchantValidator->validateIsPartner($partner);
        (new Validator())->validatePartnerSubMerchantMapping($partner, $subMerchant);

        $input = [
            PartnerConstants::MERCHANT_ID => $subMerchant->getId()
        ];

        $response = $this->pgosProxyController->handlePGOSProxyRequests('merchant_pos_fetch_all_order', $input, $subMerchant);

        $this->trace->info(
            TraceCode::SUB_MERCHANT_FETCH_ALL_DEVICE_ORDER,
            [
                'response'    => $response,
            ]
        );

        return $response;
    }

    public function posFetchSubmLatestOrder(string $subMerchantId)
    {
        Merchant\Account\Entity::verifyIdAndSilentlyStripSign($subMerchantId);
        $subMerchant = $this->repo->merchant->findOrFail($subMerchantId);

        $partner = $this->fetchPartner();
        $this->merchantValidator->validateIsPartner($partner);
        (new Validator())->validatePartnerSubMerchantMapping($partner, $subMerchant);

        $input = [
            PartnerConstants::MERCHANT_ID => $subMerchant->getId()
        ];

        $response = $this->pgosProxyController->handlePGOSProxyRequests('merchant_pos_fetch_latest_order', $input, $subMerchant);

        if ($response === null) {
            throw new Exception\ServerErrorException( "failed to execute fetch latest order request",
                ErrorCode::SERVER_ERROR);
        }

        $this->trace->info(
            TraceCode::SUB_MERCHANT_FETCH_LATEST_DEVICE_ORDER,
            [
                'response'    => $response,
            ]
        );

        return $response;
    }

    /**
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function fetchPartner(): Entity
    {
        //
        // In the context of partners and submerchants -
        //
        // $merchant_id here corresponds to the submerchant's id. This is because the merchant_access_map entity maps
        // the submerchant id to the application entity (entity_type = application and entity_id = application_id),
        // which makes the submerchant as the primary entity in the merchant_access_map
        //
        $partner = $this->merchant;

        if ($partner === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_CONTEXT_NOT_SET,
                Entity::PARTNER_TYPE);
        }

        return $partner;
    }


    public function handlePOSDeviceConfigRequest(string $partnerId, array $input, string $route)
    {
        $partner = $this->repo->merchant->findOrFail($partnerId);

        $isPOSPartnershipEnabled = $this->core()->isPOSEnabledForPartner($partner->getId());

        if($isPOSPartnershipEnabled == false)
        {
            return [
                "success" => false,
                "message" => "partnerships pos not enabled for the partner"
            ];
        }

        $input[PartnerConstants::ENTITY_ID]   = $partnerId;
        $input[PartnerConstants::ENTITY_TYPE] = PartnerConstants::ENTITY_TYPE_PARTNER;

        return $this->partnerPGOSProxyController->handlePGOSProxyRequests($route, $input, $partner, true);
    }

    public function autoApproveMerchantActivationCheckerFlow(array $input): array
    {
        $splitzVariables = $this->getSplitzExperimentVariables('enable');
        if (empty($splitzVariables))
        {
            throw new Exception\LogicException("Admin not allowed to auto-approve!");
        }

        $this->increaseAllowedSystemLimits();

        return $this->core->autoApproveMerchantActivationCheckerFlow($input, $splitzVariables);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('2048M');

        RuntimeManager::setTimeLimit(1800);

        RuntimeManager::setMaxExecTime(1800);
    }

    public function getSplitzExperimentVariables(string $checkVariant): array
    {
        $splitzVariables = [];

        try
        {
            $admin = $this->app['basicauth']->getAdmin();

            $properties = [
                'id'            => $admin->getId(),
                'experiment_id' => app('config')->get('app.sub_merchant_activation_auto_approval_checker'),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);
            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            $variant = $response['response']['variant']['name'] ?? null;
            if ( is_null($variant) === true )
            {
                return [];
            }

            $isVariantMatch = $variant === $checkVariant;

            if (! $isVariantMatch)
            {
                return [];
            }

            foreach ( $response['response']['variant']['variables'] as $variable )
            {
                $splitzVariables[$variable['key']] = $variable['value'];
            }
        }
        catch (\Exception $e)
        {
            $id = $properties['id'] ?? null;
            $this->trace->traceException(
                $e, Trace::ERROR,
                TraceCode::SPLITZ_ERROR,
                ['id' => $id, 'variables' => $splitzVariables]
            );

            return [];
        }

        return $splitzVariables;
    }
}
