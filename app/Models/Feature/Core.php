<?php

namespace RZP\Models\Feature;

use Mail;
use Config;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Settings\Accessor;
use RZP\Models\Base\PublicEntity;
use RZP\Mail\Merchant\FeatureEnabled;
use RZP\Models\Merchant\SlackActions;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class Core extends Base\Core
{
    use NotifyTrait;

    /**
     * Create feature
     *
     * @param array $input
     * @param bool  $shouldSync Should the entity be save on both test and live
     *
     * @return Entity
     */
    public function create(array $input, bool $shouldSync = false): Entity
    {
        $feature = (new Entity)->build($input);

        $feature->generateId();

        $existingFeatures = $this->repo->feature->findByEntityId($feature->getEntityId());

        $assignedFeatureNames = $existingFeatures->pluck(Entity::NAME)->toArray();

        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_EDIT_REQUEST,
            [
                PublicEntity::MERCHANT_ID => $feature->getEntityId(),
                Entity::OLD_FEATURES      => $assignedFeatureNames,
                Entity::NEW_FEATURE       => $feature->getName(),
                Entity::SHOULD_SYNC       => $shouldSync
            ]);

        $this->repo->feature->saveAndSyncIfApplicableOrFail(
            $feature,
            $assignedFeatureNames,
            $shouldSync);

        $this->approveFeatureOnboardingRequestIfApplicable($feature, $shouldSync);

        $this->notifyFeatureUpdateOnSlack($feature);

        $merchantId = $input['entity_id'];

        $this->notifyMerchantIfApplicable($merchantId, $feature, $shouldSync);

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

        $this->app['workflow']
             ->setEntity($feature->getEntity())
             ->handle($original, $dirty);

        $this->repo->feature->deleteAndSyncIfApplicableOrFail($feature, $shouldSync);

        $this->notifyFeatureUpdateOnSlack($feature, true);
    }

    /**
     * notifyFeature is enabled on Live mode
     *
     * @param string $merchantId
     * @param Entity $feature
     * @param bool   $shouldSync
     */
    public function notifyMerchantIfApplicable(
        string $merchantId,
        Entity $feature,
        bool $shouldSync)
    {
        $isLiveMode = $this->isLiveMode();

        if (($feature->isProductFeature() === true) and
            (($shouldSync === true) or ($isLiveMode === true)))
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $visibleFeatures = Constants::$visibleFeaturesMap;
            $featureName     = $feature->getName();
            $merchantEmail   = $merchant->getEmail();

            $data['feature']       = $visibleFeatures[$featureName]['display_name'];
            $data['documentation'] = $visibleFeatures[$featureName]['documentation'];
            $data['contact_name']  = $merchant->getName();
            $data['contact_email'] = $merchantEmail;

            $featureUpdateEmail = new FeatureEnabled($data);

            Mail::queue($featureUpdateEmail);

            $this->trace->info(
                TraceCode::FEATURE_ENABLED_MERCHANT_NOTIFIED,
                [
                    PublicEntity::MERCHANT_ID => $merchantId,
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
                    PublicEntity::MERCHANT_ID => $merchantId,
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

        $status = $this->processOnboardingResponses(Constants::CREATE, $data, $merchant);

        return $status;
    }

    /**
     * @param string          $action
     * @param array           $data
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public function processOnboardingResponses(
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

            (new Validator)->validateInput(Constants::ONBOARDING, $data);

            // While updating the responses, the file gets overwritten,
            // so no need to delete the old file.
            $this->processFiles($data, $merchant, $action);

            $this->processOnboardingKeys($data, $merchant, $action);

            Accessor::for($merchant, Constants::ONBOARDING)
                    ->upsert($data)
                    ->save();

            // Set the product activation status as pending
            if ($action === Constants::CREATE)
            {
                $this->updateFeatureActivationStatus(
                    $merchantId,
                    $featureName,
                    Merchant\Detail\Entity::PENDING);

                $saved = true;
            }

            $this->notifyFeatureOnboardingFormSubmitOnSlack($merchant, $featureName);
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
     * Updates the feature activation status in the merchant details table
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
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_NOT_ASSIGNED,
                $featureName,
                [$featureName, $status]);
        }

        $this->repo->merchant_detail->updateFeatureActivationStatus(
            $merchant,
            $featureName,
            $status
        );

        $merchantDetail = $merchant->merchantDetail;

        $response = $merchantDetail->getFeatureOnboardingStatuses();

        return $response;
    }

    /**
     * When an admin updates the feature activation submissions,
     * all the existing responses are fetched first and the only
     * the keys present in the input are updated.
     * The old keys for in settings table
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

            foreach($inputKeys as $inputKey => $inputValue)
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

        $message .= $feature->getEntityId() . ' by ' . $user;

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

        if (isset($response[$featureName][$question]) === true)
        {
            $fileId = $response[$featureName][$question];

            $fileUrl = $this->getSignedUrl($fileId, $merchant->getId());

            $response[$featureName][$question] = $fileUrl;
        }
    }

    /**
     * @param string $fileStoreId
     * @param string $merchantId
     *
     * @return mixed
     */
    protected function getSignedUrl(string $fileStoreId, string $merchantId)
    {
        $accessor = new FileStore\Accessor;

        $signedUrls = $accessor->id($fileStoreId)->merchantId($merchantId)->getSignedUrl();

        return $signedUrls[$fileStoreId];
    }

    /**
     * Uploads the vendor agreement file to S3 via UFH for the marketplace feature
     *
     * @param   array               $input
     * @param   Merchant\Entity     $merchant
     * @param   string              $action
     */
    protected function processFiles(array & $input, Merchant\Entity $merchant, string $action)
    {
        $featureName = Constants::MARKETPLACE;

        $question = Constants::VENDOR_AGREEMENT;

        $merchantId = $merchant->getId();

        // If the input has a file, process it and
        // update the file name in the input variable.
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
        $merchantId = $feature->getEntityId();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $featureName     = $feature->getName();

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

        $this->sendFeatureActivationEmail($merchant, $feature, $shouldSync, $isLiveMode);
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
    protected function sendFeatureActivationEmail(
        Merchant\Entity $merchant,
        Entity $feature,
        bool $shouldSync,
        bool $isLiveMode)
    {
        $merchantId = $feature->getEntityId();

        if ($this->shouldNotifyViaEmail($merchant, $feature, $shouldSync, $isLiveMode) === false)
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

        $merchantEmail   = $merchant->getEmail();

        $featureName     = $feature->getName();

        $visibleFeatures = Constants::$visibleFeaturesMap;

        $data['feature']       = $visibleFeatures[$featureName]['display_name'];
        $data['documentation'] = $visibleFeatures[$featureName]['documentation'];
        $data['contact_name']  = $merchant->getName();
        $data['contact_email'] = $merchantEmail;

        $featureUpdateEmail = new FeatureEnabled($data);

        Mail::queue($featureUpdateEmail);

        $this->trace->info(
            TraceCode::FEATURE_ENABLED_MERCHANT_NOTIFIED,
            [
                PublicEntity::MERCHANT_ID => $merchantId,
                Mode::LIVE                => $isLiveMode,
                Entity::NEW_FEATURE       => $feature,
                Merchant\Entity::EMAIL    => $merchantEmail
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
    protected function shouldNotifyViaEmail(
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

    /**
     * Posts the feature onboarding responses to slack,
     * We post only when the submission is created by the merchant and not admin
     *
     * @param Merchant\Entity $merchant
     * @param string          $productName
     */
    protected function notifyFeatureOnboardingFormSubmitOnSlack(
        Merchant\Entity $merchant,
        string $productName)
    {
        if ($this->app['basicauth']->isAdminAuth() === false)
        {
            $isLive = ($merchant->isLive() === true) ? "true" : "false";

            $isActivated = ($merchant->isActivated() === true) ? "true" : "false";

            $merchantDetails = $merchant->merchantDetail;

            $submitted = (($merchantDetails !== null) and
                ($merchantDetails->isSubmitted() === true)) ? "true" : "false";

            $data = [
                'id'                        => $merchant->getId(),
                'activated'                 => $isActivated,
                'activation_form_submitted' => $submitted,
                'live'                      => $isLive,
                'product'                   => $productName
            ];

            $this->logActionToSlack($merchant, SlackActions::PRODUCT_ACTIVATION, $data);
        }
    }
}
