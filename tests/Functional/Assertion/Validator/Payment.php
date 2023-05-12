<?php

namespace RZP\Tests\Functional\Assertion\Validator;

use RZP\Exception;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;

class Payment extends Validator
{
    protected static $entityRules = array(
        Entity::ID                  => 'required',
        Entity::ENTITY              => 'required|in:payment',
        Entity::MERCHANT_ID         => 'sometimes',
        Entity::RECEIVER_TYPE       => 'sometimes',
        Entity::INTERNAL_ERROR_CODE => 'sometimes',
        Entity::GATEWAY             => 'sometimes',
        Entity::GATEWAY_CAPTURED    => 'sometimes',
        Entity::CALLBACK_URL        => 'sometimes',
        Entity::RECURRING           => 'sometimes',
        Entity::REFUND_AT           => 'sometimes',
        Entity::UPDATED_AT          => 'sometimes',
        'gateway_terminal_id'       => 'sometimes',
        Entity::AMOUNT              => 'required|integer',
        Entity::CURRENCY            => 'required|in:INR,USD,MYR',
        Entity::BASE_AMOUNT         => 'sometimes|integer',
        Entity::BASE_CURRENCY       => 'sometimes|string',
        Entity::STATUS              => 'required|in:created,authorized,captured,failed,refunded,pending',
        Entity::TWO_FACTOR_AUTH     => 'sometimes|nullable|in:passed,skipped,unknown,failed,not_applicable,unavailable',
        Entity::METHOD              => 'required|custom',
        Entity::CAPTURED            => 'required|boolean',
        Entity::AMOUNT_REFUNDED     => 'sometimes',
        Entity::AMOUNT_TRANSFERRED  => 'sometimes',
        Entity::AMOUNT_PAIDOUT      => 'sometimes',
        Entity::REFUND_STATUS       => 'sometimes',
        Entity::DESCRIPTION         => 'sometimes',
        Entity::CARD_ID             => 'sometimes',
        Entity::CARD                => 'sometimes',
        Entity::TRANSFER_ID         => 'sometimes',
        Entity::PAYMENT_LINK_ID     => 'sometimes',
        Entity::BANK                => 'sometimes',
        Entity::WALLET              => 'sometimes',
        Entity::VPA                 => 'sometimes|max:100',
        Entity::EMAIL               => 'sometimes|nullable|email',
        Entity::CONTACT             => 'sometimes',
        Entity::NOTES               => 'sometimes',
        Entity::ORDER_ID            => 'sometimes',
        Entity::INTERNATIONAL       => 'sometimes',
        Entity::ERROR_CODE          => 'sometimes',
        Entity::ERROR_DESCRIPTION   => 'sometimes',
        Entity::ERROR_SOURCE        => 'sometimes',
        Entity::ERROR_STEP          => 'sometimes',
        Entity::ERROR_REASON        => 'sometimes',
        Entity::FEE                 => 'required_if:status,captured,refunded|nullable|integer',
        Entity::ACQUIRER_DATA       => 'sometimes|array',
        Entity::TAX                 => 'sometimes',
        Entity::CREATED_AT          => 'sometimes',
        Entity::INVOICE_ID          => 'sometimes|nullable|string|size:18',
        Entity::CUSTOMER_ID         => 'sometimes|nullable|string|size:19',
        Entity::TOKEN_ID            => 'sometimes|nullable|string|size:20',
        Entity::DISPUTED            => 'sometimes|boolean',
        Entity::RECURRING_TYPE      => 'sometimes|nullable|string',
        Entity::AUTH_TYPE           => 'sometimes|nullable|string',
        Entity::EMI                 => 'sometimes',
        Entity::EMI_PLAN            => 'sometimes',
        Entity::DISPUTES            => 'sometimes',
        Entity::REFERENCE16         => 'sometimes',
        Entity::ACCOUNT_ID          => 'sometimes|string',
        Entity::TERMINAL_ID         => 'sometimes',
        Entity::FEE_BEARER          => 'sometimes',
        Entity::OFFERS              => 'sometimes',
        Entity::ORDER               => 'sometimes',
        Entity::LATE_AUTHORIZED     => 'sometimes',
        Entity::AUTO_CAPTURED       => 'sometimes',
        Entity::AUTHORIZED_AT       => 'sometimes',
        Entity::CAPTURED_AT         => 'sometimes',
        Entity::PROVIDER            => 'sometimes',
        Entity::OPTIMIZER_PROVIDER  => 'sometimes',
        Entity::SETTLED_BY          => 'sometimes',
        Entity::TOKEN               => 'sometimes',
    );

    protected function validateMethod($attribute, $value)
    {
        $isValid = Method::isValid($value);

        $googlePayMethod = Method::isValidPreAuthorizeGooglePayMethod($value);

        if ($isValid === false and $googlePayMethod === false)
        {
            throw new Exception\BadRequestValidationFailureException('The selected method is invalid.');
        }
    }
}
