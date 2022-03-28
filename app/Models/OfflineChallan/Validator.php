<?php

namespace RZP\Models\OfflineChallan;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::VIRTUAL_ACCOUNT_ID => 'required|alpha_num|size:14',
        Entity::CHALLAN_NUMBER     => 'required|alpha_num|size:16',
        Entity::STATUS             => 'required|in:pending',
        Entity::BANK_NAME          => 'required|in:HDFC',
        Entity::CLIENT_CODE        => 'required',
    ];

}
