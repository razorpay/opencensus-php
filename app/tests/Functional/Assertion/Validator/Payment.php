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
        Entity::STATUS              => 'required|in:created,authorized,captured,failed',
        Entity::AMOUNT_REFUNDED     => 'sometimes|',
        Entity::REFUND_STATUS       => 'sometimes|',
        Entity::DESCRIPTION         => 'sometimes|',
        Entity::EMAIL               => 'sometimes|email',
        Entity::CONTACT             => 'sometimes|',
        Entity::NOTES               => 'sometimes|',
        Entity::ERROR_CODE          => 'sometimes|',
        Entity::ERROR_DESCRIPTION   => 'sometimes|',
        Entity::CREATED_AT          => 'sometimes|',
    );
}