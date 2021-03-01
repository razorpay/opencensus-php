<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $adminCreateRules = [
        Entity::MERCHANT_ID                => 'required|alpha_num|size:14',
        Entity::BALANCE_ID                 => 'required|alpha_num|size:14',
        Entity::STATUS                     => 'required|string|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::REJECTED,
        Entity::PAYER_NAME                 => 'required|string|max:255',
        Entity::PAYER_ACCOUNT_NUMBER       => 'required|alpha_num|between:5,40',
        Entity::PAYER_IFSC                 => 'required|alpha_num|size:11',
        Entity::CREATED_BY                 => 'sometimes|string|max:255',
        Entity::FUND_ACCOUNT_VALIDATION_ID => 'sometimes|string|between:14,18',
        Entity::TYPE                       => 'sometimes|in:'.Type::BANK_ACCOUNT,
        Entity::REMARKS                    => 'sometimes|string|max:255',
        Entity::NOTES                      => 'sometimes|string|max:255',
    ];

    protected static $createRules      = [
        Entity::MERCHANT_ID                => 'required|alpha_num|size:14',
        Entity::BALANCE_ID                 => 'required|alpha_num|size:14',
        Entity::STATUS                     => 'required|string|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::REJECTED,
        Entity::PAYER_NAME                 => 'required|string|max:255',
        Entity::PAYER_ACCOUNT_NUMBER       => 'required|alpha_num|between:5,40',
        Entity::PAYER_IFSC                 => 'required|alpha_num|size:11',
        Entity::CREATED_BY                 => 'sometimes|string|max:255',
        Entity::FUND_ACCOUNT_VALIDATION_ID => 'sometimes|alpha_num|size:14',
        Entity::TYPE                       => 'sometimes|in:'.Type::BANK_ACCOUNT,
        Entity::REMARKS                    => 'sometimes|string|max:255',
        Entity::NOTES                      => 'sometimes|string|max:255',
    ];

    protected static $adminEditRules   = [
        Entity::MERCHANT_ID                => 'filled|alpha_num|size:14',
        Entity::BALANCE_ID                 => 'filled|alpha_num|size:14',
        Entity::PAYER_ACCOUNT_NUMBER       => 'filled|alpha_num|between:5,40',
        Entity::PAYER_IFSC                 => 'filled|alpha_num|size:11',
        Entity::STATUS                     => 'sometimes|string|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::REJECTED,
        Entity::PAYER_NAME                 => 'sometimes|string|max:255',
        Entity::CREATED_BY                 => 'sometimes|string|max:255',
        Entity::TYPE                       => 'sometimes|in:'.Type::BANK_ACCOUNT,
        Entity::REMARKS                    => 'sometimes|string|max:255',
        Entity::NOTES                      => 'sometimes|string|max:255',
        Entity::FUND_ACCOUNT_VALIDATION_ID => 'sometimes|string|between:14,18',
    ];

    protected static $merchantDashboardCreateRules = [
        Entity::BALANCE_ID           => 'required|alpha_num|size:14',
        Entity::PAYER_NAME           => 'required|string|max:255',
        Entity::PAYER_ACCOUNT_NUMBER => 'required|alpha_num|between:5,40',
        Entity::PAYER_IFSC           => 'required|alpha_num|size:11',
        Entity::NOTES                => 'sometimes|string|max:255',
        Entity::TYPE                 => 'sometimes|in:' . Type::BANK_ACCOUNT,
    ];
}
