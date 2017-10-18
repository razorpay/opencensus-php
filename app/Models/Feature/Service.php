<?php

namespace RZP\Models\Feature;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Settings\Accessor;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Spine\DataTypes\Dictionary;

class Service extends Base\Service
{
    public function addFeatures(array $input)
    {
        $featureParams = $this->buildFeatureParams($input);

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $featureCore = new Core;

        $features = $featureParams->map(function ($item) use ($featureCore, $shouldSync)
        {
            return $featureCore->create($item, $shouldSync);
        });

        return $features->toArray();
    }

    public function getFeatures(string $entityId)
    {
        $response = new Base\Collection;

        $response['assigned_features'] = $this->repo->feature->findByEntityId($entityId);

        // all_features is a list of currently available features in the system
        $response['all_features'] = array_keys(Constants::$featureValueMap);

        return $response;
    }

    public function deleteFeature(string $entityId, string $featureName, array $input)
    {
        $feature = $this->repo->feature->findByEntityIdAndNameOrFail($entityId, $featureName);

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        (new Core)->delete($feature, $shouldSync);

        // We delete the tag also along with feature.
        (new Merchant\Service)->deleteTag($entityId, $feature->getName());

        return $feature->toArrayDeleted();
    }

    public function multiAssignFeature($input)
    {
        $this->trace->info(TraceCode::FEATURE_MULTI_ASSIGN_REQUEST, $input);

        $entityIds = $input[Constants::ENTITY_IDS];

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $response = new Base\Collection;

        foreach ($entityIds as $entityId)
        {
            $featureParam = [
                Entity::ENTITY_TYPE     => $input[Entity::ENTITY_TYPE],
                Entity::ENTITY_ID       => $entityId,
                Entity::NAME            => $input[Entity::NAME]
            ];

            try
            {
                $feature = (new Core)->create($featureParam, $shouldSync);

                $response->push($feature);
            }
            catch (\Exception $e)
            {
                $this->trace->warn(
                    TraceCode::FEATURE_ASSIGNMENT_EXCEPTION,
                    [
                        'msg' => $e->getMessage()
                    ]);
            }
        }

        return $response->toArray();
    }

    public function multiRemoveFeature($input)
    {
        $this->trace->info(TraceCode::FEATURE_MULTI_REMOVE_REQUEST, $input);

        $entityIds = $input[Constants::ENTITY_IDS];

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $featureName = $input[Entity::NAME];

        $response = new Base\Collection;

        foreach ($entityIds as $entityId)
        {
            $feature = $this->repo->feature->findByEntityIdAndNameOrFail(
                        $entityId,
                        $featureName);

            if ($feature !== null)
            {
                $response->push($feature);

                (new Core)->delete($feature, $shouldSync);
            }
        }

        return $response->toArray();
    }

    public function getFeaturesForEntity($entity)
    {
        $entityId = $entity->getId();

        $data['features'] = [];

        $enabledFeatures = $entity->features
                                  ->pluck(\RZP\Models\Feature\Entity::NAME)
                                  ->toArray();

        foreach (Constants::$visibleFeaturesMap as $visibleFeature => $featureDetails)
        {
            $feature = $featureDetails['feature'];

            $isEnabled = in_array($feature, $enabledFeatures, true);

            $data['features'][] = [
                'feature'      => $visibleFeature,
                'value'        => $isEnabled,
                'display_name' => $featureDetails['display_name']
            ];
        }

        return $data;
    }

    /**
     * Returns all the questions required for onboarding features
     *
     * @param  array $input
     *
     * @return array
     */
    public function getOnboardingDetails(array $input): array
    {
        $response['questions'] = $this->getOnboardingQuestions($input);

        $response['submissions'] = $this->getOnboardingSubmissions();

        return $response;
    }

