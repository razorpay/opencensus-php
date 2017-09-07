<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\Merchant;
use RZP\Models\FileStore;

class Service extends Base\Service
{
    public function addFeatures($input)
    {
        $featureParams = $this->buildFeatureParams($input);

        $features = $featureParams->map(function ($item)
        {
            return (new Core)->create($item);
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

    public function deleteFeature(string $entityId, string $featureName)
    {
        $feature = $this->repo->feature->findByEntityIdAndNameOrFail($entityId, $featureName);

        (new Core)->delete($entityId, $feature);

        return $feature->toArrayDeleted();
    }

    public function multiAssignFeature($input)
    {
        $this->trace->info(TraceCode::FEATURE_MULTI_ASSIGN_REQUEST, $input);

        $entityIds = $input[Constants::ENTITY_IDS];

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
                $feature = (new Core)->create($featureParam);

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

                $this->repo->deleteOrFail($feature);
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

    public function getOnboardingQuestions($input)
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

    public function createOnboardingResponses($input)
    {
        $settingsService = Settings\Service::getNewInstance();

        $featureQuestionsMap = Constants::$featureQuestionsMap;

        $settingsMap = [];

        $merchant = $this->merchant;

        $merchantId = $merchant->getId();

        foreach ($input as $featureName => $userQuestionMaps)
        {
            // Ignore, if the feature sent is not found in the Constants defined
            if (array_key_exists($featureName, $featureQuestionsMap) === false)
            {
                $this->trace->info(TraceCode::ONBOARDING_FEATURE_DOES_NOT_EXIST, [$featureName]);
                continue;
            }

            $questionsMap = Constants::getFeatureQuestions($featureName);

            foreach ($userQuestionMaps as $userQuestion => $userResponse)
            {
                // Ignore, if the question key sent is not found in the Constants defined
                if (array_key_exists($userQuestion, $questionsMap) === false)
                {
                    $this->trace->info(TraceCode::ONBOARDING_FEATURE_QUESTION_DOES_NOT_EXIST, [$featureName, $userQuestion]);
                    continue;
                }

                // example settingKey = "onboarding.marketplace.use_case"
                $settingKey   = implode(".", [$featureName, $userQuestion]);

                // json_encode is being used as the response can also be an array. Don't want to join the array based on comma's.
                $settingValue = json_encode($userResponse);

                if ($questionsMap[$userQuestion][Constants::RESPONSE_TYPE] === 'file')
                {
                    $file = $userResponse;

                    $extension = $file->extension();

                    $fileName = 'api/' . $merchantId . '/' . $settingKey;

                    $file = $this->createFile($extension, $file, $fileName, $settingKey, $merchant);

                    $filePath = $file['local_file_path'];

                    $settingValue = json_encode($filePath);
                }

                $settingsMap[$settingKey] = $settingValue;
            }
        }

        $entity = Constants::MERCHANT;

        $entityId = $merchantId;

        $settingsService->upsert($entity, $entityId, Constants::ONBOARDING, $settingsMap);

        $response = $settingsService->getAll($entity, $entityId, Constants::ONBOARDING, $settingsMap);

        $response = $response['settings'];

        $returnResponse = [];

        foreach ($response as $feature => $questionResponseMap)
        {
            $returnResponse[$feature] = [];

            foreach ($questionResponseMap as $question => $userResponse)
            {
                // json_decode, because just json_encode does not help
                $returnResponse[$feature][$question] = json_decode($userResponse);
            }
        }

        return $returnResponse;
    }

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
                Entity::ENTITY_TYPE     => $entityType,
                Entity::ENTITY_ID       => $entityId,
                Entity::NAME            => $featureName
            ]);
        }

        return $featureParams;
    }
}

