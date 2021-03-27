<?php

namespace RZP\Models\FundAccount\Validation;

use Config;

class Constants
{
    const DEFAULT_PENNY_TESTING_AMOUNT   = 100;
    const DEFAULT_PENNY_TESTING_CURRENCY = 'INR';
    const IFSC_CODE                      = 'ifsc_code';
    const DEFAULT_INDIA_COUNTRY_CODE     = '+91';

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
