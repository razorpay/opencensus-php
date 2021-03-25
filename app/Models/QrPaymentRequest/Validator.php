<?php

namespace RZP\Models\QrPaymentRequest;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::IS_CREATED            => 'sometimes',
        Entity::QR_CODE_ID            => 'required|string',
        Entity::TRANSACTION_REFERENCE => 'required|string',
        Entity::EXPECTED              => 'sometimes',
        Entity::REQUEST_SOURCE        => 'sometimes',
        Entity::BHARAT_QR_ID          => 'sometimes',
        Entity::UPI_ID                => 'sometimes',
        Entity::FAILURE_REASON        => 'sometimes',
        Entity::REQUEST_PAYLOAD       => 'sometimes',
    ];
}
