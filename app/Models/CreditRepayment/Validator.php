<?php

namespace RZP\Models\CreditRepayment;

class Validator
{
    public static $createTransactionInput = [
        Entity::ID          => 'required|alpha_num|size:14',
        Entity::AMOUNT      => 'required|int|min:1',
        Entity::CURRENCY    => 'required|string|in:INR',
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
    ];
}
