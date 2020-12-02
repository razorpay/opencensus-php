<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations;

use RZP\Base;

class Validator extends Base\Validator
{

    protected static $createRules = [
        Entity::MERCHANT_ID         => 'required|string',
        Entity::ESCALATION_TYPE     => 'required|string|max:255',
        Entity::ESCALATION_METHOD   => 'required|string|max:255',
        Entity::ESCALATION_LEVEL    => 'required|integer|',
        Entity::WORKFLOW_ID         => 'sometimes|string'
    ];
}
