<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Base;

class Validator extends Base\Validator
{
    const PRE_FETCH_RULES = 'pre_fetch';
    const STATUS_UPDATE_RULES = 'status_update';

    protected static $createRules = [
        Entity::BALANCE_ID                          => 'required|size:14',
        Entity::MERCHANT_ID                         => 'required|size:14',
        Entity::CHANNEL                             => 'required|custom',
        Entity::ACCOUNT_NUMBER                      => 'required|string|max:40',
        Entity::STATUS                              => 'sometimes|custom',
        Entity::GATEWAY_BALANCE                     => 'sometimes|nullable|int',
        Entity::STATEMENT_CLOSING_BALANCE           => 'sometimes|nullable|int',
        Entity::ACCOUNT_TYPE                        => 'sometimes|custom'
    ];

    protected static $preFetchRules = [
        Entity::CHANNEL             => 'required|custom',
        Entity::ACCOUNT_NUMBER      => 'required|string|max:40',
    ];

    protected static $statusUpdateRules = [
        Entity::STATUS => 'required|custom'
    ];

    protected function validateChannel($attribute, $channel)
    {
        Channel::validate($channel);
    }

    protected function validateStatus($attributes, $status)
    {
        Status::validate($status);
    }

    protected function validateAccountType($attributes, $status)
    {
        AccountType::validate($status);
    }
}
