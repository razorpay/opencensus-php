<?php

namespace RZP\Models\Admin;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $sendTestNewsletterRules = [
        'msg'     => 'required|max:10000',
        'subject' => 'required|max:200',
        'email'   => 'required|email',
        'template'=> 'required|max:255'
    ];

    protected static $sendNewsletterRules = [
        'msg'     => 'required|max:10000',
        'subject' => 'required|max:200',
        'lists'   => 'required|max:100',
        'template'=> 'required|max:255'
    ];
    
    protected static $processEmailFailureRules = [
        'token'             => 'required',
        'signature'         => 'required',
        'timestamp'         => 'required',
        'recipient'         => 'required',
        'event'             => 'sometimes',
        'X-Mailgun-Sid'     => 'sometimes',
        'domain'            => 'sometimes',
        'X-Mailgun-Tag'     => 'sometimes',
        'message-headers'   => 'sometimes',
        'Message-Id'        => 'sometimes',
        'body-plain'        => 'sometimes'
    ];
}
