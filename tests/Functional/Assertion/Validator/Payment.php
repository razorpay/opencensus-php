<?php

namespace RZP\Tests\Functional\Assertion\Validator;

use RZP\Models\Payment\Entity;

class Payment extends Validator
{
    protected static $entityRules = array(
        Entity::ID                  => 'required|',
        Entity::ENTITY              => 'required|in:payment',
        Entity::AMOUNT              => 'required|integer',
        Entity::CURRENCY            => 'required|in:INR,USD',
        Entity::BASE_AMOUNT         => 'sometimes|integer',
        Entity::STATUS              => 'required|in:created,authorized,captured,failed,refunded',
        Entity::TWO_FACTOR_AUTH     => 'sometimes|nullable|in:passed,skipped,unknown,failed,not_applicable,unavailable',
        Entity::METHOD              => 'required|in:card,netbanking,wallet,emi,transfer,bank_transfer',
        Entity::CAPTURED            => 'required|boolean',
        Entity::AMOUNT_REFUNDED     => 'sometimes|',
        Entity::AMOUNT_TRANSFERRED  => 'sometimes|',
        Entity::AMOUNT_PAIDOUT      => 'sometimes|',
        Entity::REFUND_STATUS       => 'sometimes|',
        Entity::DESCRIPTION         => 'sometimes|',
        Entity::CARD_ID             => 'sometimes|',
        Entity::CARD                => 'sometimes|',
        Entity::TRANSFER_ID         => 'sometimes|',
        Entity::BANK                => 'sometimes|',
        Entity::WALLET              => 'sometimes|',
        Entity::VPA                 => 'sometimes|max:100',
        Entity::EMAIL               => 'sometimes|nullable|email',
        Entity::CONTACT             => 'sometimes|',
        Entity::NOTES               => 'sometimes|',
        Entity::ORDER_ID            => 'sometimes|',
        Entity::INTERNATIONAL       => 'sometimes|',
        Entity::ERROR_CODE          => 'sometimes|',
        Entity::ERROR_DESCRIPTION   => 'sometimes|',
        Entity::FEE                 => 'required_if:status,captured,refunded|nullable|integer',
        Entity::ACQUIRER_DATA       => 'sometimes|array',
        Entity::TAX                 => 'sometimes|',
        Entity::CREATED_AT          => 'sometimes|',
        Entity::INVOICE_ID          => 'sometimes|nullable|string|size:18',
        Entity::CUSTOMER_ID         => 'sometimes|nullable|string|size:19',
        Entity::TOKEN_ID            => 'sometimes|nullable|string|size:20',
        Entity::DISPUTED            => 'sometimes|boolean',
        Entity::RECURRING_TYPE      => 'sometimes|nullable|string',
    );
}
