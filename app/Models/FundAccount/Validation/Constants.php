<?php

namespace RZP\Models\FundAccount\Validation;

use Config;

class Constants
{
    const DEFAULT_PENNY_TESTING_AMOUNT   = 100;
    const DEFAULT_PENNY_TESTING_CURRENCY = 'INR';
    const IFSC_CODE                      = 'ifsc_code';
    const DEFAULT_INDIA_COUNTRY_CODE     = '+91';

    const CACHE                          = 'Cache';

    const PENNILESS                      = 'Penniless';

    const TYPE_OPTIMIZED                      = "optimized";

    const TYPE_PENNYDROP                     = "pennydrop";

    const TYPE_PENNILESS                    = "penniless";

    const REASON_COMPLETED                  = "validation_completed";

    const SOURCE_COMPLETED                  = "beneficiary_bank";

    const DESC_COMPLETED                    = "validation request is completed";

    const REASON_CEATED                     = "validation_request_created";

    const SOURCE_CREATED                    = "internal";

    const DESC_CREATED                    = "validation request is created";


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
