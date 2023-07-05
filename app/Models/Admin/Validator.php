<?php

namespace RZP\Models\Admin;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\AdminFetch;
use RZP\Models\Payment\Gateway;
use RZP\Models\Admin\Admin\Entity;

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
        'MRH_Date__c'                   => 'sometimes|string',
    ];

    protected static $syncEntityRules = [
        'from_mode'      => 'required|string',
        'to_mode'        => 'required|string',
        'fields_to_sync' => 'required',
    ];

    protected static $setConfigKeysRules = [
        ConfigKey::TENANT_ROLES_ENTITY                  => 'filled|array',
        ConfigKey::TENANT_ROLES_ROUTES                  => 'filled|array',
        ConfigKey::ASYNC_ESCALATION_HANDLING_ENABLED    => 'filled|boolean',
        ConfigKey::TERMINAL_SELECTION_LOG_VERBOSE       => 'filled|boolean',
        ConfigKey::PRICING_RULE_SELECTION_LOG_VERBOSE   => 'filled|boolean',
        ConfigKey::GATEWAY_PROCESSED_REFUNDS            => 'filled|array',
        ConfigKey::GATEWAY_UNPROCESSED_REFUNDS          => 'filled|array',
        ConfigKey::MASTER_PERCENT                       => 'filled|integer',
        ConfigKey::BLOCK_BANK_TRANSFERS_FOR_CRYPTO      => 'filled|boolean',
        ConfigKey::DISABLE_MAGIC                        => 'filled|boolean',
        ConfigKey::BLOCK_SMART_COLLECT                  => 'filled|boolean',
        ConfigKey::BLOCK_YESBANK                        => 'filled|boolean',
        ConfigKey::BLOCK_AADHAAR_REG                    => 'filled|boolean',
        ConfigKey::NPCI_UPI_DEMO                        => 'filled|array',
        ConfigKey::MERCHANT_ENACH_CONFIGS               => 'filled|array',
        ConfigKey::HITACHI_DYNAMIC_DESCR_ENABLED        => 'filled|boolean',
        ConfigKey::CPS_SERVICE_ENABLED                  => 'filled|boolean',
        ConfigKey::SETTLEMENT_TRANSACTION_LIMIT         => 'filled|integer',
        ConfigKey::FTS_ROUTE_PERCENTAGE                 => 'filled|integer',
        ConfigKey::ENABLE_PAYMENT_DOWNTIMES             => 'filled|boolean',
        ConfigKey::FTS_TEST_MERCHANT                    => 'filled|string',
        ConfigKey::CURL_INFO_LOG_VERBOSE                => 'filled|boolean',
        ConfigKey::HITACHI_NEW_URL_ENABLED              => 'filled|boolean',
        ConfigKey::CARD_PAYMENT_SERVICE_ENABLED         => 'filled|boolean',
        ConfigKey::PG_ROUTER_SERVICE_ENABLED            => 'filled|boolean',
        ConfigKey::CARD_ARCHIVAL_FALLBACK_ENABLED       => 'filled|boolean',
        ConfigKey::PAYMENT_ARCHIVAL_EAGER_LOAD          => 'filled|boolean',
        ConfigKey::PAYMENT_ARCHIVAL_FALLBACK_ENABLED    => 'filled|boolean',
        ConfigKey::PAYMENTS_DUAL_WRITE                  => 'filled|boolean',
        ConfigKey::DATA_WAREHOUSE_CONNECTION_FALLBACK   => 'filled|string',
        ConfigKey::PAYSECURE_BLACKLISTED_MCCS           => 'filled|array',
        ConfigKey::RX_SLA_FOR_IMPS_PAYOUT               => 'filled|integer',
        ConfigKey::FTS_PAYOUT_VPA                       => 'filled|string',
        ConfigKey::FTS_PAYOUT_CARD                      => 'filled|string',
        ConfigKey::FTS_PAYOUT_BANK_ACCOUNT              => 'filled|string',
        ConfigKey::CARD_PAYMENT_SERVICE_EMI_FETCH       => 'filled|boolean',
        ConfigKey::ENABLE_PAYMENT_DOWNTIME_CARD         => 'filled|boolean',
        ConfigKey::ENABLE_PAYMENT_DOWNTIME_CARD_ISSUER  => 'filled|boolean',
        ConfigKey::ENABLE_PAYMENT_DOWNTIME_CARD_NETWORK => 'filled|boolean',
        ConfigKey::ENABLE_PAYMENT_DOWNTIME_NETBANKING   => 'filled|boolean',
        ConfigKey::ENABLE_PAYMENT_DOWNTIME_UPI          => 'filled|boolean',
        ConfigKey::ENABLE_PAYMENT_DOWNTIME_WALLET       => 'filled|boolean',
        ConfigKey::ENABLE_DOWNTIME_SERVICE              => 'filled|boolean',
        ConfigKey::ENABLE_DOWNTIME_SERVICE_UPI          => 'filled|boolean',
        ConfigKey::ENABLE_DOWNTIME_SERVICE_NETBANKING   => 'filled|boolean',
        ConfigKey::ENABLE_DOWNTIME_SERVICE_EMANDATE   => 'filled|boolean',
        ConfigKey::ENABLE_DOWNTIME_SERVICE_CARD         => 'filled|boolean',
        ConfigKey::ENABLE_PAYMENT_DOWNTIME_PHONEPE      => 'filled|boolean',
        ConfigKey::USE_MUTEX_FOR_DOWNTIMES              => 'filled|boolean',
        ConfigKey::ENABLE_DOWNTIME_WEBHOOKS             => 'filled|boolean',

        ConfigKey::WORLDLINE_TID_RANGE_LIST           => 'filled|array',
        ConfigKey::WORLDLINE_TID_RANGE_LIST.'.*'      => 'filled|array',

        ConfigKey::LOW_BALANCE_RX_EMAIL               => 'filled|array',

        ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT             => 'filled|integer',
        ConfigKey::RX_BAS_FORCED_FETCH_TIME_IN_HOURS                => 'filled|integer',
        ConfigKey::RBL_BANKING_ACCOUNT_STATEMENT_CRON_ATTEMPT_DELAY => 'filled|integer',

        ConfigKey::PAYOUT_SERVICE_DATA_MIGRATION_LIMIT_PER_BATCH            => 'filled|integer',
        ConfigKey::PAYOUT_SERVICE_DATA_MIGRATION_BATCH_ATTEMPTS             => 'filled|integer',
        ConfigKey::PAYOUT_SERVICE_DATA_MIGRATION_BUFFER                     => 'filled|integer',
        ConfigKey::GATEWAY_BALANCE_LAST_FETCHED_AT_RATE_LIMITING            => 'filled|integer',
        ConfigKey::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT    => 'filled|integer',
        ConfigKey::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT  => 'filled|integer',
        ConfigKey::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE   => 'filled|boolean',
        ConfigKey::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE => 'filled|boolean',

        ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT  => 'filled|integer',
        ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE => 'filled|boolean',

        ConfigKey::RX_ICICI_2FA_WEBHOOK_PROCESS_TYPE              => 'filled|integer',

        ConfigKey::TRANSFER_SYNC_PROCESSING_VIA_API_SEMAPHORE_CONFIG          => 'filled|array',
        ConfigKey::TRANSFER_SYNC_PROCESSING_VIA_API_HOURLY_RATE_LIMIT_PER_MID => 'filled|integer',
        ConfigKey::TRANSFER_PROCESSING_MUTEX_CONFIG                           => 'filled|array',

        ConfigKey::RBL_STATEMENT_FETCH_ATTEMPT_LIMIT              => 'filled|integer',
        ConfigKey::RBL_STATEMENT_FETCH_SPECIAL_ATTEMPT_LIMIT      => 'filled|integer',
        ConfigKey::ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT            => 'filled|integer',
        ConfigKey::ICICI_STATEMENT_FETCH_ALLOW_DESCRIPTION        => 'filled|array',
        ConfigKey::ICICI_STATEMENT_FETCH_ENABLE_IN_OFF_HOURS      => 'filled|boolean',
        ConfigKey::RBL_STATEMENT_FETCH_RETRY_LIMIT                => 'filled|integer',
        ConfigKey::BLOCK_X_REGISTRATION                           => 'filled|boolean',
        ConfigKey::BLOCK_YESBANK_RX_FAV                           => 'filled|boolean',
        ConfigKey::REMOVE_SETTLEMENT_BA_COOL_OFF                  => 'filled|boolean',
        ConfigKey::BLOCK_YESBANK_WALLET_PAYOUTS                   => 'filled|boolean',
        ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX                => 'filled|array',
        ConfigKey::RX_SHARED_ACCOUNT_ALLOWED_CHANNELS             => 'filled|array',
        ConfigKey::ENABLE_NB_KOTAK_ENCRYPTED_FLOW                 => 'filled|boolean', // Not used currently
        ConfigKey::RBL_STATEMENT_FETCH_RATE_LIMIT                 => 'filled|integer',
        ConfigKey::RBL_STATEMENT_FETCH_WINDOW_LENGTH              => 'filled|integer',
        ConfigKey::ICICI_STATEMENT_FETCH_RATE_LIMIT               => 'filled|integer',
        ConfigKey::ICICI_STATEMENT_FETCH_WINDOW_LENGTH            => 'filled|integer',
        ConfigKey::ICICI_STATEMENT_FETCH_RETRY_LIMIT              => 'filled|integer',
        ConfigKey::RBL_STATEMENT_CLOSING_BALANCE_DIFF             => 'array',
        ConfigKey::RBL_STATEMENT_FETCH_RATE_LIMIT_RELEASE_DELAY   => 'filled|integer',
        ConfigKey::ICICI_STATEMENT_FETCH_RATE_LIMIT_RELEASE_DELAY => 'filled|integer',
        ConfigKey::RBL_ENABLE_RATE_LIMIT_FLOW                     => 'filled|integer',
        ConfigKey::ICICI_ENABLE_RATE_LIMIT_FLOW                   => 'filled|integer',
        ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS         => 'filled|integer',
        ConfigKey::RBL_CA_BALANCE_UPDATE_LIMITS                   => 'filled|array',


        ConfigKey::CREDIT_CARD_REGEX_FOR_REDACTING    => 'filled|string',
        ConfigKey::EMAIL_REGEX_FOR_REDACTING          => 'filled|string',
        ConfigKey::PHONE_NUMBER_REGEX_FOR_REDACTING   => 'filled|string',
        ConfigKey::CVV_REGEX_FOR_REDACTING            => 'filled|string',
        ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION       => 'array',
        ConfigKey::RX_QUEUED_PAYOUTS_CRON_LAST_RUN_AT => 'filled|integer',
        ConfigKey::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA => 'array',
        ConfigKey::RX_ON_HOLD_PAYOUTS_DEFAULT_SLA => 'filled|integer',
        ConfigKey::RX_BLACKLISTED_VPA_REGEXES_FOR_MERCHANT_PAYOUTS => 'array',
        ConfigKey::RX_CA_MISSING_STATEMENTS_RBL              => 'array',
        ConfigKey::RX_CA_MISSING_STATEMENTS_ICICI            => 'array',
        ConfigKey::RX_CA_MISSING_STATEMENTS_UPDATION_PARAMS  => 'array',
        ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS   => 'filled|integer',
        ConfigKey::ICICI_MISSING_STATEMENT_FETCH_MAX_RECORDS => 'filled|integer',
        ConfigKey::RETRY_COUNT_FOR_ID_GENERATION             => 'filled|integer',
        COnfigKey::RX_MISSING_STATEMENTS_INSERTION_LIMIT     => 'filled|integer',
        ConfigKey::RX_CA_MISSING_STATEMENT_DETECTION_RBL     => 'filled|array',
        ConfigKey::RX_CA_MISSING_STATEMENT_DETECTION_ICICI   => 'filled|array',
        ConfigKey::CA_RECON_PRIORITY_ACCOUNT_NUMBERS         => 'filled|array',
        ConfigKey::CARD_PAYMENTS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => 'filled|integer',
        ConfigKey::CARD_REFUNDS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => 'filled|integer',
        ConfigKey::CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => 'filled|integer',
        ConfigKey::UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => 'filled|integer',
        ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => 'array',
        ConfigKey::LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH => 'filled|integer',
        ConfigKey::BATCH_PAYOUTS_FETCH_LIMIT          => 'integer|nullable',
        ConfigKey::RX_PAYOUTS_CUSTOM_BATCH_FILE_LIMIT_MERCHANTS => 'array',
        ConfigKey::RX_PAYOUTS_DEFAULT_MAX_BATCH_FILE_COUNT       => 'integer|nullable',
        ConfigKey::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1       => 'filled|integer',
        ConfigKey::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB1   => 'filled|integer',
        ConfigKey::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI_SLAB1 => 'filled|integer',
        ConfigKey::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB2       => 'filled|integer',
        ConfigKey::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB2   => 'filled|integer',
        ConfigKey::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI_SLAB2 => 'filled|integer',

        ConfigKey::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_AXIS_SLAB1    => 'filled|integer',
        ConfigKey::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_YESBANK_SLAB1 => 'filled|integer',
        ConfigKey::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_AXIS_SLAB2    => 'filled|integer',
        ConfigKey::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_YESBANK_SLAB2 => 'filled|integer',

        ConfigKey::FREE_PAYOUTS_SUPPORTED_MODES           => 'filled|array',
        ConfigKey::DELAY_RUPAY_CAPTURE                    => 'filled|boolean',
        ConfigKey::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE   => 'array',
        ConfigKey::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP   => 'filled|integer',
        ConfigKey::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT     => 'filled|array',
        ConfigKey::ENABLE_CRED_ELIGIBILITY_CALL                                        => 'filled|boolean',
        ConfigKey::RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_MERCHANTS               => 'filled|array',
        ConfigKey::RX_WEBHOOK_URL_FOR_MFN                                              => 'filled|url',
        ConfigKey::RX_WEBHOOK_URL_FOR_MFN_TEST_MODE                                    => 'filled|url',
        ConfigKey::RX_LIMIT_STATEMENT_FIX_ENTITIES_UPDATE                              => 'filled|integer',
        ConfigKey::RX_POSTED_DATE_WINDOW_FOR_PREVIOUS_BAS_SEARCH                       => 'filled|integer',
        ConfigKey::RX_GLOBALLY_WHITELISTED_PAYER_ACCOUNTS_FOR_FUND_LOADING             => 'filled|array',
        ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE                           => 'filled|integer',
        ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_IN_TOTAL                          => 'filled|integer',
        ConfigKey::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE                      => 'filled|integer',
        ConfigKey::ACCOUNT_STATEMENT_V2_FLOW                                           => 'filled|array',
        ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_PROCESS_AT_ONCE                        => 'filled|integer',
        ConfigKey::REQUEST_LOG_STATE                                                   => 'filled|string',
        ConfigKey::BANKING_ACCOUNT_STATEMENT_PROCESS_DELAY                             => 'filled|integer',
        ConfigKey::MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION => 'filled|integer',
        ConfigKey::MAX_ACTIVATION_PROGRESS_FOR_POPUP_RANGE1                            => 'filled|integer',
        ConfigKey::REARCH_CARD_PAYMENTS                                                => 'filled|boolean',
        ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X                                       => 'filled|boolean',
        ConfigKey::PAYMENT_SHOW_DCC_MARKUP                                             => 'filled|boolean',
        ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE                    => 'filled|integer',
        ConfigKey::PAYER_ACCOUNT_NUMBER_INVALID_REGEXES                                => 'filled|array',
        ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE                    => 'filled|integer',
        ConfigKey::FUND_MANAGEMENT_PAYOUTS_RETRIEVAL_THRESHOLD                         => 'filled|integer',
        ConfigKey::RZP_INTERNAL_ACCOUNTS                                               => 'array',
        ConfigKey::RZP_INTERNAL_TEST_ACCOUNTS                                          => 'array',
        ConfigKey::SUB_BALANCES_MAP                                                    => 'filled|array',
        ConfigKey::BAS_CREDIT_BEFORE_DEBIT_UTRS                                        => 'array',
        ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS                            => 'array',
        ConfigKey::SHIFT_BULK_PAYOUT_APPROVE_TO_BULK_APPROVE_PAYOUT_SMS_TEMPLATE       => 'array',
        ConfigKey::USE_MASTER_DB_CONNECTION                                            => 'filled|boolean',
        ConfigKey::PAYER_ACCOUNT_NAME_INVALID_REGEXES                                  => 'filled|array',
        ConfigKey::SCROOGE_0LOC_ENABLED                                                => 'filled|boolean',
        ConfigKey::ONDEMAND_SETTLEMENT_INTERNAL_MERCHANTS                              => 'array',
        ConfigKey::MCC_DEFAULT_MARKDOWN_PERCENTAGE                                     => 'filled|numeric',
        ConfigKey::MCC_DEFAULT_MARKDOWN_PERCENTAGE_CONFIG                              => 'array',
        ConfigKey::COMMISSION_FEE_FOR_CC_MERCHANT_PAYOUT                               => 'filled|integer',
        ConfigKey::SET_CARD_METADATA_NULL                                              => 'filled|boolean',
        ConfigKey::DEFAULT_OPGSP_TRANSACTION_LIMIT_USD                                 => 'filled|integer',
        ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA                              => 'filled|boolean',
        ConfigKey::RX_OD_BALANCE_CONFIGURED_FOR_MAGICBRICKS                            => 'filled|integer',
        ConfigKey::RISK_FOH_TEAM_EMAIL_IDS                                             => 'filled|array',
        ConfigKey::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_RATE_LIMIT                        => 'filled|integer',
        ConfigKey::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_WINDOW_LENGTH                     => 'filled|integer',
        ConfigKey::PAYOUT_ASYNC_APPROVE_PROCESSING_RATE_LIMIT                          => 'filled|integer',
        ConfigKey::PAYOUT_ASYNC_APPROVE_PROCESSING_WINDOW_LENGTH                       => 'filled|integer',
        ConfigKey::DCS_READ_WHITELISTED_FEATURES                                       => 'filled|array',
        ConfigKey::UNEXPECTED_PAYMENT_DELAY_REFUND                                     => 'filled|integer',
        ConfigKey::DIRECT_TRANSFER_LIMITS                                              => 'filled|array',
        ConfigKey::ACCOUNT_SUB_ACCOUNT_RESTRICTED_PERMISSIONS_LIST                     => 'filled|array',
        ConfigKey::DEFAULT_PRICING_FOR_ACH                                             => 'filled|array',
        ConfigKey::DEFAULT_PRICING_FOR_SWIFT                                           => 'filled|array',
    ];

    protected static $setRedisKeysRules = [
        ConfigKey::DOWNTIME_THROTTLE                => 'filled|array',
        ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2 => 'filled|array',
        ConfigKey::FTS_BENEFICIARY                  => 'filled|array',
    ];

    //Verify if $setGatewayDowntimeRedisKeysRules is getting used and clean it up.
    protected static $setGatewayDowntimeRedisKeysRules = [
        'config:{downtime}:detection:configuration_v2'             => 'required|array',
        'config:{downtime}:detection:configuration_v2.*.key'       => 'required|string',
        'config:{downtime}:detection:configuration_v2.*.value'     => 'required|array',
        'config:{downtime}:detection:configuration_v2.*.value.*.*' => 'required|string',
    ];

    protected static $scorecardRules = [
        'count'             => 'required|integer|max:100'
    ];

    protected static $bankingScorecardRules = [
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
        'key'   => 'required|in:merchant_enach_configs,payment_show_dcc_markup',
        'path'  => 'required|string',
    ];

    protected static $updateConfigKeyValidators = [
        'update_config_value'
    ];

    protected static $emailRules = [
        'email' => 'required|email',
    ];

    protected static $batchAdminUpdateRules = [
        Entity::ID                  => 'required|string|max:20',
        Entity::ALLOW_ALL_MERCHANTS => 'sometimes|in:0,1',
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

    protected static $bulkCreateEntityRules = [
        'type' => 'required|string',
        'data' => 'required|array|min:1',
    ];

    protected static $mozartGatewayPvtRules = [
        'gateway'            => 'required|string|in:citi,icici,yesbank_upi,yesbank,icici_imps,rbl,m2p,axis,amazonpay,mc_send',
        'action'             => 'required|string|in:gateway_auth,gateway_session,transfer_init,transfer_status,beneficiary_verify,beneficiary_register,registration,account_balance,account_statement,account_statement_consolidated',
        'namespace'          => 'required|string',
        'payload'            => 'required|array',
        'payload.entities'   => 'required|array',
        'version'            => 'required|string|in:v1,v2,v3,v4',
    ];

    protected static $externalAdminFetchMultiplePaymentRules = [
        'count'           => 'required|integer|max:5',
        'order_id'        => 'sometimes',
        'subscription_id' => 'sometimes',
        'wallet'          => 'sometimes',
        'status'          => 'sometimes',
        'refund_status'   => 'sometimes',
        'notes'           => 'sometimes',
        'method'          => 'sometimes',
        'gateway'         => 'sometimes',
        'amount'          => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleUpiRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'gateway_payment_id'    => 'sometimes',
        'npci_reference_id'     => 'sometimes',
        'refund_id'             => 'sometimes',
        'merchant_reference'    => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleAtomRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'bank_payment_id'       => 'sometimes',
        'gateway_payment_id'    => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleRefundRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'payment_id'            => 'sometimes',
        'transaction_id'        => 'sometimes',
        'amount'                => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleDisputeRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'payment_id'            => 'sometimes',
        'amount'                => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleMerchantRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleBilldeskRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'TxnReferenceNo'        => 'sometimes',
        'status'                => 'sometimes',
        'BankReferenceNo'       => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleNetbankingRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'bank_payment_id'       => 'sometimes',
        'caps_payment_id'       => 'sometimes',
        'int_payment_id'        => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleBankTransferRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'utr'                   => 'sometimes',
        'payment_id'            => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleMerchantDetailRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleBalanceRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'merchant_id'           => 'sometimes',
    ];

    protected static $externalAdminFetchMultipleCreditsRules = [
        'count'                 => 'required|integer|max:5',
        'id'                    => 'sometimes',
        'merchant_id'           => 'sometimes',
        'type'                  => 'sometimes',
    ];


    public function validateEntityTypeForExternalAdmin(string $entity)
    {
        if (in_array($entity, array_keys(AdminFetch::$externalAdminEntityAllowedAttributesMap)) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }
    }
}
