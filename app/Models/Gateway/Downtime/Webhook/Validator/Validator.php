<?php

namespace RZP\Models\Gateway\Downtime\Webhook\Validator;

use RZP\Base;
use RZP\Models\Gateway\Downtime as GatewayDowntime;

class Validator extends Base\Validator
{
    const FPX_CRON  = 'fpx_cron';

    protected static $fpxCronRules = [
        GatewayDowntime\Entity::TERMINAL_ID => 'required|public_id'
    ];



}
