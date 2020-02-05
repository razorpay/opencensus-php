<?php

namespace RZP\Models\UpiTransfer;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT                => 'required|integer|min:0',
        Entity::GATEWAY               => 'required|string',
        // No validation on max size as it can be anything sent by gateway
        Entity::PAYER_VPA             => 'required|string',
        Entity::PAYEE_VPA             => 'required|string|max:40',
        Entity::TRANSACTION_TIME      => 'required',
        Entity::PAYER_ACCOUNT         => 'nullable|string|max:40',
        Entity::PAYER_BANK            => 'nullable|string|max:40',
        Entity::PAYER_IFSC            => 'nullable|string',
        Entity::GATEWAY_MERCHANT_ID   => 'required|string',
        Entity::NPCI_REFERENCE_ID     => 'nullable|string|max:40',
        Entity::PROVIDER_REFERENCE_ID => 'required|string',
        Entity::TRANSACTION_REFERENCE => 'nullable|string',
    ];
}
