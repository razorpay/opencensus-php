<?php

namespace RZP\Gateway\GooglePay;

use RZP\Base\JitValidator;

class Validator extends JitValidator
{
    protected static $googlePayCardVerificationRules = [
        RequestFields::PAYMENT_ID => 'required|public_id',
    ];
}