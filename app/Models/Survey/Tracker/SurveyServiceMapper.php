<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

class SurveyServiceMapper
{
    private static $surveyClientMap = [
        Entity::NPS_PAYOUTS_DASHBOARD     => 'Payout\NpsDashboardClient',
        Entity::NPS_PAYOUTS_API           => 'Payout\NpsAPIClient',
        Entity::NPS_CSAT                  => 'BankingAccount\NpsClient',
        Entity::NPS_ACTIVE_CA             => 'Merchant\Balance\NpsClient'
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
