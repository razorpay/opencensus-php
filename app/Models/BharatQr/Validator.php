<?php

namespace RZP\Models\BharatQr;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::METHOD                => 'required|string|in:upi,card',
        Entity::AMOUNT                => 'required|integer',
        // No validation on max size as it can be anything sent by gateway
        Entity::PROVIDER_REFERENCE_ID => 'required|string',
        // No validation on max size of merchant reference as it can be anything in case of unexpected payments
        Entity::MERCHANT_REFERENCE    => 'required|string',
    ];

    protected static $gatewayResponseRules = [
        GatewayResponseParams::MERCHANT_REFERENCE    => 'required|string',
        GatewayResponseParams::METHOD                => 'required|in:card,upi',
        GatewayResponseParams::VPA                   => 'required_if:method,upi',
        GatewayResponseParams::CARD_FIRST6           => 'required_if:method,card',
        GatewayResponseParams::CARD_LAST4            => 'required_if:method,card',
        GatewayResponseParams::SENDER_NAME           => 'required_if:method,card',
        GatewayResponseParams::PROVIDER_REFERENCE_ID => 'required|string',
        GatewayResponseParams::AMOUNT                => 'required|integer|min:100',
        GatewayResponseParams::GATEWAY_MERCHANT_ID   => 'required_without:gateway_mpan|string',
        GatewayResponseParams::MPAN                  => 'required_without:gateway_merchant_id|string',
    ];
}

