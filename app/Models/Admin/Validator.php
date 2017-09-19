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

    protected static $mailgunWebhookRules = [
        'token'             => 'required|string|size:50',
        'signature'         => 'required|string',
        'timestamp'         => 'required|integer',
        'recipient'         => 'required|email',
        'event'             => 'sometimes|string',
        'domain'            => 'sometimes|string',
        'message-headers'   => 'sometimes|string',
        'reason'            => 'sometimes|string',
    ];

    protected static $setConfigKeysRules = [
        ConfigKey::TERMINAL_SELECTION_LOG_VERBOSE => 'filled|boolean',
    ];
}
