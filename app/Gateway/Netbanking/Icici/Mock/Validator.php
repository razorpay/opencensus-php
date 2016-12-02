<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Base;

use RZP\Gateway\Netbanking\Icici\RequestFields;

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


    // We don't really need them right now

}
