<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Axis\Emandate;
use RZP\Gateway\Netbanking\Axis\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ENCRYPTED_STRING         => 'required|string',
        RequestFields::RETURN_URL               => 'required|string|url'
    ];

    protected static $emandaterequestRules = [
        Emandate\RequestFields::DATA            => 'required|string',
    ];

    protected static $emandateauthRules = [
        Emandate\RequestFields::VERSION         => 'required|string|in:1.0',
        Emandate\RequestFields::CORP_ID         => 'required|string',
        Emandate\RequestFields::TYPE            => 'required|string|in:TEST',
        Emandate\RequestFields::REQUEST_ID      => 'required|alpha_num|size:14',
        Emandate\RequestFields::CUSTOMER_REF_NO => 'required|alpha_num|size:14',
        Emandate\RequestFields::CURRENCY        => 'required|string|in:INR',
        Emandate\RequestFields::AMOUNT          => 'required|numeric',
        Emandate\RequestFields::RETURN_URL      => 'required|string|url',
        Emandate\RequestFields::PRE_POP_INFO    => 'required|string',
        Emandate\RequestFields::RESERVE_FIELD_1 => 'required|string|in:MN',
        Emandate\RequestFields::RESERVE_FIELD_2 => 'sometimes|string',
        Emandate\RequestFields::RESERVE_FIELD_3 => 'sometimes|string',
        Emandate\RequestFields::RESERVE_FIELD_4 => 'sometimes|string',
        Emandate\RequestFields::RESERVE_FIELD_5 => 'sometimes|string',
        Emandate\RequestFields::CHECKSUM        => 'required|string',
    ];

    protected static $emandateverifyRules = [
        Emandate\RequestFields::VERSION         => 'required|string|in:1.0',
        Emandate\RequestFields::CORP_ID         => 'required|string',
        Emandate\RequestFields::TYPE            => 'required|string|in:TEST',
        Emandate\RequestFields::REQUEST_ID      => 'required|alpha_num|size:14',
        Emandate\RequestFields::CUSTOMER_REF_NO => 'required|alpha_num|size:14',
        // When we do not get the callback, we would not have BRN and would not send it
        Emandate\RequestFields::BANK_REF_NO     => 'sometimes|string',
        Emandate\RequestFields::CHECKSUM        => 'required|string',
    ];

    protected static $verifyRules = [
        RequestFields::VERIFY_PAYEE_ID_QS => 'required|string',
        RequestFields::VERIFY_ENCDATA     => 'required|string',
    ];

    protected static $corporateVerifyRules = [
        RequestFields::VERIFY_PAYEE_ID          => 'required|string',
        RequestFields::VERIFY_ITC               => 'sometimes|string',
        RequestFields::VERIFY_PRN               => 'sometimes|string|size:14',
        RequestFields::VERIFY_AMT               => 'sometimes|numeric',
        RequestFields::VERIFY_DATE              => 'sometimes|date_format:Y-m-d',
        RequestFields::VERIFY_ENCDATA           => 'sometimes|string',
    ];
}
