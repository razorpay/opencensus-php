<?php


namespace RZP\Models\Merchant\Cron;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $runRules = [
        Constants::CRON_NAME    => "required|string",
        "start_time"            => "sometimes|int",
        "end_time"              => "sometimes|int"
    ];

}
