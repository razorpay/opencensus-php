<?php

namespace RZP\Models\Merchant\AccountV2\BMCQuestionnaire;

use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\BusinessCategoriesV2;

class Helper
{
    /*
     *  getPendingQuestionsForMerchant returns the pending questions required for given merchant
     */
    public static function getPendingQuestionsForMerchant(array $answers = [], Detail\Entity $merchantDetails): array
    {
        $pendingQuestions = [];

        switch ($merchantDetails->getBusinessSubcategory())
        {
            case BusinessCategoriesV2\BusinessSubcategory::ELECTRONICS_AND_FURNITURE:
            case BusinessCategoriesV2\BusinessSubcategory::ACCESSORY_AND_APPAREL_STORES:
            case BusinessCategoriesV2\BusinessSubcategory::HARDWARE_EQUIPMENT_AND_SUPPLY_STORES:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_2, $answers));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::GROCERY:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_4, $answers));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::RESTAURANT:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_11, $answers));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::ACCOUNTING:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_24, $answers));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::HOSPITAL:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_43, $answers));
                break;
            default:

        }

        return $pendingQuestions;
    }

    public static function getUnAnsweredQuestions(string $questionKey, array $answers = []): array
    {
        $unansweredQuestions = [];
        if (isset($answers[$questionKey]))
        {
            if (isset(Questions::QUESTIONS_OPTIONS_MAP[$questionKey][Questions::OPTIONS]) === true)
            {
                foreach (Questions::QUESTIONS_OPTIONS_MAP[$questionKey][Questions::OPTIONS] as $optionKey => $option)
                {
                    if ( in_array($optionKey, $answers[$questionKey]) === true && isset($option[Questions::NEXT]))
                    {
                        foreach ($option[Questions::NEXT] as $nextQuestionKey)
                        {
                            $unansweredQuestions = array_merge($unansweredQuestions, static::getUnAnsweredQuestions($nextQuestionKey, $answers));
                        }
                    }
                }
            }
        }
        else
        {
            $unansweredQuestions[] = Questions::QUESTIONS_OPTIONS_MAP[$questionKey][Questions::API_KEY];
        }

        return $unansweredQuestions;
    }

    public static function transformInputToAPIInput(array $input): array
    {
        $apiInput = [];
        foreach ($input as $questionKey => $answers)
        {
            if (isset(Questions::QUESTIONS_OPTIONS_MAP[$questionKey]) === true)
            {
                $question = Questions::QUESTIONS_OPTIONS_MAP[$questionKey];
                $apiKey   = $question[Questions::API_KEY];
                $isArray = $question[Questions::TYPE] === Questions::TYPE_ARRAY;
                if (isset($question[Questions::OPTIONS]) === true)
                {
                    foreach ($question[Questions::OPTIONS] as $optionKey => $option)
                    {
                        if (in_array($optionKey, $answers) === true)
                        {
                            $apiInput[$apiKey][] = $option[Questions::VALUE];
                        }
                    }
                }
                else
                {
                    $apiInput[$apiKey] = $answers;
                }

                if ($isArray === false)
                {
                    $apiInput[$apiKey] = $apiInput[$apiKey][0];
                }
            }
        }
        return $apiInput;
    }

    public static function transformInputToPGOSInput(array $input): array
    {
        $pgosInput = [];
        foreach ($input as $apiKey => $apiValue)
        {
            $answers = $apiValue;
            if (!is_array($apiValue))
            {
                $answers = [$apiValue];
            }
            if (isset(Questions::API_KEYS_TO_QUESTION_KEYS[$apiKey]) === true)
            {
                $questionKey = Questions::API_KEYS_TO_QUESTION_KEYS[$apiKey];
                $question = Questions::QUESTIONS_OPTIONS_MAP[$questionKey];
                $answerArr = [];
                if (isset($question[Questions::OPTIONS]) === true)
                {
                    foreach ($question[Questions::OPTIONS] as $optionKey => $option)
                    {
                        if (in_array($option[Questions::VALUE], $answers) === true)
                        {
                            $answerArr[] = $optionKey;
                        }
                    }
                    $pgosInput[] = [
                        "question_id" => $questionKey,
                        "answer" => $answerArr
                    ];
                }
                else
                {
                    $pgosInput[] = [
                        "question_id" => $questionKey,
                        "answer" => $answers
                    ];
                }
            }
        }
        return $pgosInput;
    }

}
