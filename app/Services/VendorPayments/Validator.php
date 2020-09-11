<?php

namespace RZP\Services\VendorPayments;

use RZP\Base;

class Validator extends Base\Validator
{
    const SEND_MAIL = 'send_mail';

    protected static $sendMailRules = [
        'to_email'      => 'required|email',
        'data'          => 'required|array',
        'subject'       => 'required|string',
        'template_name' => 'required|string',
    ];
}
