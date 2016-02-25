<?php

namespace Tests\Functional\Assertion\Validator;

use Models\Payment\Entity;

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
        Entity::EMI_PLAN_ID         => 'required_if:method,emi',
        Entity::AMOUNT_REFUNDED     => 'sometimes|',
        Entity::REFUND_STATUS       => 'sometimes|',
        Entity::DESCRIPTION         => 'sometimes|',
        Entity::EMAIL               => 'sometimes|email',
        Entity::CONTACT             => 'sometimes|',
        Entity::NOTES               => 'sometimes|',
        Entity::ORDER_ID            => 'sometimes|',
        Entity::ERROR_CODE          => 'sometimes|',
        Entity::ERROR_DESCRIPTION   => 'sometimes|',
        Entity::FEE                 => 'required_if:status,captured,refunded|integer',
        Entity::SERVICE_TAX         => 'required_with:fee|integer',
        Entity::CREATED_AT          => 'sometimes|',
    );
}
