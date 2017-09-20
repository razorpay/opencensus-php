<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Settings\Accessor;
use Razorpay\Trace\Logger as Trace;

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
     * @param $input
     *
     * @return array
     */
    public function getOnboardingQuestions($input): array
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
     */
    public function postOnboardingResponses(array $input, string $feature): bool
    {
        $data[$feature] = $input;

        $saved = false;

        $this->trace->info(
            TraceCode::FEATURE_ONBOARDING_RESPONSE_REQUEST,
            [$input, $feature]);

        (new Validator)->validateInput(Constants::ONBOARDING, $data);

        try
        {
            $this->processFiles($data);

            Accessor::for($this->merchant, Constants::ONBOARDING)
                ->upsert($data)
                ->save();

            $saved = true;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception, Trace::CRITICAL, TraceCode::FEATURE_ONBOARDING_RESPONSE_CREATION_FAILED);
        }

        return $saved;
    }

    /**
     * Returns the merchant responses to the onboarding questions of
     * one/ all features
     *
     * @param null $feature
     *
     * @return \Razorpay\Spine\DataTypes\Dictionary|string
     */
    public function getOnboardingResponses(string $feature = null)
    {
        $merchant = $this->merchant;

        if ($feature === null)
        {
            $settings = Accessor::for ($merchant, Constants::ONBOARDING)
                                ->all();

            if (isset($settings[Constants::MARKETPLACE][Constants::VENDOR_AGREEMENT]) === true)
            {
                $fileId = $settings[Constants::MARKETPLACE][Constants::VENDOR_AGREEMENT];

                $fileUrl = $this->getSignedUrl($fileId, $merchant->getId());

                $marketplaceSettings = $settings->__get(Constants::MARKETPLACE);

                $marketplaceSettings[Constants::VENDOR_AGREEMENT] = $fileUrl;

                $settings->__set(Constants::MARKETPLACE, $marketplaceSettings);
            }
        }
        else
        {
            $settings = Accessor::for ($merchant, Constants::ONBOARDING)
                                ->get($feature);
            // signedUrl is not required for this route
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
    protected function processFiles(& $input)
    {
        $featureName = Constants::MARKETPLACE;

        $question = Constants::VENDOR_AGREEMENT;

        $merchant = $this->merchant;

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
     * @param string          $extension
     * @param                 $file
     * @param string          $fileName
     * @param string          $type
     * @param Merchant\Entity $merchant
     * @param string          $store
     *
     * @return array
     */
    protected function createFile(string $extension,
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

    protected function getSignedUrl(string $fileStoreId, string $merchantId)
    {
        $accessor = new FileStore\Accessor;

        $signedUrls = $accessor->id($fileStoreId)->merchantId($merchantId)->getSignedUrl();

        return $signedUrls[$fileStoreId];
    }
}

