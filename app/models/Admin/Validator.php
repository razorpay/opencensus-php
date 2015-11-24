<?php

namespace Models\Admin;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $sendTestNewsletterRules = [
        'msg'     => 'required|max:10000',
        'subject' => 'required|max:200',
        'email'   => 'required|email'
    ];

    protected static $sendNewsletterRules = [
        'msg'     => 'required|max:10000',
        'subject' => 'required|max:200',
        'lists'   => 'required|max:100'
    ];
}
