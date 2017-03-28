<?php

namespace RZP\Models\Settlement;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $batchFetchRules = [
        Entity::BATCH_FUND_TRANSFER_ID => 'required|alpha_num|size:14',
    ];

    protected static $nodalTransferRules = [
        Entity::AMOUNT => 'required|integer|min:100|max:1000000000',
    ];

    protected static $retryRules = [
        'settlement_ids'   => 'required|array',
        'settlement_ids.*' => 'required|alpha_dash|max:20',
    ];
}