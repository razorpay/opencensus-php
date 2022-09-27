<?php

namespace RZP\Models\Gateway\Priority;

use RZP\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;

class Validator extends Base\Validator
{
    protected static $validPaymentMethods = [Method::CARD, Method::NETBANKING];

    protected static $addPriorityRules = [
        Gateway::PAYSECURE        => 'sometimes|numeric|min:0|max:100',
        Gateway::HDFC             => 'sometimes|numeric|min:0|max:100',
        Gateway::ATOM             => 'sometimes|numeric|min:0|max:100',
        Gateway::AXIS_MIGS        => 'sometimes|numeric|min:0|max:100',
        Gateway::AXIS_TOKENHQ     => 'sometimes|numeric|min:0|max:100',
        Gateway::AMEX             => 'sometimes|numeric|min:0|max:100',
        Gateway::CYBERSOURCE      => 'sometimes|numeric|min:0|max:100',
        Gateway::FIRST_DATA       => 'sometimes|numeric|min:0|max:100',
        Gateway::BILLDESK         => 'sometimes|numeric|min:0|max:100',
        Gateway::EBS              => 'sometimes|numeric|min:0|max:100',
        Gateway::HITACHI          => 'sometimes|numeric|min:0|max:100',
        Gateway::CARD_FSS         => 'sometimes|numeric|min:0|max:100',
        Gateway::MPGS             => 'sometimes|numeric|min:0|max:100',
        Gateway::PAYTM            => 'sometimes|numeric|min:0|max:100',
        Gateway::ISG              => 'sometimes|numeric|min:0|max:100',
        Gateway::PAYU             => 'sometimes|numeric|min:0|max:100',
        Gateway::CASHFREE         => 'sometimes|numeric|min:0|max:100',
        Gateway::ZAAKPAY          => 'sometimes|numeric|min:0|max:100',
        Gateway::CCAVENUE         => 'sometimes|numeric|min:0|max:100',
        Gateway::PINELABS         => 'sometimes|numeric|min:0|max:100',
        Gateway::CHECKOUT_DOT_COM => 'sometimes|numeric|min:0|max:100',
        Gateway::INGENICO         => 'sometimes|numeric|min:0|max:100',
        Gateway::BILLDESK_OPTIMIZER => 'sometimes|numeric|min:0|max:100',
    ];

    public function validateMethod(string $method)
    {
        if (in_array($method, static::$validPaymentMethods, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD);
        }
    }

}
