<?php

namespace RZP\Models\Admin;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $sendTestNewsletterRules = [
        'msg'      => 'required|max:10000',
        'subject'  => 'required|max:200',
        'email'    => 'required|email',
        'template' => 'required|max:255'
    ];

    protected static $sendNewsletterRules = [
        'msg'      => 'required|max:10000',
        'subject'  => 'required|max:200',
        'lists'    => 'required|max:100',
        'template' => 'required|max:255'
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
        ConfigKey::TERMINAL_SELECTION_LOG_VERBOSE       => 'filled|boolean',
        ConfigKey::PRICING_RULE_SELECTION_LOG_VERBOSE   => 'filled|boolean',
        ConfigKey::GATEWAY_PROCESSED_REFUNDS            => 'filled|array',
        ConfigKey::GATEWAY_UNPROCESSED_REFUNDS          => 'filled|array',
        ConfigKey::SKIP_SLAVE                           => 'filled|boolean',
        ConfigKey::BLOCK_BANK_TRANSFERS_FOR_CRYPTO      => 'filled|boolean',
        ConfigKey::DISABLE_MAGIC                        => 'filled|boolean',
        ConfigKey::BLOCK_SMART_COLLECT                  => 'filled|boolean',
        ConfigKey::BLOCK_YESBANK                        => 'filled|boolean',
        ConfigKey::NPCI_UPI_DEMO                        => 'filled|array',
    ];

    protected static $scorecardRules = [
        'count'             => 'required|integer|max:100'
    ];

    protected static $setEsPricingKeyRules = [
        'on_demand' => 'sometimes|integer',
        'scheduled' => 'sometimes|integer',
    ];
}
