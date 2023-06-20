<?php

namespace RZP\Models\Feature;

use Mail;
use Config;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Mail\Merchant\FullES;
use RZP\Constants\HyperTrace;
use RZP\Jobs\MailingListUpdate;
use RZP\Exception\LogicException;
use RZP\Models\Settings\Accessor;
use RZP\Models\Base\PublicEntity;
use RZP\Mail\Merchant\EsEligible;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Merchant\FeatureEnabled;
use RZP\Models\Merchant\SlackActions;
use RZP\Notifications\Dashboard\Events;
use RZP\Constants\Entity as AppConstants;
use RZP\Jobs\SkipOnboardingCommFromHubSpot;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Customer\Token;
use RZP\Services\PayoutService;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Settlement\OndemandFundAccount;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Jobs\Transfers\AutoLinkedAccountCreation;
use RZP\Models\Merchant\Request as MerchantRequest;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Jobs\Transfers\LinkedAccountBankVerificationStatusBackfill;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Notifications\Dashboard\Constants as DashboardNotificationConstants;

class Core extends Base\Core
{
    use NotifyTrait;

    /**
     * @var PayoutService\MerchantConfig
     */
    protected $payoutServiceMerchantConfigClient;

    public function __construct()
    {
        parent::__construct();

        if ($this->merchant !== null)
        {
            $this->merchant->setLoadedFeaturesNull();
        }

        $this->payoutServiceMerchantConfigClient = $this->app[PayoutService\MerchantConfig::PAYOUT_SERVICE_MERCHANT_CONFIG];
    }

