<?php

namespace RZP\Models\CapitalTransaction;

class Validator
{
    public static $createTransactionInput = [
        Entity::ID          => 'required|alpha_num|size:14',
        Entity::AMOUNT      => 'required|int',
        Entity::TYPE        => 'required|string|in:repayment_breakup,installment,charge,interest_waiver',
        Entity::CURRENCY    => 'required|string|in:INR',
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
        Entity::BALANCE_ID  => 'required|alpha_num|size:14',
    ];
}
