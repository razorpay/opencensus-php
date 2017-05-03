<?php

namespace RZP\Models\Payment;

use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    // TODO:
    // (Applies for Payment, Order, Refund's EsRepository classes.)
    // - Do we want to index any other fields here?
    // - Do we want to add 'q' support?

    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
    ];
}
