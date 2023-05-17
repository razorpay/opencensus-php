<?php

namespace RZP\Models\PaymentLink\PaymentPageRecord;

use RZP\Base;

/**
 * Class Validator
 *
 * @package RZP\Models\PaymentLink\PaymentPageItem
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYMENT_LINK_ID         => 'required|alpha_num|size:14',
        Entity::BATCH_ID                => 'required|alpha_num|size:14',
        Entity::MERCHANT_ID             => 'required|alpha_num|size:14',
        Entity::PRIMARY_REFERENCE_ID    => 'required|string',
        Entity::AMOUNT                  => 'required|integer',
        Entity::EMAIL                   => 'sometimes|email',
        Entity::CONTACT                 => 'sometimes|contact_syntax',
        Entity::STATUS                  => 'required|in:unpaid',
        Entity::OTHER_DETAILS           => 'required|string',
        Entity::TOTAL_AMOUNT            => 'required|integer',
        Entity::CUSTOM_FIELD_SCHEMA     => 'required'
    ];

}
