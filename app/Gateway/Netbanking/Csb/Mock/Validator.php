<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Csb\Constants;
use RZP\Gateway\Netbanking\Csb\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::CHNPGSYN     => 'required|string|in:' . Constants::CHNPGSYN,
        RequestFields::CHNPGCODE    => 'required|string|in:' . Constants::CHNPGCODE,
        RequestFields::PAYEE_ID     => 'required|string',
        RequestFields::BANK_REF_NUM => 'required|string|size:14',
        RequestFields::AMOUNT       => 'required|integer',
        RequestFields::RETURN_URL   => 'required|string|url',
        RequestFields::MODE         => 'required|string|in:P',
        RequestFields::CHECKSUM     => 'required|string|size:8'
    ];

    protected static $verifyRules = [
        RequestFields::CHNPGSYN     => 'required|string|in:' . Constants::CHNPGSYN,
        RequestFields::CHNPGCODE    => 'required|string|in:' . Constants::CHNPGCODE,
        RequestFields::PAYEE_ID     => 'required|string',
        RequestFields::BANK_REF_NUM => 'required|string|size:14',
        RequestFields::AMOUNT       => 'required|integer',
        RequestFields::TRAN_REF_NUM => 'required|integer|in:9999999999',
        RequestFields::MODE         => 'required|string|in:V',
        RequestFields::CHECKSUM     => 'required|string|size:8'
    ];
}
