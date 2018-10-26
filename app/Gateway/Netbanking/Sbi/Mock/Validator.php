<?php

namespace RZP\Gateway\Netbanking\Sbi\Mock;

use RZP\Gateway\Netbanking\Sbi\RequestFields;

class Validator
{
    protected static $authRules = [
        RequestFields::TREIRB_ID        => 'required|string',
        RequestFields::TREIRB_AMT       => 'required|numeric',
        RequestFields::REDIRECT_URL     => 'required|url',
    ];
}
