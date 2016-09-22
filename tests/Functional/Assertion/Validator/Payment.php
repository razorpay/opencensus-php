<?php

namespace RZP\Tests\Functional\Assertion\Validator;

use RZP\Models\Payment\Entity;

class Payment extends Validator
{
    protected static $entityRules = array(
        Entity::ID                  => 'required|',
        Entity::ENTITY              => 'required|in:payment',
        Entity::AMOUNT              => 'required|integer',
        Entity::CURRENCY            => 'required|in:INR',
        Entity::STATUS              => 'required|in:created,authorized,captured,failed,refunded',
        Entity::METHOD              => 'required|in:card,netbanking,wallet,emi',
        Entity::CAPTURED            => 'required|boolean',
        Entity::AMOUNT_REFUNDED     => 'sometimes|',
        Entity::REFUND_STATUS       => 'sometimes|',
        Entity::DESCRIPTION         => 'sometimes|',
        Entity::CARD_ID             => 'sometimes|',
        Entity::BANK                => 'sometimes|',
        Entity::WALLET              => 'sometimes|',
        Entity::VPA                 => 'sometimes|max:100',
        Entity::EMAIL               => 'sometimes|email',
        Entity::CONTACT             => 'sometimes|',
        Entity::NOTES               => 'sometimes|',
        Entity::ORDER_ID            => 'sometimes|',
        Entity::ERROR_CODE          => 'sometimes|',
        Entity::ERROR_DESCRIPTION   => 'sometimes|',
        Entity::FEE                 => 'required_if:status,captured,refunded|integer',
        Entity::SERVICE_TAX         => 'sometimes|',
        Entity::CREATED_AT          => 'sometimes|',
    );
}
