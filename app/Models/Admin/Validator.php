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

    protected static $sfPocDataRules = [
        'totalSize'      => 'required|integer',
        'done'           => 'required|boolean',
        'nextRecordsUrl' => 'sometimes|string',
        'records'        => 'required|array',
    ];

    protected static $sfPocRecordRules = [
        'attributes'                    => 'sometimes',
        'Merchant_ID__c'                => 'required|string|max:14',
        'Owner'                         => 'required',
        'Owner.Email'                   => 'required|email',
        'Owner_Role__c'                 => 'required|string',
        'Managers_In_Role_Hierarchy__c' => 'sometimes|string|custom|nullable',
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
        ConfigKey::FTS_TEST_MERCHANT                  => 'filled|string',
        ConfigKey::CURL_INFO_LOG_VERBOSE              => 'filled|boolean',
        ConfigKey::HITACHI_NEW_URL_ENABLED            => 'filled|boolean',
        ConfigKey::CARD_PAYMENT_SERVICE_ENABLED       => 'filled|boolean',
        ConfigKey::NB_PLUS_SERVICE_ENABLED            => 'filled|boolean',
        ConfigKey::PAYSECURE_BLACKLISTED_MCCS         => 'filled|array',
        ConfigKey::RX_SLA_FOR_IMPS_PAYOUT             => 'filled|integer',
        ConfigKey::FTS_PAYOUT_VPA                     => 'filled|string',
        ConfigKey::FTS_PAYOUT_CARD                    => 'filled|string',
        ConfigKey::FTS_PAYOUT_BANK_ACCOUNT            => 'filled|string',


        ConfigKey::WORLDLINE_TID_RANGE_LIST           => 'filled|array',
        ConfigKey::WORLDLINE_TID_RANGE_LIST.'.*'      => 'filled|array',

        ConfigKey::LOW_BALANCE_RX_EMAIL               => 'filled|array',
    ];

    protected static $setRedisKeysRules = [
        ConfigKey::HEARTBEAT_ROUTES                 => 'filled|array',
        ConfigKey::DOWNTIME_THROTTLE                => 'filled|array',
        ConfigKey::DOWNTIME_DETECTION_CONFIGURATION => 'filled|array',
        ConfigKey::FTS_BENEFICIARY                  => 'filled|array',
    ];

    protected static $setGatewayDowntimeRedisKeysRules = [
        'config:downtime:detection:configuration'             => 'required|array',
        'config:downtime:detection:configuration.*.key'       => 'required|string',
        'config:downtime:detection:configuration.*.value'     => 'required|array',
        'config:downtime:detection:configuration.*.value.*'   => 'required|array|size:4',
        'config:downtime:detection:configuration.*.value.*.*' => 'required|string',
    ];

    protected static $updateRedisKeysRules = [
        'key'   => 'required|in:config:heartbeat_routes',
        'value' => 'array',
    ];

    protected static $getRedisKeyRules = [
        'key'   => 'required|in:config:heartbeat_routes'
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
        'key'   => 'required'
    ];

    protected static $deleteConfigKeyRules = [
        'key'   => 'required|in:merchant_enach_configs',
        'path'  => 'required|string',
    ];

    protected static $updateConfigKeyValidators = [
        'update_config_value'
    ];

    protected static $emailRules = [
        'email' => 'required|email',
    ];

    /**
     * @param string $attribute
     * @param string $value
     */
    public function validateManagersInRoleHierarchyC(string $attribute, string $value)
    {
        $value = rtrim($value, ',');

        $emails = explode(',', $value);

        foreach ($emails as $email)
        {
            $this->validateInput('email', ['email' => $email]);
        }
    }

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

    protected static $bulkCreateEntityRules = [
        'type' => 'required|string',
        'data' => 'required|array|min:1',
    ];

    protected static $mozartGatewayPvtRules = [
        'gateway'            => 'required|string|in:citi,icici,yesbank_upi,yesbank,icici_imps,rbl',
        'action'             => 'required|string|in:gateway_auth,transfer_init,transfer_status,beneficiary_verify,beneficiary_register,registration,account_balance',
        'namespace'          => 'required|string',
        'payload'            => 'required|array',
        'payload.entities'   => 'required|array',
        'version'            => 'required|string|in:v1,v2',
    ];
}
