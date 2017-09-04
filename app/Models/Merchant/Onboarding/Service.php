<?php

namespace RZP\Models\Merchant\Onboarding;

use RZP\Models\Base;
use RZP\Models\Settings;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;

class Service extends Base\Service
{
    public function getQuestions($input)
    {
        $features = $input[Constants::FEATURES];

        $response = [];

        foreach ($features as $feature)
        {
            $questionMap = Constants::getFeatureQuestions($feature);

            if (count($questionMap) > 0)
            {
                $response[Constants::ONBOARDING][$feature] = $questionMap;
            }
        }

        return $response;
    }

    public function createResponses($input)
    {
        $service = Settings\Service::getNewInstance();

        $entity = Constants::MERCHANT;

        $entityId = $this->merchant->getId();

        $featureQuestionsMap = Constants::$featureQuestionsMap;

        $userResponses = $input[Constants::ONBOARDING];

        $settingsMap = [];

        $merchant = $this->merchant;

        foreach ($userResponses as $featureName => $userQuestionMaps)
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
                $settingKey   = implode(".", [Constants::ONBOARDING, $featureName, $userQuestion]);

                $settingValue = json_encode($userResponse);

                if ($questionsMap[$userQuestion]['response_type'] === 'file')
                {
                    $file = $userResponse;
                    $extension = $file->extension();
                    $fileName = 'api/' . $merchant->getId() . '/' . $settingKey;

                    $file = $this->createFile($extension, $file, $fileName, $settingKey, $merchant);
                    s($file);
                    $settingValue = FileStore\Entity::verifyIdAndSilentlyStripSign($file['id']);
                }

                $settingsMap[$settingKey] = $settingValue;
            }
        }

        $service->upsert($entity, $entityId, $settingsMap);

        $response = $service->getAll($entity, $entityId);

        return $response;
    }

    public function getResponses($feature = null)
    {
        $service = Settings\Service::getNewInstance();

        $entity = Constants::MERCHANT;

        $entityId = $this->merchant->getId();

        if ($feature === null)
        {
            $response = $service->getAll($entity, $entityId);
        }
        else
        {
            $response = $service->get($entity, $entityId, $feature);
        }

        return $response;
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
}