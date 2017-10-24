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
     * Notifies slack about the new onboarding responses submitted.
     * Notifies only when the submission is created (by the merchant)
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

    /**
     * Sends an email to the merchant if a
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

        if (($feature->isNotifyFeature() === true) and
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
            $this->processFiles($data, $merchant);

            Accessor::for($merchant, Constants::ONBOARDING)
                    ->upsert($data)
                    ->save();

            // Set the product activation status as pending
            if ($action === Constants::CREATE)
            {
                $saved = $this->updateFeatureActivationStatus(
                    $merchantId,
                    $featureName,
                    Merchant\Detail\Entity::PENDING);
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

        $settings = $settings->toArray();

        $settings = $this->addFileUrlInResponseIfApplicable($merchant, $settings);

        return $settings;
    }

    /**
     * Updates the feature activation status in the merchant details table
     *
     * @param string $merchantId
     * @param string $featureName
     * @param string $status
     *
     * @return bool
     * @throws Exception\BadRequestException
     */
    public function updateFeatureActivationStatus(
        string $merchantId,
        string $featureName,
        string $status): bool
    {
        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $attributeName = $featureName . '_activation_status';

        $attributeName = constant(MerchantDetail::class . '::' . strtoupper($attributeName));

        if (in_array($status, Constants::ONBOARDING_STATUSES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ONBOARDING_STATUS_INVALID,
                $attributeName,
                [$featureName, $status]);
        }

        if (($status === MerchantDetail::APPROVED) and
            ($merchant->isFeatureEnabled($featureName) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_NOT_ASSIGNED,
                $attributeName,
                [$featureName, $status]);
        }

        $status =  $this->repo->merchant_detail->updateFeatureActivationStatus(
            $merchant,
            $attributeName,
            $status
        );

        return $status;
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
     * @param Merchant\Entity $merchant
     * @param                 $settings
     *
     * @return array
     */
    protected function addFileUrlInResponseIfApplicable(Merchant\Entity $merchant, $settings): array
    {
        $featureName = Constants::MARKETPLACE;

        $question = Constants::VENDOR_AGREEMENT;

        if (isset($settings[$featureName][$question]) === true)
        {
            $fileId = $settings[$featureName][$question];

            $fileUrl = $this->getSignedUrl($fileId, $merchant->getId());

            $settings[$featureName][$question] = $fileUrl;
        }

        return $settings;
    }

    /**
     * Processes the file, primarily,
     * $input['marketplace']['vendor_agreement'] right now.
     * Need to make it generic enough for any other key
     *
     * @param $input
     */
    protected function processFiles(& $input, Merchant\Entity $merchant)
    {
        $featureName = Constants::MARKETPLACE;

        $question = Constants::VENDOR_AGREEMENT;

        $merchantId = $merchant->getId();

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
}
