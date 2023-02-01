<?php

namespace RZP\Models\QrPayment;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Constants\Mode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT                => 'required|integer|min:0',
        Entity::GATEWAY               => 'required|string',
        Entity::PAYER_VPA             => 'sometimes',
        Entity::TRANSACTION_TIME      => 'sometimes|epoch',
        Entity::PROVIDER_REFERENCE_ID => 'required|string',
        Entity::METHOD                => 'required|string|in:upi,card,bank_transfer',
        Entity::MERCHANT_REFERENCE    => 'required|string',
        Entity::NOTES                 => 'sometimes|string',
    ];
}
