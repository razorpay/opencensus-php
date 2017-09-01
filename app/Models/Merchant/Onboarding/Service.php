<?php

namespace RZP\Models\Merchant\Onboarding;

use RZP\Models\Base;
use RZP\Models\Settings;
use RZP\Trace\TraceCode;

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
                $response[Constants::QUESTIONS][$feature] = $questionMap;
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

        $userResponses = $input['responses'];

        $settingsMap = [];

        foreach ($userResponses as $featureName => $userQuestionMaps)
        {
            // Ignore, if the feature sent is not found in the Constants defined
            if (array_key_exists($featureName, $featureQuestionsMap) === false)
            {
                $this->trace->info(TraceCode::ONBOARDING_FEATURE_DOES_NOT_EXIST, [$featureName]);
                continue;
            }

            $questionMap = $featureQuestionsMap[$featureName];

            foreach ($userQuestionMaps as $userQuestion => $userResponse)
            {
                // Ignore, if the question key sent is not found in the Constants defined
                if (in_array($userQuestion, $questionMap) === false)
                {
                    $this->trace->info(TraceCode::ONBOARDING_FEATURE_QUESTION_DOES_NOT_EXIST, [$featureName, $userQuestion]);
                    continue;
                }

                $settingKey   = implode(".", [$featureName, $userQuestion]);

                $settingValue = json_encode($userResponse);

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
}