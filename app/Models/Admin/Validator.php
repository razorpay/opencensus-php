<?php

namespace RZP\Models\Admin;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Exception;

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
        ConfigKey::TERMINAL_SELECTION_LOG_VERBOSE     => 'filled|boolean',
        ConfigKey::PRICING_RULE_SELECTION_LOG_VERBOSE => 'filled|boolean',
        ConfigKey::GATEWAY_PROCESSED_REFUNDS          => 'filled|array',
        ConfigKey::GATEWAY_UNPROCESSED_REFUNDS        => 'filled|array',
        ConfigKey::MASTER_PERCENT                     => 'filled|integer',
        ConfigKey::BLOCK_BANK_TRANSFERS_FOR_CRYPTO    => 'filled|boolean',
        ConfigKey::DISABLE_MAGIC                      => 'filled|boolean',
        ConfigKey::BLOCK_SMART_COLLECT                => 'filled|boolean',
        ConfigKey::BLOCK_YESBANK                      => 'filled|boolean',
        ConfigKey::BLOCK_AADHAAR_REG                  => 'filled|boolean',
        ConfigKey::NPCI_UPI_DEMO                      => 'filled|array',
        ConfigKey::MERCHANT_ENACH_CONFIGS             => 'filled|array',
        ConfigKey::HEARTBEAT_ENABLED                  => 'filled|boolean',
        ConfigKey::HEARTBEAT_FORCE_RUN                => 'filled|boolean',
        ConfigKey::HEARTBEAT_MOCK                     => 'filled|boolean',
        ConfigKey::HEARTBEAT_TIME_THRESHOLD           => 'filled|integer',
        ConfigKey::HEARTBEAT_TRAFFIC_PERCENTAGE       => 'filled|integer',
        ConfigKey::HEARTBEAT_SLAVE_TIME_THRESHOLD     => 'filled|integer',
        ConfigKey::HEARTBEAT_ROUTES                   => 'filled|array',
        ConfigKey::HITACHI_DYNAMIC_DESCR_ENABLED      => 'filled|boolean',
        ConfigKey::CPS_SERVICE_ENABLED                => 'filled|boolean',
        ConfigKey::SETTLEMENT_TRANSACTION_LIMIT       => 'filled|integer',
        ConfigKey::FTS_ROUTE_PERCENTAGE               => 'filled|integer',
        ConfigKey::ENABLE_PAYMENT_DOWNTIMES           => 'filled|boolean',
        ConfigKey::FTS_TEST_MERCHANT                  => 'filled|string|size:14',
        ConfigKey::CURL_INFO_LOG_VERBOSE              => 'filled|boolean',
    ];

    protected static $setRedisKeysRules = [
        ConfigKey::FTS_CHANNELS     => 'filled|array',
        ConfigKey::HEARTBEAT_ROUTES => 'filled|array',
    ];

    protected static $updateRedisKeysRules = [
        'key'   => 'required|in:config:fts_channels,config:heartbeat_routes',
        'value' => 'array',
    ];

    protected static $getRedisKeyRules = [
        'key'   => 'required|in:config:fts_channels,config:heartbeat_routes'
    ];

    protected static $scorecardRules = [
        'count'             => 'required|integer|max:100'
    ];

    protected static $updateConfigKeyRules = [
        'key'   => 'required|in:merchant_enach_configs',
        'path'  => 'required|string',
        'value' => 'required|string',
    ];

    protected static $getConfigKeyRules = [
        'key'   => 'required|in:merchant_enach_configs,settlement_transaction_limit,'.ConfigKey::GATEWAY_UNPROCESSED_REFUNDS
    ];

    protected static $deleteConfigKeyRules = [
        'key'   => 'required|in:merchant_enach_configs',
        'path'  => 'required|string',
    ];

    protected static $updateConfigKeyValidators = [
        'update_config_value'
    ];

    /**
     * @param array $input
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateUpdateConfigValue(array $input)
    {
        // Checks if the values passed for eSigner gateways are correct
        if ($input['key'] === ConfigKey::MERCHANT_ENACH_CONFIGS)
        {
            if (in_array($input['value'], [Gateway::ESIGNER_DIGIO, Gateway::ESIGNER_LEGALDESK]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_INVALID_GATEWAY,
                    'gateway',
                    $input['value']
                );
            }
        }
    }

    protected static $setEsPricingKeyRules = [
        'on_demand' => 'sometimes|integer',
        'scheduled' => 'sometimes|integer',
    ];
}