    /**
     * Create feature
     *
     * @param array $input
     * @param bool  $shouldSync Should the entity be save on both test and live
     * @param bool $shouldBackfillForLa Should push back fill job for route_la_penny_testing feature
     * @param string|null $mode
     *
     * @return Entity
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function create(array $input, bool $shouldSync = false, bool $shouldBackfillForLa = true, string $mode = null): Entity
    {
        $tokenizationGateways = $input['tokenization_gateways'] ?? [];
        unset($input['tokenization_gateways']);

        $feature = (new Entity)->build($input);
        if ($mode !== null) {
            $feature->setConnection($mode);
        }

        $entityType = $input[Entity::ENTITY_TYPE];

        $entityId = $input[Entity::ENTITY_ID];

        //
        //route_la_penny_testing feature is used for penny testing Linked accounts created in Route.
        // This automates the backFilling script which updates bank verification status on Linked accounts
        // LinkedAccountBankVerificationStatusBackFill Job updates all existing linked accounts as verified,
        // this job can take upto 3hrs.
        //
        // Hence we do not want to assign this feature before all existing LAs are marked verified.
        // This "create" method will be called from LinkedAccountBankVerificationStatusBackFill job in the
        // end with $shouldBackFillForLa as false which ensures from pushing the same job again and then assign
        // the feature.
        //
        if (($shouldBackfillForLa === true) and
            ($input[Entity::NAME] === Constants::ROUTE_LA_PENNY_TESTING) and
            ($entityType === Constants::MERCHANT))
        {
            $linkedAccountIds = $this->repo->merchant->fetchActivatedLinkedAccountIdsForParentMerchant($entityId);

            LinkedAccountBankVerificationStatusBackfill::dispatch($this->mode, $linkedAccountIds, $input, $shouldSync);

            return $feature;
        }

        if (($input[Entity::NAME] === Constants::VIRTUAL_ACCOUNTS) and
            (in_array($entityType, [Constants::MERCHANT, Constants::ACCOUNT], true) === true))
        {
            $this->validateMerchantForVirtualAccountFeature($entityId);
        }

        //
        // These entity types are owned by api, hence we validate their existence
        // here before associating.
        //

        $isMerchant = (in_array($entityType, [Constants::MERCHANT, Constants::ACCOUNT], true) === true);

        if ($isMerchant)
        {
            $entity = $this->repo->merchant->findOrFailPublic($entityId);

            $feature->entity()->associate($entity);
        }
        //
        // Features for other entity types which are external to api, aren't checked
        // for existence.
        //
        else
        {
            if (in_array($entityType, [Constants::APPLICATION, Constants::PARTNER_APPLICATION], true) === true)
            {
                $merchantApplication = $this->repo->merchant_application
                    ->fetchMerchantApplication($entityId, MerchantApplications\Entity::APPLICATION_ID);

                if ($merchantApplication->count() === 0)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_APPLICATION_ID, null);
                }
            }

            $feature->setEntityId($entityId);

            $feature->setEntityType($entityType);
        }

        $feature->generateId();

        $existingFeatures = Tracer::inspan(['name' => HyperTrace::FETCH_FEATURE], function () use ($entityType, $entityId, $mode) {

            return $this->repo->feature->fetchByEntityTypeAndEntityId($entityType, $entityId, $mode);
        });

        $assignedFeatureNames = array_unique($existingFeatures->pluck(Entity::NAME)->toArray());

        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_EDIT_REQUEST,
            [
                PublicEntity::MERCHANT_ID => $feature->getEntityId(),
                Entity::OLD_FEATURES      => $assignedFeatureNames,
                Entity::NEW_FEATURE       => $feature->getName(),
                Entity::SHOULD_SYNC       => $shouldSync
            ]);

        if ($entityType === Constants::MERCHANT and
            in_array($feature->getName(),
                     [
                         Constants::HIGH_TPS_COMPOSITE_PAYOUT,
                         Constants::HIGH_TPS_PAYOUT_EGRESS,
                         Constants::LEDGER_JOURNAL_READS,
                         Constants::LEDGER_JOURNAL_WRITES,
                         Constants::LEDGER_REVERSE_SHADOW,
                     ],
                     true) === true)
        {
            $this->controlFeatureAssignmentForLedger($feature->getName(), $assignedFeatureNames);
        }

        if ($entityType === Constants::MERCHANT and $feature->getName() === Constants::MFN)
        {
            try
            {
                (new Merchant\WebhookV2\Service())->handleWebhookForMFN($entity, $shouldSync);
            }
            catch(\Throwable $throwable)
            {
                $this->trace->traceException(
                    $throwable,
                    null,
                    TraceCode::MFN_WEBHOOK_CREATE_FAILURE,
                    [ 'merchant_id' => $entityId ]
                );

                $this->trace->count(Metric::MFN_WEBHOOK_CREATE_FAILURE);
            }
        }

        $this->checkAuthTypeIfApplicable($feature);

        $this->checkCollectionsAuthTypeForCreationIfApplicable($feature);

        Tracer::inspan(['name' => HyperTrace::SAVE_AND_SYNC_FEATURE], function () use ($feature, $assignedFeatureNames, $shouldSync) {

            $this->repo->feature->saveAndSyncIfApplicableOrFail(
                $feature,
                $assignedFeatureNames,
                $shouldSync);
        });

        Tracer::inspan(['name' => HyperTrace::APPROVE_FEATURE_ONBOARDING_REQUEST], function () use ($feature, $shouldSync) {

            $this->approveFeatureOnboardingRequestIfApplicable($feature, $shouldSync);
        });

        $this->notifyFeatureUpdateOnSlack($feature);

        if (($feature->getName() === Feature::USE_MSWIPE_TERMINALS) && ($feature->getEntityType() === Constants::MERCHANT))
        {
            (new Terminal\Service())->addMswipeTerminals($feature->getEntityId());
        }

        if (($feature->getName() === Feature::ES_ON_DEMAND) && ($feature->getEntityType() === Constants::MERCHANT))
        {
            (new Merchant\Service)->addMerchantToOnDemandEnabledMailingList($feature->getEntityId());
        }

        if (( ($feature->getName() === Feature::ES_ON_DEMAND) || ($feature->getName() === Feature::ONDEMAND_LINKED))
            && ($feature->getEntityType() === Constants::MERCHANT))
        {
            (new OndemandFundAccount\Service)->dispatchSettlementOndemandFundAccountCreateJob($feature->getEntityId());
        }

        Tracer::inspan(['name' => HyperTrace::SKIP_SUBM_ONBOARDING_COMMUNICATION], function () use ($feature, $entityId) {

            if (($feature->getName() === Feature::SKIP_SUBM_ONBOARDING_COMM) && ($feature->getEntityType() === Constants::MERCHANT)
                && ($this->mode === Mode::LIVE)) {
                $partner = $this->repo->merchant->findOrFailPublic($entityId);

                $partnerEmail = $partner->getEmail();

                $appIds = (new Merchant\Core())->getPartnerApplicationIds($partner);

                $subMerchants = $this->repo->merchant->fetchSubmerchantsByAppIds($appIds);

                $subMerchantEmails = $subMerchants->pluck(Merchant\Entity::EMAIL)->toArray();

                $subMerchantEmailChunks = array_chunk($subMerchantEmails, 500);

                foreach ($subMerchantEmailChunks as $subMerchantEmailChunk) {
                    SkipOnboardingCommFromHubSpot::dispatch($this->mode, $entityId, $partnerEmail, $subMerchantEmailChunk);
                }
            }
        });

        if($feature->getName() === Feature::ONBOARD_TOKENIZATION && $feature->isMerchantFeature() === true)
        {
           $merchant = $this->repo->merchant->findOrFailPublic($entityId);

           (new Token\Core())->onboardMerchant($merchant, $tokenizationGateways);
        }
        else if(str_contains($feature->getName(), Feature::ONBOARD_TOKENIZATION) && $feature->isMerchantFeature() === true)
        {
            $merchant = $this->repo->merchant->findOrFailPublic($entityId);

            $network = str_replace(Feature::ONBOARD_TOKENIZATION . "_", "", $feature->getName());

            // Move this logic to router service in future
            if($network === 'mc'){
                $network = 'mastercard';
            }

            if ($network === "rpy"){
                $network = 'rupay';
            }

            $tokenizationGateways = "tokenisation_".$network;

            // Map onboard_tokenization_diners to tokenisation_hdfc
            // Diners only have hdfc issued cards for tokenisation
            if ($network === 'dnrs')
            {
                $tokenizationGateways = 'tokenisation_hdfc';
            }

            (new Token\Core())->onboardMerchant($merchant, [$tokenizationGateways]);
        }

        if ($isMerchant) {
            $autoLinkedAccountCreation = (($feature->getName() === Feature::MARKETPLACE) and
                ($entity->getCategory() === Merchant\Constants::AUTO_CREATE_AMC_LINKED_ACCOUNT_MCC[Merchant\Entity::CATEGORY]) and
                ($entity->getCategory2() === Merchant\Constants::AUTO_CREATE_AMC_LINKED_ACCOUNT_MCC[Merchant\Entity::CATEGORY2]));

            if ($autoLinkedAccountCreation) {
                AutoLinkedAccountCreation::dispatch($this->mode, $entityId);
            } else {
                $this->trace->info(
                    TraceCode::AMC_LINKED_ACCOUNT_CREATION_SKIPPED,
                    [
                        "merchant_id" => $entity->getId(),
                        "category" => $entity->getCategory(),
                        "category2" => $entity->getCategory2(),
                    ]);
            }
        }

        $this->notifyMerchantOfFeatureActivationIfApplicable($entityType, $entityId, $feature, $shouldSync);

        $this->updatePayoutsMicroserviceOnFeatureUpdate($feature, $entityType, $entityId, AppConstants::ENABLE);

        return $feature;
    }

    // updatePayoutsMicroserviceOnFeatureUpdate is used to update the merchant config
    // cache on Payouts service so that there is no lag between enabling a feature
    // on API and the same being reflected on Payouts Service.
    // Any error/exception in this function is logged and not thrown so that the feature
    // addition/deletion flows are not affected.
    public function updatePayoutsMicroserviceOnFeatureUpdate(
        Entity $feature, string $entityType, string $entityId, string $action)
    {
        try {
            if ($entityType === Constants::MERCHANT)
            {
                $merchant = $this->repo->merchant->findOrFailPublic($entityId);

                if ($merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === true)
                {
                    $payoutServiceRequest = [
                        Constants::MERCHANT_ID        => $merchant->getId(),
                        Constants::FEATURE            => $feature->getName(),
                        AppConstants::ACTION          => $action,
                    ];

                    $this->payoutServiceMerchantConfigClient->updateMerchantFeatureCacheInPayoutMicroservice($payoutServiceRequest);
                }
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::UPDATE_MERCHANT_FEATURE_IN_PAYOUT_SERVICE_FAILED,
                [
                    Constants::MERCHANT_ID => $entityId,
                    Constants::FEATURE     => $feature->getName(),
                    AppConstants::ACTION   => $action,
                    'error'                => $ex->getMessage()
                ]);
        }
    }

    protected function validateMerchantForVirtualAccountFeature(string $merchantId)
    {
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        if ($merchantDetail->isUnregisteredBusiness() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_FEATURE_NOT_ALLOWED_FOR_MERCHANT);
        }
    }

    /**
     * Delete feature
     *
     * @param Entity $feature
     * @param bool   $shouldSync
     */
    public function delete(Entity $feature, bool $shouldSync = false)
    {
        $this->trace->info(
            TraceCode::FEATURE_DELETE_REQUEST,
            [
                Entity::FEATURE     => $feature->toArrayPublic(),
                Entity::SHOULD_SYNC => $shouldSync
            ]);

        if (($feature->getName() === 'ledger_journal_writes') or
            ($feature->getName() === 'ledger_reverse_shadow') or
            ($feature->getName() === 'ledger_journal_reads') or
            ($feature->getName() === 'da_ledger_journal_writes') or
            ($feature->getName() === 'da_ledger_reverse_shadow'))

        {
            $this->trace->count(Metric::LEDGER_FEATURE_REMOVAL_COUNT,
                                [
                                    'mode'         => $this->app['rzp.mode'],
                                    'environment'  => $this->app['env'],
                                ]);
        }

        // Merchant shouldn't be able to remove this feature for compliance of DS only merchants
        if ($feature->toArrayPublic()['name'] === 'only_ds')
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED,
                null
            );
        }

        // Workflow
        list($original, $dirty) = [
            ['feature' => $feature->getName()],
            ['feature' => null],
        ];

        $this->checkCollectionsAuthTypeForDeletionIfApplicable($feature);

        $this->app['workflow']
            ->setEntity($feature->getEntity())
            ->handle($original, $dirty);

        $this->repo->feature->deleteAndSyncIfApplicableOrFail($feature, $shouldSync);

        $this->handleEsFeatureDeletion($feature);

        $this->notifyFeatureUpdateOnSlack($feature, true);

        $this->updatePayoutsMicroserviceOnFeatureUpdate($feature, $feature->getEntityType(), $feature->getEntityId(), AppConstants::DISABLE);
    }

    /**
     * Handle deletion of Early Settlement Features
     *
     * @param Entity $feature
     */
    private function handleEsFeatureDeletion(Entity $feature)
    {
        if ($feature->getEntityType() !== Constants::MERCHANT)
        {
            return;
        }
        if ($feature->getName() === Feature::ES_ON_DEMAND)
        {
            (new Merchant\Service)->removeMerchantFromOnDemandEnabledMailingList($feature->getEntityId());
        }
        if ($feature->getName() === Feature::ES_AUTOMATIC)
        {
            $merchant = $this->repo->merchant->findByPublicId($feature->getEntityId());

            if ($merchant->isFeatureEnabled(Feature::ES_ON_DEMAND) === false)
            {
                return;
            }

            $pricing = (new \RZP\Models\Settlement\Ondemand\Core)->getOndemandPricingByFeature($merchant, PricingFeature::SETTLEMENT_ONDEMAND);
            if ($pricing === null)
            {
                $percentRate = null;
            } else
            {
                [$percentRate, $fixedRate] =  $pricing->getRates();
            }

            $this->trace->info(TraceCode::ES_AUTOMATIC_FEATURE_DELETED,
                [
                    PublicEntity::MERCHANT_ID              => $feature->getEntityId(),
                    "ondemand_pricing_percent_rate_in_bps" => $percentRate,
                ]);
        }
    }

    /**
     * Notify the merchant of feature Activation by email if applicable based on mode, feature type and sync status.
     *
     * @param string $entityType
     * @param string $entityId
     * @param Entity $feature
     * @param bool   $shouldSync
     */
    public function notifyMerchantOfFeatureActivationIfApplicable(
        string $entityType,
        string $entityId,
        Entity $feature,
        bool $shouldSync)
    {
        // We currently do not notify the applications of the feature activation
        if ($entityType !== Constants::MERCHANT)
        {
            return;
        }

        $isLiveMode = $this->isLiveMode();

        $merchant = $this->repo->merchant->findOrFailPublic($entityId);

        if (($feature->isProductFeature() === true) and
            (($shouldSync === true) or ($isLiveMode === true)) and
            (in_array($feature->getName(), Constants::$skipFeaturesEnableMail, true) === false))
        {
            $featureName     = $feature->getName();

            $visibleFeatures = Constants::$visibleFeaturesMap;

            $featureDisplayName = $visibleFeatures[$featureName][Constants::DISPLAY_NAME];

            $data = [
                Merchant\Constants::MERCHANT     => $merchant,
                Events::EVENT                    => Events::FEATURE_UPDATE_NOTIFICATION,
                Merchant\Constants::PARAMS       => [
                    DashboardNotificationConstants::MESSAGE_SUBJECT   => $featureDisplayName. ' enabled for Live mode',
                    Constants::FEATURE                                => $featureDisplayName,
                    Constants::DOCUMENTATION                          => $visibleFeatures[$featureName][Constants::DOCUMENTATION],
                ]
            ];

            (new DashboardNotificationHandler($data))->send();

            $this->trace->info(
                TraceCode::FEATURE_ENABLED_MERCHANT_NOTIFIED,
                [
                    PublicEntity::MERCHANT_ID => $entityId,
                    Entity::SHOULD_SYNC       => $shouldSync,
                    Mode::LIVE                => $isLiveMode,
                    Entity::NEW_FEATURE       => $feature,
                ]);
        }

        else if(($feature->getName() === Constants::ES_ON_DEMAND) and
                (in_array(Constants::ES_AUTOMATIC, $merchant->getEnabledFeatures()) === false) and
                (in_array(Constants::ES_ON_DEMAND_RESTRICTED, $merchant->getEnabledFeatures()) === false) and
                ($isLiveMode === true) and
                $this->app['basicauth']->isAdminAuth() === true)
        {
            $merchantEmail = $merchant->getEmail();

            $data['contact_name']  = $merchant->getName();
            $data['contact_email'] = $merchantEmail;

            $esEligibleEmail = new FullES($data);

            Mail::queue($esEligibleEmail);

            $this->trace->info(
                TraceCode::ES_ELIGIBLE_MERCHANT_NOTIFIED,
                [
                    PublicEntity::MERCHANT_ID => $entityId,
                    Entity::SHOULD_SYNC       => $shouldSync,
                    Mode::LIVE                => $isLiveMode,
                    Entity::NEW_FEATURE       => $feature,
                    Merchant\Entity::EMAIL    => $merchantEmail
                ]);
        }
        else
        {
            $this->trace->info(
                TraceCode::FEATURE_ENABLED_MERCHANT_NOT_NOTIFIED,
                [
                    PublicEntity::MERCHANT_ID => $entityId,
                    Entity::SHOULD_SYNC       => $shouldSync,
                    Mode::LIVE                => $isLiveMode,
                    Entity::NEW_FEATURE       => $feature,
                ]);
        }
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param string          $feature
     *
     * @return bool
     * @throws Exception\BadRequestException
     */
    public function postOnboardingSubmissions(Merchant\Entity $merchant, array $input, string $feature): bool
    {
        // Prevent the merchant from re-submitting
        $data = Accessor::for($merchant, Constants::ONBOARDING)
                        ->get($feature)
                        ->toArray();

        if (count($data) > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ACTIVATION_FORM_ALREADY_SUBMITTED,
                $feature,
                ['feature' => $feature, 'data' => $data]);
        }

        $data[$feature] = $input;

        $status = $this->processOnboardingSubmissions(Constants::CREATE, $data, $merchant);

        return $status;
    }

    /**
     * @param string          $action
     * @param array           $data
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public function processOnboardingSubmissions(
        string $action,
        array $data,
        Merchant\Entity $merchant): bool
    {
        $saved = false;

        $merchantId = $merchant->getId();

        $this->trace->info(
            TraceCode::FEATURE_ONBOARDING_SUBMISSION_REQUEST,
            [
                'action'      => $action,
                'data'        => $data,
                'merchant_id' => $merchantId
            ]);

        $featureName = array_keys($data)[0] ?? null;

        if ($featureName !== null)
        {
            $saved = true;

            (new Validator)->validateInput(Constants::ONBOARDING_SUBMISSIONS_UPSERT, $data);

            // While updating the responses, the file gets overwritten,
            // so no need to delete the old file.
            $this->processFiles($data, $merchant);

            $this->processOnboardingKeys($data, $merchant, $action);

            Accessor::for($merchant, Constants::ONBOARDING)
                    ->upsert($data)
                    ->save();

            // Set the product activation status as pending
            if ($action === Constants::CREATE)
            {
                $featureStatus = $this->getFeatureStatus($merchant, $featureName);

                $this->updateFeatureActivationStatus(
                    $merchantId,
                    $featureName,
                    $featureStatus);

                $saved = true;
            }
        }

        return $saved;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string|null     $feature
     *
     * @return array
     */
    public function getOnboardingSubmissions(
        Merchant\Entity $merchant,
        string $feature = null): array
    {
        $settings = Accessor::for($merchant, Constants::ONBOARDING);

        $settings = ($feature === null) ? $settings->all() : $settings->get($feature);

        $response = $settings->toArray();

        $this->updateFileUrlInResponseIfApplicable($response, $merchant);

        return $response;
    }

    /**
     * Updates the feature activation status in the merchant details table.
     * It also adds the feature, if the status is approved and the feature is not enabled for the merchant.
     *
     * @param string $merchantId
     * @param string $featureName
     * @param string $status
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function updateFeatureActivationStatus(
        string $merchantId,
        string $featureName,
        string $status): array
    {
        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        if (in_array($status, Constants::ONBOARDING_STATUSES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ONBOARDING_STATUS_INVALID,
                $featureName,
                [$featureName, $status]);
        }

        if (($status === MerchantDetail::APPROVED) and
            ($merchant->isFeatureEnabled($featureName) === false))
        {
            // Add the feature
            $params = [
                Entity::ENTITY_TYPE => Constants::MERCHANT,
                Entity::ENTITY_ID   => $merchantId,
                Entity::NAME        => $featureName
            ];

            // Adds to live mode
            $this->create($params, true);
        }

        // TODO:: Remove this once the migration to new merchant requests flow has been done.
        $this->repo->merchant_detail->updateFeatureActivationStatus(
            $merchant,
            $featureName,
            $status
        );

        // Creating/Update a Merchant Request if applicable from the current status of onboarding feature submission
        (new MerchantRequest\Core)->syncOnboardingSubmissionToMerchantRequest(
            $merchant,
            $featureName,
            MerchantRequest\Type::PRODUCT,
            $status);

        $merchantDetail = $merchant->merchantDetail;

        $response = $merchantDetail->getFeatureOnboardingStatuses();

        return $response;
    }

    /**
     * Accepts a merchant map (merchantId => status) for a product feature and updates the status
     *
     * @param string $featureName
     * @param array  $merchantMap
     *
     * @return array
     */
    public function bulkUpdateFeatureActivationStatus(string $featureName, array $merchantMap): array
    {
        $success   = 0;
        $failed    = 0;
        $failedIds = [];

        $this->trace->info(
            TraceCode::FEATURE_ONBOARDING_BULK_UPDATE_STATUS,
            [
                Entity::FEATURE => $featureName,
                'merchant_map'  => $merchantMap,
                'admin_id'      => $this->app['basicauth']->getAdmin()->getId()
            ]);

        foreach ($merchantMap as $merchantId => $status)
        {
            try
            {
                $response = $this->updateFeatureActivationStatus($merchantId, $featureName, $status);

                // Verify that the status was updated
                $featureActivationStatus = snake_case($featureName . '_activation_status');

                if ($response[$featureActivationStatus] !== $status)
                {
                    throw new LogicException('Feature activation status could not be updated');
                }

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    null,
                    [
                        Entity::MERCHANT_ID => $merchantId,
                        Entity::FEATURE     => $featureName,
                        'status'            => $status
                    ]);

                $failed++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'success'    => $success,
            'failed'     => $failed,
            'failed_ids' => $failedIds
        ];

        return $response;
    }

    /**
     * Accessor class overwrites all the old responses submitted by the merchant with the new
     * keys sent while updating. This function preserves the old keys and only updates the new ones.
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param string          $action
     */
    protected function processOnboardingKeys(array & $input, Merchant\Entity $merchant, string $action)
    {
        if ($action === Constants::UPDATE)
        {
            $featureName = array_keys($input)[0];

            $settings = Accessor::for($merchant, Constants::ONBOARDING);

            $settings = $settings->get($featureName)->toArray();

            $inputKeys = $input[$featureName];

            foreach ($inputKeys as $inputKey => $inputValue)
            {
                $settings[$inputKey] = $inputValue;
            }

            $input[$featureName] = $settings;
        }
    }

    /**
     * Send a slack notification on feature create/delete
     *
     * @param Entity $feature
     * @param bool   $featureDeleted
     */
    protected function notifyFeatureUpdateOnSlack(Entity $feature, bool $featureDeleted = false)
    {
        $message = $feature->getDashboardEntityLinkForSlack($feature->getName());

        if ($featureDeleted === true)
        {
            $message .= ' deleted from ';
        }
        else
        {
            $message .= ' added to ';
        }

        $user = $this->getInternalUsernameOrEmail();
        $messageUser = Constants::DASHBOARD_INTERNAL;

        if($user !== Constants::DASHBOARD_INTERNAL)
        {
            $messageUser = 'Merchant User';
        }

        $message .= $feature->getEntityId() . ' by ' . $messageUser;

        $this->app['slack']->queue(
            $message,
            [],
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        );
    }

    /**
     * Adds the vendor_agreement file URL to the response if the feature is marketplace
     *
     * @param   array               $response
     * @param   Merchant\Entity     $merchant
     *     */
    protected function updateFileUrlInResponseIfApplicable(array & $response, Merchant\Entity $merchant)
    {
        $featureName = Constants::MARKETPLACE;

        $question = Constants::VENDOR_AGREEMENT;

        $replacementVariable = null;

        //
        // Adding multiple key checks since this function can be called with response of a single feature submissions
        // or responses of all submissions fetched together, which causes the responses array to be either without
        // key of feature name or keyed by feature name respectively in both cases.
        //
        if (isset($response[$featureName][$question]) === true)
        {
            $fileId = $response[$featureName][$question];

            $replacementVariable = &$response[$featureName][$question];
        }

        if (isset($response[$question]) === true)
        {
            $fileId = $response[$question];

            $replacementVariable = &$response[$question];
        }

        if (empty($replacementVariable) === false)
        {
            $fileUrl = (new FileStore\Core)->getSignedUrl($fileId, $merchant->getId());

            $replacementVariable = $fileUrl;
        }
    }

    /**
     * Uploads the vendor agreement file to S3 via UFH for the marketplace feature
     *
     * @param   array               $input
     * @param   Merchant\Entity     $merchant
     */
    protected function processFiles(array & $input, Merchant\Entity $merchant)
    {
        $featureName = Constants::MARKETPLACE;

        $question = Constants::VENDOR_AGREEMENT;

        $merchantId = $merchant->getId();

        //
        // If the input has a file, process it and
        // update the file name in the input variable.
        //
        if ((isset($input[$featureName]) === true) and
            (isset($input[$featureName][$question]) === true))
        {
            $file = $input[$featureName][$question];

            $settingKey = $featureName . "." . $question;

            $extension = $file->extension();

            $fileName = 'api/' . $merchantId . '/' . $settingKey;

            $file = $this->createFile($extension, $file, $fileName, $settingKey, $merchant);

            $input[$featureName][$question] = FileStore\Entity::stripSignWithoutValidation($file['id']);
        }
    }

    /**
     * Creates a file entity and uploads it to S3 bucket
     *
     * @param                 $extension
     * @param                 $file
     * @param string          $fileName
     * @param string          $type
     * @param Merchant\Entity $merchant
     * @param string          $store
     *
     * @return array
     */
    protected function createFile($extension,
                                  $file,
                                  string $fileName,
                                  string $type,
                                  Merchant\Entity $merchant,
                                  string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $file = $creator->extension($extension)
                        ->localFile($file)
                        ->name($fileName)
                        ->store($store)
                        ->type($type)
                        ->merchant($merchant)
                        ->save()
                        ->get();

        return $file;
    }

    /**
     * Approves the pending feature onboarding request, if any,
     * if the feature is being added to the live mode
     *
     * @param Entity $feature
     * @param bool   $shouldSync
     */
    protected function approveFeatureOnboardingRequestIfApplicable(
        Entity $feature,
        bool $shouldSync)
    {
        if ($feature->isMerchantFeature() === false)
        {
            // Return if the feature is not for a merchant
            return;
        }

        $merchantId = $feature->getEntityId();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $featureName = $feature->getName();

        $isLiveMode = $this->isLiveMode();

        if ($this->shouldUpdateFeatureOnboardingStatus($feature, $shouldSync, $isLiveMode) === true)
        {
            $this->updateFeatureActivationStatus(
                $merchantId,
                $featureName,
                MerchantDetail::APPROVED);

            $this->trace->info(
                TraceCode::FEATURE_ONBOARDING_SUBMISSION_APPROVED,
                [
                    PublicEntity::MERCHANT_ID => $merchantId,
                    Entity::NEW_FEATURE       => $featureName,
                ]);
        }

        $this->sendFeatureActivationNotificaton($merchant, $feature, $shouldSync, $isLiveMode);
    }

    /**
     * Returns true if the feature is a Product feature and if the mode is Live
     *
     * @param Entity $feature
     * @param bool   $shouldSync
     * @param bool   $isLiveMode
     *
     * @return bool
     */
    protected function shouldUpdateFeatureOnboardingStatus(
        Entity $feature,
        bool $shouldSync,
        bool $isLiveMode): bool
    {
        // Notify the merchants, only if the feature is a ProductFeature
        if ($feature->isProductFeature() === false)
        {
            return false;
        }

        if (($shouldSync === false) and ($isLiveMode === false))
        {
            return false;
        }

        return true;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param Entity          $feature
     * @param bool            $shouldSync
     * @param bool            $isLiveMode
     */
    protected function sendFeatureActivationNotificaton(
        Merchant\Entity $merchant,
        Entity $feature,
        bool $shouldSync,
        bool $isLiveMode)
    {
        $merchantId = $feature->getEntityId();

        if (($this->shouldNotifyViaNotification($merchant, $feature, $shouldSync, $isLiveMode) === false) or
            (in_array($feature->getName(), Constants::$skipFeaturesEnableMail, true) === true))
        {
            $this->trace->info(
                TraceCode::FEATURE_ENABLED_MERCHANT_NOT_NOTIFIED,
                [
                    PublicEntity::MERCHANT_ID => $merchantId,
                    Mode::LIVE                => $isLiveMode,
                    Entity::NEW_FEATURE       => $feature,
                ]);

            return;
        }

        $featureName     = $feature->getName();

        $visibleFeatures = Constants::$visibleFeaturesMap;

        $featureDisplayName = $visibleFeatures[$featureName][Constants::DISPLAY_NAME];

        $data = [
            Merchant\Constants::MERCHANT     => $merchant,
            Events::EVENT                    => Events::FEATURE_UPDATE_NOTIFICATION,
            Merchant\Constants::PARAMS       => [
                DashboardNotificationConstants::MESSAGE_SUBJECT   => $featureDisplayName. ' enabled for Live mode',
                Constants::FEATURE                                => $featureDisplayName,
                Constants::DOCUMENTATION                          => $visibleFeatures[$featureName][Constants::DOCUMENTATION],
            ]
        ];

        (new DashboardNotificationHandler($data))->send();

        $this->trace->info(
            TraceCode::FEATURE_ENABLED_MERCHANT_NOTIFIED,
            [
                PublicEntity::MERCHANT_ID => $merchantId,
                Mode::LIVE                => $isLiveMode,
                Entity::NEW_FEATURE       => $feature,
            ]);
    }

    /**
     * Notify the merchant that the feature has been enabled on the live mode
     *
     * @param Merchant\Entity   $merchant
     * @param bool              $shouldSync
     * @param bool              $isLiveMode
     *
     * @return bool
     */
    protected function shouldNotifyViaNotification(
        Merchant\Entity $merchant,
        Entity $feature,
        bool $shouldSync,
        bool $isLiveMode): bool
    {
        // Notify the merchants, only if the feature is a ProductFeature
        if ($feature->isProductFeature() === false)
        {
            return false;
        }

        if (($shouldSync === false) and ($isLiveMode === false))
        {
            return false;
        }

        // Do not email Linked Accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return false;
        }

        return true;
    }

    public function getOnboardingQuestions(array $features): array
    {
        $response = [];

        foreach ($features as $feature)
        {
            $questionMap = Constants::getFeatureQuestions($feature);

            if (count($questionMap) > 0)
            {
                $response[$feature] = $questionMap;
            }
        }

        return $response;
    }

    /**
     * Returns applicable feature status . If merchant is in activated state and feature should be auto approve
     * then returns approved state else returns pending status.
     *
     * @param Merchant\Entity $merchant
     * @param string          $featureName
     *
     * @return string
     */
    private function getFeatureStatus(Merchant\Entity $merchant, string $featureName): string
    {
        $featureStatus = Merchant\Detail\Entity::PENDING;
        //
        //For instantly activated merchants or already activated merchants instantly approve subscription and
        //marketplace request.
        //

        if ((Merchant\Request\Constants::isAutoApproveFeatureRequest($merchant, $featureName)))
        {
            $featureStatus = Merchant\Detail\Entity::APPROVED;
        }
        return $featureStatus;
    }

    private function checkAuthTypeIfApplicable($feature)
    {
        $restrictedEsInvalidAuth = (($feature->getName() === Feature::ES_ON_DEMAND_RESTRICTED) &&
                                    ($feature->getEntityType() === Constants::MERCHANT) &&
                                    ($this->app['basicauth']->isAdminAuth() === true));

        if ($restrictedEsInvalidAuth)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                $feature
            );
        }

    }

    /**
     * get status of feature
     *
     * @param $entityType
     * @param $entityId
     * @param $featureName
     *
     * @return array
     */
    public function getStatus($entityType, $entityId, $featureName)
    {
        $response['status'] = false;
        try
        {
            $this->trace->info(
                TraceCode::FEATURE_GET_STATUS_REQUEST,
                [
                    Entity::FEATURE     => $featureName,
                    Entity::ENTITY_TYPE => $entityType,
                    Entity::ENTITY_ID   => $entityId,
                ]);

            $entityId = $entityId ?? $this->merchant->getId();

            $response = new Base\Collection;

            $status = $this->repo
                ->feature
                ->findByEntityTypeEntityIdAndName($entityType, $entityId, $featureName);

            $statusFactory = new Status\Factory();

            $statusProcessor = $statusFactory->getStatusInstance($status);

            $response['status'] = $statusProcessor->getFeatureStatus();
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::FEATURE_GET_STATUS_FAILED,
                [
                    Entity::FEATURE     => $featureName,
                    Entity::ENTITY_TYPE => $entityType,
                    Entity::ENTITY_ID   => $entityId,
                    'error'             => $e->getMessage()
                ]);
        }

        return $response->toArray();
    }

    private function checkCollectionsAuthTypeForDeletionIfApplicable($feature){

        $disableOndemandInvalidAuth = (  ( ($feature->getName() === Feature::DISABLE_ONDEMAND_FOR_LOAN)||
                                         ($feature->getName() === Feature::DISABLE_ONDEMAND_FOR_LOC) ||
                                         ($feature->getName() === Feature::DISABLE_CARDS_POST_DPD) ||
                                         ($feature->getName() === Feature::DISABLE_LOANS_POST_DPD) ||
                                         ($feature->getName() === Feature::DISABLE_LOC_POST_DPD) ||
                                         ($feature->getName() === Feature::DISABLE_AMAZON_IS_POST_DPD) )&&
                                         ($feature->getEntityType() === Constants::MERCHANT) &&
                                         ($this->app['basicauth']->isCapitalCollectionsApp() === false));

       if ($disableOndemandInvalidAuth)
       {
           throw new Exception\BadRequestException(
               ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                     $feature
           );
       }
    }

    private function checkCollectionsAuthTypeForCreationIfApplicable($feature){

        $disableOndemandInvalidAuth = (  ( ($feature->getName() === Feature::DISABLE_ONDEMAND_FOR_LOAN)||
                                         ($feature->getName() === Feature::DISABLE_ONDEMAND_FOR_LOC) ||
                                         ($feature->getName() === Feature::DISABLE_ONDEMAND_FOR_CARD) ||
                                         ($feature->getName() === Feature::DISABLE_CARDS_POST_DPD) ||
                                         ($feature->getName() === Feature::DISABLE_LOANS_POST_DPD) ||
                                         ($feature->getName() === Feature::DISABLE_LOC_POST_DPD)   ||
                                         ($feature->getName() === Feature::DISABLE_AMAZON_IS_POST_DPD) )&&
                                         ($feature->getEntityType() === Constants::MERCHANT) &&
                                         ($this->app['basicauth']->isCapitalCollectionsApp() === false));

       if ($disableOndemandInvalidAuth)
       {
           throw new Exception\BadRequestException(
               ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                     $feature
           );
       }
    }

    /**
     * @param string $featureToAssign The new feature to be added
     *
     * @throws Exception\BadRequestValidationFailureException if ledger integration can break
     */
    public function checkAndDisableRxLedgerAndPayoutFeatureChanges(string $featureToAssign)
    {
        if (in_array($featureToAssign, array_merge(Constants::LEDGER_FEATURES, Constants::PAYOUT_SERVICE_FEATURES), true) === true)
        {
            $this->trace->info(TraceCode::MANUAL_LEDGER_FEATURE_ASSIGNMENT_ATTEMPTED,
                [
                    'feature_to_assign'     => $featureToAssign,
                    'mode'                  => $this->mode
                ]
            );

            throw new Exception\BadRequestValidationFailureException(
                'Manually enabling/disabling ledger feature ' . $featureToAssign . ' is not allowed.'
            );
        }
    }

    /**
     * @param string $newFeatureName The new feature to be added
     * @param array $assignedFeatureNames The features already present for that entity
     *
     * @throws Exception\BadRequestValidationFailureException if ledger integration can break
     */
    protected function controlFeatureAssignmentForLedger(string $newFeatureName, array $assignedFeatureNames)
    {
        if (in_array($newFeatureName,[Constants::HIGH_TPS_COMPOSITE_PAYOUT, Constants::HIGH_TPS_PAYOUT_EGRESS], true) === true and
            in_array(Constants::LEDGER_REVERSE_SHADOW, $assignedFeatureNames, true) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Enabling ' . $newFeatureName . ' is not allowed when ' . Constants::LEDGER_REVERSE_SHADOW . ' is already enabled.'
            );
        }

        if (in_array($newFeatureName,[Constants::HIGH_TPS_COMPOSITE_PAYOUT], true) === true and
            in_array(Constants::LEDGER_JOURNAL_READS, $assignedFeatureNames, true) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Enabling ' . $newFeatureName . ' is not allowed when ' . Constants::LEDGER_JOURNAL_READS . ' is already enabled.'
            );
        }

        if (($newFeatureName === Constants::HIGH_TPS_PAYOUT_EGRESS) and
            in_array(Constants::LEDGER_JOURNAL_WRITES, $assignedFeatureNames, true) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Enabling ' . $newFeatureName . ' is not allowed when ' . Constants::LEDGER_JOURNAL_WRITES . ' is already enabled.'
            );
        }
    }

    /**
     * @param string $featureToChange The new feature to be added
     *
     * @throws Exception\BadRequestValidationFailureException if feature should not be deleted manually
     */
    public function checkAndDisableFeatureChangesForPayoutServiceIdempotencyFeatures(string $featureToChange)
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        if ($routeName === 'payout_service_idempotency_key_feature_remove')
        {
            return;
        }

        if (in_array($featureToChange, Constants::PAYOUT_SERVICE_IDEMPOTENCY_KEY_FEATURES, true) === true)
        {
            $this->trace->info(TraceCode::MANUAL_PAYOUT_SERVICE_IDEMPOTENCY_KEY_FEATURE_CHANGE_ATTEMPTED,
                               [
                                   'feature_to_change'     => $featureToChange,
                                   'mode'                  => $this->mode
                               ]
            );

            throw new Exception\BadRequestValidationFailureException(
                'Manually enabling/disabling payout service feature ' . $featureToChange . ' is not allowed.'
            );
        }
    }

    public function checkAndDisableSubAccountFeatureChange(string $featureToChange)
    {
        if (in_array($featureToChange, Feature::ACCOUNT_SUB_ACCOUNT_FEATURES) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Manually enabling/disabling sub account feature ' . $featureToChange . ' is not allowed.'
            );
        }
    }

    public function removeFeature(string $featureName, bool $shouldSync = false)
    {
        $merchant = $this->merchant;

        $entityId = $merchant->getId();
        $feature  = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
            Constants::MERCHANT,
            $entityId,
            $featureName);

        if ($feature !== null)
        {
            $this->repo->feature->deleteAndSyncIfApplicableOrFail($feature, $shouldSync);
        }
    }

    public function enablePayoutService(array $payoutServiceFeatureInput, bool $shouldSync = false): Entity
    {
        $this->trace->info(TraceCode::ENABLE_PAYOUT_SERVICE_ENABLED_FEATURE_REQUEST,
                           [
                               'input'       => $payoutServiceFeatureInput,
                               'should_sync' => $shouldSync,
                           ]
        );

        /** @var Entity $payoutServiceIdempotencyKeyFromPsToApiFeature */
        $payoutServiceIdempotencyKeyFromPsToApiFeature = $this->repo->feature
            ->findByEntityTypeEntityIdAndName(
                $payoutServiceFeatureInput[Entity::ENTITY_TYPE],
                $payoutServiceFeatureInput[Entity::ENTITY_ID],
                Constants::IDEMPOTENCY_PS_TO_API);

        $this->trace->info(TraceCode::IS_IDEMPOTENCY_PS_TO_API_FEATURE_ENABLED,
                           [
                               'is_feature_enabled' =>
                                   (empty($payoutServiceIdempotencyKeyFromPsToApiFeature) === false),
                           ]
        );

        $feature = $this->repo->feature->transaction(function() use (
            $payoutServiceFeatureInput,
            $shouldSync,
            $payoutServiceIdempotencyKeyFromPsToApiFeature
        ) {
            if (empty($payoutServiceIdempotencyKeyFromPsToApiFeature) === false)
            {
                $this->delete($payoutServiceIdempotencyKeyFromPsToApiFeature, $shouldSync);
            }

            $payoutServiceIdempotencyKeyFromApiToPsFeatureInput = [
                Entity::ENTITY_TYPE => $payoutServiceFeatureInput[Entity::ENTITY_TYPE],
                Entity::ENTITY_ID   => $payoutServiceFeatureInput[Entity::ENTITY_ID],
                Entity::NAME        => Constants::IDEMPOTENCY_API_TO_PS,
            ];

            $this->create($payoutServiceIdempotencyKeyFromApiToPsFeatureInput, $shouldSync);

            return $this->create($payoutServiceFeatureInput, $shouldSync);
        });

        $this->trace->info(TraceCode::ENABLE_PAYOUT_SERVICE_ENABLED_FEATURE_RESPONSE,
                           [
                               'feature' => $feature->toArray(),
                           ]
        );

        if (empty($payoutServiceIdempotencyKeyFromPsToApiFeature) === false)
        {
            (new Merchant\Service)->deleteTag($payoutServiceIdempotencyKeyFromPsToApiFeature->getEntityId(),
                                              $payoutServiceIdempotencyKeyFromPsToApiFeature->getName());

            $this->trace->info(TraceCode::DELETE_TAG_FOR_IDEMPOTENCY_PS_TO_API_FEATURE_SUCCESS,
                               [
                                   'success' => true,
                               ]
            );
        }

        return $feature;
    }

    public function disablePayoutService(Entity $payoutServiceFeature, bool $shouldSync = false)
    {
        $this->trace->info(TraceCode::DISABLE_PAYOUT_SERVICE_ENABLED_FEATURE_REQUEST,
                           [
                               'input'       => $payoutServiceFeature->toArray(),
                               'should_sync' => $shouldSync,
                           ]
        );

        /** @var Entity $payoutServiceIdempotencyKeyFromApiToPsFeature */
        $payoutServiceIdempotencyKeyFromApiToPsFeature = $this->repo->feature
            ->findByEntityTypeEntityIdAndName(
                $payoutServiceFeature->getEntityType(),
                $payoutServiceFeature->getEntityId(),
                Constants::IDEMPOTENCY_API_TO_PS);

        $this->trace->info(TraceCode::IS_IDEMPOTENCY_API_TO_PS_FEATURE_ENABLED,
                           [
                               'is_feature_enabled' =>
                                   (empty($payoutServiceIdempotencyKeyFromApiToPsFeature) === false),
                           ]
        );

        /** @var Entity $payoutServiceFetchVaPayoutsViaPsFeature */
        $payoutServiceFetchVaPayoutsViaPsFeature = $this->repo->feature->findByEntityTypeEntityIdAndName(
                $payoutServiceFeature->getEntityType(),
                $payoutServiceFeature->getEntityId(),
                Constants::FETCH_VA_PAYOUTS_VIA_PS);

        $this->trace->info(TraceCode::IS_FETCH_VA_PAYOUTS_VIA_PS_FEATURE_ENABLED,
                           [
                               'is_feature_enabled' =>
                                   (empty($payoutServiceFetchVaPayoutsViaPsFeature) === false),
                           ]
        );

        $feature = $this->repo->feature->transaction(function() use (
            $payoutServiceFeature,
            $shouldSync,
            $payoutServiceIdempotencyKeyFromApiToPsFeature,
            $payoutServiceFetchVaPayoutsViaPsFeature
        ) {
            if (empty($payoutServiceIdempotencyKeyFromApiToPsFeature) === false)
            {
                $this->delete($payoutServiceIdempotencyKeyFromApiToPsFeature, $shouldSync);
            }

            $payoutServiceIdempotencyKeyFromPsToApiFeatureInput = [
                Entity::ENTITY_TYPE => $payoutServiceFeature->getEntityType(),
                Entity::ENTITY_ID   => $payoutServiceFeature->getEntityId(),
                Entity::NAME        => Constants::IDEMPOTENCY_PS_TO_API,
            ];

            $this->create($payoutServiceIdempotencyKeyFromPsToApiFeatureInput, $shouldSync);

            $this->delete($payoutServiceFeature, $shouldSync);

            if (empty($payoutServiceFetchVaPayoutsViaPsFeature) === false)
            {
                $this->delete($payoutServiceFetchVaPayoutsViaPsFeature, $shouldSync);
            }
        });

        $this->trace->info(TraceCode::DISABLE_PAYOUT_SERVICE_ENABLED_FEATURE_RESPONSE,
                           [
                               'feature' => $payoutServiceFeature->toArrayDeleted(),
                           ]
        );

        if (empty($payoutServiceIdempotencyKeyFromApiToPsFeature) === false)
        {
            (new Merchant\Service)->deleteTag($payoutServiceIdempotencyKeyFromApiToPsFeature->getEntityId(),
                                              $payoutServiceIdempotencyKeyFromApiToPsFeature->getName());

            $this->trace->info(TraceCode::DELETE_TAG_FOR_IDEMPOTENCY_API_TO_PS_FEATURE_SUCCESS,
                               [
                                   'success' => true,
                               ]
            );
        }

        return $feature;
    }
}
