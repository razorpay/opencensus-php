<?php

namespace RZP\Models\Merchant\AccountV2\BMCQuestionnaire;

use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\BusinessCategoriesV2;

class Helper
{
    /*
     *  getPendingQuestionsForMerchant returns the pending questions required for given merchant
     */
    public static function getPendingQuestionsForMerchant(array $answers = [], Detail\Entity $merchantDetails, bool $all = false): array
    {
        $pendingQuestions = [];

        switch ($merchantDetails->getBusinessSubcategory())
        {
            case BusinessCategoriesV2\BusinessSubcategory::ELECTRONICS_AND_FURNITURE:
            case BusinessCategoriesV2\BusinessSubcategory::ACCESSORY_AND_APPAREL_STORES:
            case BusinessCategoriesV2\BusinessSubcategory::HARDWARE_EQUIPMENT_AND_SUPPLY_STORES:
            case BusinessCategoriesV2\BusinessSubcategory::ELECTRICAL_PARTS_AND_EQUIPMENT:
            case BusinessCategoriesV2\BusinessSubcategory::STATIONERY_SUPPLIES:
            case BusinessCategoriesV2\BusinessSubcategory::DEPARTMENT_STORES:
            case BusinessCategoriesV2\BusinessSubcategory::SECOND_HAND_STORES:
            case BusinessCategoriesV2\BusinessSubcategory::USED_AUTOMOBILE_AND_TRUCK_DEALERS:
            case BusinessCategoriesV2\BusinessSubcategory::FASHION_AND_LIFESTYLE:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_2, $answers, $all));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::GROCERY:
            case BusinessCategoriesV2\BusinessSubcategory::BAKERIES:
            case BusinessCategoriesV2\BusinessSubcategory::DAIRY_PRODUCTS:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_4, $answers, $all));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::RESTAURANT:
            case BusinessCategoriesV2\BusinessSubcategory::FOOD_COURT:
            case BusinessCategoriesV2\BusinessSubcategory::CATERING:
            case BusinessCategoriesV2\BusinessSubcategory::ONLINE_FOOD_ORDERING:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_11, $answers, $all));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::ACCOUNTING:
            case BusinessCategoriesV2\BusinessSubcategory::TAX_PAYMENTS:
            case BusinessCategoriesV2\BusinessSubcategory::TAX_PREPARATION_SERVICES:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_24, $answers, $all));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::HOSPITAL:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_43, $answers, $all));
                break;
            case BusinessCategoriesV2\BusinessSubcategory::SECOND_HAND_STORES:
            case BusinessCategoriesV2\BusinessSubcategory::USED_AUTOMOBILE_AND_TRUCK_DEALERS:
                $pendingQuestions = array_merge($pendingQuestions, static::getUnAnsweredQuestions(Questions::QUESTION_34, $answers, $all));
                break;
            default:

        }

        return $pendingQuestions;
    }

    public static function getUnAnsweredQuestions(string $questionKey, array $answers = [], bool $all = false): array
    {
        $unansweredQuestions = [];
        $currentQuestion = Questions::QUESTIONS_OPTIONS_MAP[$questionKey][Questions::API_KEY];

        if ( $all === true || isset($answers[$questionKey]) === false )
        {
            $unansweredQuestions[] = $currentQuestion;
        }

        if ( $all === true || isset($answers[$questionKey]) === true )
        {
            if (isset(Questions::QUESTIONS_OPTIONS_MAP[$questionKey][Questions::OPTIONS]) === true)
            {
                foreach (Questions::QUESTIONS_OPTIONS_MAP[$questionKey][Questions::OPTIONS] as $optionKey => $option)
                {
                    if ( ( $all === true || in_array($optionKey, $answers[$questionKey]) === true ) && isset($option[Questions::NEXT]))
                    {
                        foreach ($option[Questions::NEXT] as $nextQuestionKey)
                        {
                            $unansweredQuestions = array_merge($unansweredQuestions, static::getUnAnsweredQuestions($nextQuestionKey, $answers, $all));
                        }
                    }
                }
            }
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

    public static function transformInputToPGOSInput(Detail\Entity $merchantDetails, array $profile): array
    {
        // filtering valid question keys for BMC
        $input = array_intersect_key($profile, Questions::API_KEYS_TO_QUESTION_KEYS);
        if ( empty($input) === true )
        {
            return $input;
        }

        // filtering valid questions for the sub-category
        $allQuestionKeys = self::getPendingQuestionsForMerchant([], $merchantDetails, true);
        $input = array_only($input, $allQuestionKeys);

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

    public static function getValidations(): array
    {
        $validations = [];
        foreach(Questions::QUESTIONS_OPTIONS_MAP as $key => $value)
        {
            if (isset($value[Questions::VALIDATIONS]) === true)
            {
                $validations[$value[Questions::API_KEY]] =  $value[Questions::VALIDATIONS];
            }
            if (isset($value[Questions::ITEM_VALIDATIONS]) === true)
            {
                $validations[$value[Questions::API_KEY].'.*'] =  $value[Questions::ITEM_VALIDATIONS];
            }
        }
        return $validations;
    }

}
