<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Base;

use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\ResponseFields;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        RequestFields::OBJ_NAME           => 'required',
        RequestFields::BAY_BANKID         => 'required',
        RequestFields::MODE               => 'required',
        RequestFields::PAYEE_ID           => 'required',
        RequestFields::SPID               => 'required',
        RequestFields::ENCRYPTED_STRING   => 'required',
    );


    protected static $verifyRules = array(
        RequestFields::OBJ_NAME                  => 'required',
        RequestFields::BAY_BANKID                => 'required',
        RequestFields::MODE                      => 'required',
        RequestFields::PAYEE_ID                  => 'required',
        RequestFields::SPID                      => 'required',
        RequestFields::AMOUNT                    => 'required',
        RequestFields::PAYMENT_REFERENCE_NUBER   => 'required',
        RequestFields::ITEM_CODE                 => 'required',
        RequestFields::CURRENCY_CODE             => 'required',
        ResponseFields::PAYMENT_DATE             => 'required',
    );

}
