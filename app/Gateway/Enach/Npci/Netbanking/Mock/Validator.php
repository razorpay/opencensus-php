<?php

namespace RZP\Gateway\Enach\Npci\Netbanking\Mock;

use RZP\Base;
use RZP\Gateway\Enach\Npci\Netbanking\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        RequestFields::MERCHANT_ID      => 'required|string',
        RequestFields::REQUEST_XML      => 'required|string',
        RequestFields::CHECKSUM         => 'required|string',
        RequestFields::BANK_ID          => 'required|string',
        RequestFields::AUTH_MODE        => 'required|string',
        RequestFields::SPID             => 'required|string',
    );

    protected static $verifyRules = array(
        RequestFields::MERCHANT_ID      => 'required|string',
        RequestFields::MANDATE_ID       => 'required|alpha_num|size:14',
        RequestFields::REQ_INIT_DATE    => 'required|date_format:Y-m-d',
    );
}
