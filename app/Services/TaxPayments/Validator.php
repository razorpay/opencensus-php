<?php

namespace RZP\Services\TaxPayments;

use RZP\Base;

class Validator extends Base\Validator
{
    const SEND_MAIL = 'send_mail';

    protected static $sendMailRules = [
        'merchant_email' => 'required|email',
        'data'           => 'required|array',
        'subject'        => 'required|string',
        'template_name'  => 'required|string',
    ];
}
