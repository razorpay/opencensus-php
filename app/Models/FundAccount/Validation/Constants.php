<?php

namespace RZP\Models\FundAccount\Validation;

use Config;

class Constants
{
    const DEFAULT_PENNY_TESTING_AMOUNT   = 100;
    const DEFAULT_PENNY_TESTING_CURRENCY = 'INR';
    const IFSC_CODE                      = 'ifsc_code';

    const MERCHANT_ID                     = 'merchant_id';
    const KAFKA_MESSAGE_TASK_NAME         = 'task_name';
    const KAFKA_MESSAGE_DATA              = 'data';
    const BANK_ACCOUNT_VALIDATION_RESULTS = 'bank_account_validation_results';

    protected $slackSettings;

    public static function slackSettings()
    {
        return [
            'channel'  => Config::get('slack.channels.fav_logs'),
            'username' => 'fav_logs',
            'icon'     => ':x:',
        ];
    }
}
