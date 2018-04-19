<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Csb\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::CHNPGSYN     => 'required|string',
        RequestFields::CHNPGCODE    => 'required|string',
        RequestFields::PAYEE_ID     => 'required|string',
        RequestFields::BANK_REF_NUM => 'required|string|size:14',
        RequestFields::AMOUNT       => 'required|numeric',
        RequestFields::RETURN_URL   => 'required|string|url',
        RequestFields::MODE         => 'required|string|in:P',
        RequestFields::CHECKSUM     => 'required|string'
    ];

    protected static $verifyRules = [
        RequestFields::CHNPGSYN     => 'required|string',
        RequestFields::CHNPGCODE    => 'required|string',
        RequestFields::PAYEE_ID     => 'required|string',
        RequestFields::BANK_REF_NUM => 'required|string|size:14',
        RequestFields::AMOUNT       => 'required|numeric',
        RequestFields::RETURN_URL   => 'required|string|url',
        RequestFields::TRAN_REF_NUM => 'sometimes',
        RequestFields::MODE         => 'required|string|in:V,S',
        RequestFields::CHECKSUM     => 'required|string'
    ];
}
