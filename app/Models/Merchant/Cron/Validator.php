<?php


namespace RZP\Models\Merchant\Cron;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $runRules = [
        "start_time"            => "sometimes|int",
        "end_time"              => "sometimes|int",
        "input"                 => "sometimes|array",
    ];
}
