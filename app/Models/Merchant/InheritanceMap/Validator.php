<?php

namespace RZP\Models\Merchant\InheritanceMap;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $postInheritanceParentRules = [
        'id'          => 'required|alpha_num',
    ];

    protected static $postInheritanceParentBulkRules = [
        'input'                         => 'required|array',
        'input.*.idempotency_key'       => 'required',
        'input.*.merchant_id'           => 'required|alpha_num',
        'input.*.parent_merchant_id'    => 'required|alpha_num',
    ];
}
