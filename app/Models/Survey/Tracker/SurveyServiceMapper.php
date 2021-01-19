<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Exception;
use RZP\Error\ErrorCode;

class SurveyServiceMapper
{
    private static $surveyClientMap = [
        Entity::NPS_RAZORPAYX   => 'Payout\NpsClient'
    ];

    /**
     * @param string $type
     * @return string
     * @throws Exception\BadRequestException
     */
    public static function getClient(string $type) : string
    {
        if (empty(SurveyServiceMapper::$surveyClientMap[$type]) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_SURVEY_TYPE,
                null,
                null);
        }

        return SurveyServiceMapper::$surveyClientMap[$type];
    }
}
