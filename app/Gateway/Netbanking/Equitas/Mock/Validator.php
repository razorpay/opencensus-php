<?php
namespace RZP\Gateway\Netbanking\Equitas\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Equitas\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::MERCHANT_ID                    => 'required|string|max:15',
        RequestFields::PAYMENT_ID                     => 'required|alpha_num|size:14',
        RequestFields::AMOUNT                         => 'required|string',
        RequestFields::RETURN_URL                     => 'required|url',
        RequestFields::ACCOUNT_NUMBER                 => 'required',
        RequestFields::MODE                           => 'required|in:P',
        RequestFields::DESCRIPTION                    => 'required|string',
        RequestFields::CHECKSUM                       => 'required',
    ];

    protected static $verifyRules = [
        RequestFields::MERCHANT_ID                    => 'required|string|max:15',
        RequestFields::PAYMENT_ID                     => 'required|alpha_num|size:14',
        RequestFields::AMOUNT                         => 'required|string',
        RequestFields::ACCOUNT_NUMBER                 => 'required',
        RequestFields::MODE                           => 'required|in:V',
        RequestFields::DESCRIPTION                    => 'sometimes|string',
        RequestFields::VERIFY_BANK_PAYMENT_ID         => 'sometimes',
        RequestFields::CHECKSUM                       => 'required',
    ];
}
