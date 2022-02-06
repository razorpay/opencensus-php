<?php

namespace RZP\Models\Feature;

use Mail;
use Config;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Mail\Los\LoanEligible;
use RZP\Jobs\MailingListUpdate;
use RZP\Exception\LogicException;
use RZP\Models\Settings\Accessor;
use RZP\Models\Base\PublicEntity;
use RZP\Mail\Merchant\EsEligible;
use RZP\Mail\Merchant\FeatureEnabled;
use RZP\Models\Merchant\SlackActions;
use RZP\Mail\Loc\CashAdvanceEligible;
use RZP\Notifications\Dashboard\Events;
use RZP\Jobs\SkipOnboardingCommFromHubSpot;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Settlement\OndemandFundAccount;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\Request as MerchantRequest;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Notifications\Dashboard\Constants as DashboardNotificationConstants;

class Core extends Base\Core
{
    use NotifyTrait;

    public function __construct()
    {
        parent::__construct();

        if ($this->merchant !== null)
        {
            $this->merchant->setLoadedFeaturesNull();
        }
    }

    /**
     * Create feature
     *
     * @param array $input
     * @param bool $shouldSync Should the entity be save on both test and live
     *
     * @return Entity
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function create(array $input, bool $shouldSync = false): Entity
    {
        $tokenizationGateways = $input['tokenization_gateways'] ?? [];
        unset($input['tokenization_gateways']);

        $feature = (new Entity)->build($input);

        $entityType = $input[Entity::ENTITY_TYPE];

        $entityId = $input[Entity::ENTITY_ID];

        //
        // These entity types are owned by api, hence we validate their existence
        // here before associating.
        //
        if (in_array($entityType, [Constants::MERCHANT, Constants::ACCOUNT], true) === true)
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
            if ($entityType === Constants::APPLICATION)
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

        $existingFeatures = $this->repo->feature->fetchByEntityTypeAndEntityId($entityType, $entityId);

        $assignedFeatureNames = $existingFeatures->pluck(Entity::NAME)->toArray();

        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_EDIT_REQUEST,
            [
                PublicEntity::MERCHANT_ID => $feature->getEntityId(),
                Entity::OLD_FEATURES      => $assignedFeatureNames,
                Entity::NEW_FEATURE       => $feature->getName(),
                Entity::SHOULD_SYNC       => $shouldSync
            ]);

        $this->checkAuthTypeIfApplicable($feature);

        $this->checkCollectionsAuthTypeForCreationIfApplicable($feature);

        $this->repo->feature->saveAndSyncIfApplicableOrFail(
            $feature,
            $assignedFeatureNames,
            $shouldSync);

        $this->approveFeatureOnboardingRequestIfApplicable($feature, $shouldSync);

        $this->notifyFeatureUpdateOnSlack($feature);

        if (($feature->getName() === Feature::USE_MSWIPE_TERMINALS) && ($feature->getEntityType() === Constants::MERCHANT))
        {
            (new Terminal\Service())->addMswipeTerminals($feature->getEntityId());
        }

        if (($feature->getName() === Feature::ES_ON_DEMAND) && ($feature->getEntityType() === Constants::MERCHANT))
        {
            (new Merchant\Service)->addMerchantToOnDemandEnabledMailingList($feature->getEntityId());

            (new OndemandFundAccount\Service)->dispatchSettlementOndemandFundAccountCreateJob($feature->getEntityId());
        }

        if (($feature->getName() === Feature::SKIP_SUBM_ONBOARDING_COMM) && ($feature->getEntityType() === Constants::MERCHANT)
            && ($this->mode === Mode::LIVE))
        {
            $partner = $this->repo->merchant->findOrFailPublic($entityId);

            $partnerEmail = $partner->getEmail();

            $appIds = (new Merchant\Core())->getPartnerApplicationIds($partner);

            $subMerchants = $this->repo->merchant->fetchSubmerchantsByAppIds($appIds);

            $subMerchantEmails = $subMerchants->pluck(Merchant\Entity::EMAIL)->toArray();

            $subMerchantEmailChunks = array_chunk($subMerchantEmails, 500);

            foreach ($subMerchantEmailChunks as $subMerchantEmailChunk)
            {
                SkipOnboardingCommFromHubSpot::dispatch($this->mode, $entityId, $partnerEmail, $subMerchantEmailChunk);
            }
        }

        if($feature->getName() === Feature::ONBOARD_TOKENIZATION && $feature->isMerchantFeature() === true)
        {
           $merchant = $this->repo->merchant->findOrFailPublic($entityId);

           (new Token\Core())->onboardMerchant($merchant, $tokenizationGateways);
        }

        $this->notifyMerchantOfFeatureActivationIfApplicable($entityType, $entityId, $feature, $shouldSync);

        return $feature;
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

        if(($feature->getName() === Feature::ES_ON_DEMAND) && ($feature->getEntityType() === Constants::MERCHANT))
        {
            (new Merchant\Service)->removeMerchantFromOnDemandEnabledMailingList($feature->getEntityId());
        }

        $this->notifyFeatureUpdateOnSlack($feature, true);
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
                ($isLiveMode === true))
        {
            $merchantEmail = $merchant->getEmail();

            $data['contact_name']  = $merchant->getName();
            $data['contact_email'] = $merchantEmail;

            $esEligibleEmail = new EsEligible($data);

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
        else if (($feature->getName() === Constants::LOC) and
                 ($isLiveMode === true))
        {
            $merchantEmail = $merchant->getEmail();

            $data['contact_name']  = $merchant->getName();
            $data['contact_email'] = $merchantEmail;

            $esEligibleEmail = new CashAdvanceEligible($data);

            Mail::queue($esEligibleEmail);

            $this->trace->info(
                TraceCode::LOC_ELIGIBLE_MERCHANT_NOTIFIED,
                [
                    PublicEntity::MERCHANT_ID => $entityId,
                    Entity::SHOULD_SYNC       => $shouldSync,
                    Mode::LIVE                => $isLiveMode,
                    Entity::NEW_FEATURE       => $feature,
                    Merchant\Entity::EMAIL    => $merchantEmail
                ]);
        }
        else if (($feature->getName() === Constants::LOAN) and
                 ($isLiveMode === true))
        {
            $merchantEmail = $merchant->getEmail();

            $data['contact_name']  = $merchant->getName();
            $data['contact_email'] = $merchantEmail;

            $loanEligibleEmail = new LoanEligible($data);

            Mail::queue($loanEligibleEmail);

            $this->trace->info(
                TraceCode::LOAN_ELIGIBLE_MERCHANT_NOTIFIED,
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
                                         ($feature->getName() === Feature::DISABLE_ONDEMAND_FOR_LOC) )&&
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
                                         ($feature->getName() === Feature::DISABLE_ONDEMAND_FOR_CARD) ) &&
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
}
