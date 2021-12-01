<?php

namespace RZP\Models\Merchant\M2MReferral;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID     => 'required|string|size:14',
        Entity::REFERRER_ID     => 'sometimes|string|size:14',
        Entity::STATUS          => 'required|string|custom',
        Entity::REFERRER_STATUS => 'sometimes|string|custom',
        Entity::METADATA        => 'sometimes|array',
    ];

    protected static $editRules   = [
        Entity::METADATA        => 'sometimes|array',
        Entity::STATUS          => 'sometimes|string|custom',
        Entity::REFERRER_STATUS => 'sometimes|string|custom',
        Entity::REFERRER_ID     => 'sometimes|string|size:14',
    ];

    public function validateStatus(string $attribute, $status)
    {
        Status::validateStatus($status);
    }

    public function validateReferrerStatus(string $attribute, $status)
    {
        Status::validateReferrerStatus($status);
    }
}
