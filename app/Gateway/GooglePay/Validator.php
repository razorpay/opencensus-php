<?php

namespace RZP\Gateway\GooglePay;

use RZP\Base\JitValidator;

class Validator extends JitValidator
{
    protected static $googlePayCardVerificationRules = [
        RequestFields::PAYMENT_ID => 'required|public_id',
    ];

    protected static $googlePayCardAuthorizationRules = [
        RequestFields::PAYMENT_ID   => 'required|public_id',
        RequestFields::CARD_TYPE    => 'required',
        RequestFields::CARD_NETWORK => 'required',
        RequestFields::AMOUNT       => 'required',
        RequestFields::TOKEN        => 'required',
        RequestFields::PG_BUNDLE    => 'sometimes',
    ];
}