    /**
     * Returns all the questions required for onboarding features
     *
     * @param  array $input
     *
     * @return array
     */
    public function getOnboardingQuestions(array $input): array
    {
        $features = $input[Constants::FEATURES];

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
     * Saves the merchant responses to the onboarding questions
     *
     * @param array  $input
     * @param string $feature
     *
     * @return bool
     * @throws Exception\BadRequestException
     */
    public function postOnboardingSubmissions(array $input, string $feature): bool
    {
        // Prevent the merchant from re-submitting
        $data = Accessor::for($this->merchant, Constants::ONBOARDING)
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

        $status = $this->processOnboardingResponses(Constants::CREATE, $data, $this->merchant);

        return $status;
    }

    /**
     * Updates the merchant responses to the onboarding questions
     *
     * @param array  $input
     * @param string $feature
     *
     * @return bool
     */
    public function updateOnboardingSubmissions(array $input, string $feature): bool
    {
        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        unset($input['merchant_id']);

        $data[$feature] = $input;

        $status = $this->processOnboardingResponses(Constants::UPDATE, $data, $merchant);

        return $status;
    }

    /**
     * @param string          $action
     * @param array           $data
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    private function processOnboardingResponses(
        string $action,
        array $data,
        Merchant\Entity $merchant): bool
    {
        $saved = true;

        $this->trace->info(
            TraceCode::FEATURE_ONBOARDING_SUBMISSION_REQUEST,
            [
                'action'      => $action,
                'data'        => $data,
                'merchant_id' => $merchant->getId()]);

        $feature = array_keys($data)[0] ?? null;

        if ($feature === null)
        {
            return false;
        }

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
            $saved = $this->repo->merchant_detail->updateFeatureActivationStatus(
                $merchant,
                $feature,
                Merchant\Detail\Entity::PENDING);
        }

        (new Core)->notifyFeatureOnboardingFormSubmitOnSlack($feature);

        return $saved;
    }

    /**
     * Returns the merchant submissions to the onboarding questions of
     * one/ all features
     *
     * @param string|null $feature
     *
     * @return Dictionary|string
     */
    public function getOnboardingSubmissions(string $feature = null)
    {
        $settings = Accessor::for($this->merchant, Constants::ONBOARDING);

        $settings = ($feature === null) ? $settings->all() : $settings->get($feature);

        $settings = $this->addFileUrlInResponseIfApplicable($settings);

        return $settings;
    }

    /**
     * Adds the vendor_agreement file URL to the response if the feature is marketplace
     *
     * @param $settings
     *
     * @return Dictionary
     */
    protected function addFileUrlInResponseIfApplicable($settings): Dictionary
    {
        if (isset($settings[Constants::MARKETPLACE][Constants::VENDOR_AGREEMENT]) === true)
        {
            $settings = $settings->toArray();

            $fileId = $settings[Constants::MARKETPLACE][Constants::VENDOR_AGREEMENT];

            $fileUrl = $this->getSignedUrl($fileId, $this->merchant->getId());

            $settings[Constants::MARKETPLACE][Constants::VENDOR_AGREEMENT] = $fileUrl;
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

    private function buildFeatureParams($input)
    {
        $featureParams = new Base\Collection;

        $entityType = $input[Entity::ENTITY_TYPE];

        $entityId = $input[Entity::ENTITY_ID];

        $featureNames = $input[Constants::NAMES];

        foreach ($featureNames as $featureName)
        {
            $featureParams->push([
                Entity::ENTITY_TYPE => $entityType,
                Entity::ENTITY_ID   => $entityId,
                Entity::NAME        => $featureName
            ]);
        }

        return $featureParams;
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
     * Returns the feature activation requests based on the status
     *
     * @param array $input
     *
     * @return mixed
     */
    public function getFeatureOnboardingRequests(array $input)
    {
        $status = $input['status'];

        $merchantDetails = $this->repo->merchant_detail->getFeatureOnboardingRequestsByStatus($status);

        return $merchantDetails;
    }

    /**
     * @param string $featureName
     * @param array  $input
     *
     * @return bool
     */
    public function updateFeatureActivationStatus(string $featureName, array $input): bool
    {
        $status = $input['status'];

        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $status =  $this->repo->merchant_detail->updateFeatureActivationStatus(
                        $merchant,
                        $featureName,
                        $status
                    );

        return $status;
    }
}

