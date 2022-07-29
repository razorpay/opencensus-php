<?php

namespace RZP\Models\Transaction\FeeBreakupNew;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'fee_breakup_new';

    protected $appFetchParamRules = array(
        Entity::TRANSACTION_ID          => 'sometimes|alpha_num|size:14',
        Entity::PRICING_RULE_ID         => 'sometimes|alpha_num|size:14',
    );
}
