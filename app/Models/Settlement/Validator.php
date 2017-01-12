<?php

namespace RZP\Models\Settlement;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $batchFetchRules = [
        Entity::BATCH_SETTLEMENT_ID => 'required|alpha_num|size:14',
    ];
}