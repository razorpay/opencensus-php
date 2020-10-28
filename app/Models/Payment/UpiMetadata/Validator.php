<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Base;
use RZP\Models\Vpa;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base\ProviderCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::FLOW        => 'required|string|in:collect,intent,omnichannel',
        Entity::TYPE        => 'required|string|in:otm,default,recurring',
        Entity::START_TIME  => 'required_if:type,otm|epoch',
        Entity::END_TIME    => 'required_if:type,otm|epoch',
        Entity::VPA         => 'sometimes|string',
        Entity::EXPIRY_TIME => 'sometimes|integer|between:5,5760|filled',
        Entity::PROVIDER    => 'sometimes|string',
        Entity::APP         => 'sometimes|string',
        Entity::ORIGIN      => 'sometimes|string',
        Entity::FLAG        => 'sometimes|string',
        Entity::MODE        => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::VPA         => 'sometimes|string',
        Entity::NPCI_TXN_ID => 'sometimes|string',
        Entity::REFERENCE   => 'sometimes|string',
        Entity::RRN         => 'sometimes|string',
        Entity::UMN         => 'sometimes|string',
        Entity::REMIND_AT   => 'sometimes|nullable|epoch',
        Entity::ORIGIN      => 'sometimes|string',
    ];
}
