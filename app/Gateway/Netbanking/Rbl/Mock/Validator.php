<?php

namespace RZP\Gateway\Netbanking\Rbl\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Rbl\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::FORM_ID          => 'required|string|in:AuthenticationFG',
        RequestFields::TRANSACTION_FLAG => 'required|string|in:Y',
        RequestFields::FG_BUTTON        => 'required|string|in:LOAD',
        RequestFields::ACTION_LOAD      => 'sometimes|string|in:Y',
        RequestFields::BANK_ID          => 'required|string',
        RequestFields::LOGIN_FLAG       => 'sometimes|integer|in:1',
        RequestFields::USER_TYPE        => 'sometimes|integer|in:1',
        RequestFields::MENU_ID          => 'sometimes|string|in:CIMSHP',
        RequestFields::CALL_MODE        => 'sometimes|integer|in:2',
        RequestFields::CATEGORY_ID      => 'required|string',
        RequestFields::RETURN_URL       => 'required|string',
        RequestFields::QUERY_STRING     => 'required|string',
    ];

    protected static $verifyRules = [
        RequestFields::BANK_ID          => 'required|string',
        RequestFields::LANGUAGE_ID      => 'required|string|in:001',
        RequestFields::CHANNEL_ID       => 'required|string|in:I',
        RequestFields::V_LOGIN_FLAG     => 'required|integer|in:2',
        RequestFields::SERVICE_ID       => 'required|string|in:RRTSE',
        RequestFields::STATE_MODE       => 'required|string|in:N',
        RequestFields::RESPONSE_FORMAT  => 'required|string|in:XML',
        RequestFields::REQUEST_FORMAT   => 'required|string|in:NV',
        RequestFields::MULTIPLE_RECORDS => 'required|string|in:N',
        RequestFields::USER_PRINCIPAL   => 'required|string',
        RequestFields::ACCESS_CODE      => 'required|string',
        RequestFields::V_PAYEE_ID       => 'required|string',
        RequestFields::BANK_REFERENCE   => 'required|string',
        RequestFields::ENTITY_TYPE      => 'required|string|in:M',
        RequestFields::TRANS_CURRENCY   => 'required|string|in:INR',
    ];
}
