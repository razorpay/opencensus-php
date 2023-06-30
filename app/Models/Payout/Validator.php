<?php

namespace RZP\Models\Payout;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Card;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\FundAccount;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\FundTransfer;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Mode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\Workflow\Service\Adapter;
use RZP\Models\PartnerBankHealth\Events;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Models\Settlement\SlackNotification;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\FundTransfer\Attempt\Constants;
use RZP\Models\Counter\Entity as CounterEntity;
use RZP\Models\Feature\Repository as FeatureRepo;
use RZP\Models\PayoutSource\Entity as PayoutSource;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;
use RZP\Models\PayoutsDetails\Entity as PayoutDetailsEntity;
use RZP\Models\Workflow\Action\Checker\Entity as ActionChecker;
use RZP\Models\PayoutsDetails\Validator as PayoutDetailsValidator;
use RZP\Models\Payout\Configurations\DirectAccounts\PayoutModeConfig;

class Validator extends Base\Validator
{

    // We are increasing this from 100 to 200. Slack thread for reference:
    // https://razorpay.slack.com/archives/C013868TRK4/p1615796447155300?thread_ts=1615544530.147100&cid=C013868TRK4
    // TODO: Finalize on some final number that we wish to support in the long run
    const MAX_PURPOSES_ALLOWED = 200;

    const MAX_PURPOSES_ALLOWED_TO_XPAYROLL = 100;

    /**
     * Rate limit on items sending for bulk payout create.
     */
    const MAX_BULK_PAYOUTS_LIMIT = 15;

    const CALCULATE_ES_ON_DEMAND_FEES = 'calculate_es_on_demand_fees';

    const FUND_ACCOUNT_PAYOUT_COMPOSITE = 'fund_account_payout_composite';

    const BEFORE_CREATE_FUND_ACCOUNT_PAYOUT = 'before_create_fund_account_payout';

    const BEFORE_CREATE_FUND_ACCOUNT_PAYOUT_WITH_OTP = 'before_create_fund_account_payout_with_otp';

    const BULK_UPDATE_ATTACHMENTS = 'bulk_update_attachments';

    const UPDATE_REQUEST = 'update_request';

    const UPDATE_TAX_PAYMENT = 'update_tax_payment';

    const DOWNLOAD_ATTACHMENTS = 'download_attachments';

    // The max payout amount allowed for merchant payouts is 80 L
    const MAX_LIMIT_MERCHANT_PAYOUT_AMOUNT = 800000000;

    // The max payout amount allowed for merchant payouts on demand is 2 Cr
    const MAX_LIMIT_MERCHANT_ON_DEMAND_PAYOUT_AMOUNT = 2000000000;

    const MIN_TRANSACTION_AMOUNT_ALLOWED_IN_PAISE = 100;

    const APPROVE_PAYOUT_RULES = 'approve_payout';

    const APPROVE_ICICI_CA_PAYOUT_RULES = 'approve_icici_ca_payout';

    const CANCEL_PAYOUT = 'cancel_payout';

    const PROCESS_QUEUED_PAYOUTS_INITIATE = 'process_queued_payouts_initiate';

    // Scheduled Payouts Initiate
    const PROCESS_SCHEDULED_PAYOUTS = 'process_scheduled_payouts';

    const PAYOUT_STATUS_MANUAL = 'payout_status_manual';

    const ACCEPTED_ORIGIN_VALUES = 'accepted_origin_values';

    const VALIDATE_PAYOUT_PURPOSE = 'validate_payout_purpose';

    const PAYOUT_BULK_SAMPLE_FILE = 'payout_bulk_sample_file';

    const PAYOUT_BULK_TEMPLATE_FILE = 'payout_bulk_template_file';

    const PAYOUT_BULK_STATUS_UPDATE_MANUAL = 'payout_bulk_status_update_manual';

    const PAYOUT_SERVICE_DATA_MIGRATION_INPUT = 'payout_service_data_migration_input';
    const PAYOUT_SERVICE_MAIL_AND_SMS_INPUT   = 'payout_service_mail_and_sms_input';
    const PAYOUT_SERVICE_TXN_MAIL_DATA        = 'payout_service_txn_mail_data';

    const PAYOUT_SERVICE_DUAL_WRITE_INPUT = 'payout_service_dual_write_input';

    // Payout Service Validations
    const PAYOUT_SERVICE_CREATE                     = 'payout_service_create';
    const PAYOUT_SERVICE_TRANSACTION_CREATE         = 'payout_service_transaction_create';
    const DEDUCT_CREDITS_VIA_PAYOUT_SERVICE         = 'deduct_credits_via_payout_service';
    const PAYOUT_SERVICE_FTS_CREATE                 = 'payout_service_fts_create';
    const RETRY_PAYOUTS_ON_SERVICE                  = 'retry_payouts_on_service';
    const PAYOUT_SERVICE_FETCH_PRICING_INFO         = 'payout_service_fetch_pricing_info';
    const DECREMENT_FREE_PAYOUT_FOR_PAYOUTS_SERVICE = 'decrement_free_payout_for_payouts_service';
    const MIGRATE_FREE_PAYOUT_PAYOUTS_SERVICE       = 'migrate_free_payout_payouts_service';
    const ROLLBACK_FREE_PAYOUT_PAYOUTS_SERVICE      = 'rollback_free_payout_payouts_service';
    const PAYOUTS_SOURCE_UPDATE                     = 'payouts_source_update';
    const STATUS_DETAILS_SOURCE_UPDATE              = 'status_details_source_update';
    const DELETE_CARD_META_DATA_FOR_PAYOUT_SERVICE  = 'delete_card_meta_data_payout_service';

    const PAYOUTS_SERVICE_CREATE_FAILURE_PROCESSING_CRON    = 'payouts_service_create_failure_processing_cron';
    const PAYOUTS_SERVICE_UPDATE_FAILURE_PROCESSING_CRON    = 'payouts_service_update_failure_processing_cron';

    const PAYOUT_2FA_OTP_SEND_REQUEST  =  'payout_2fa_otp_send_request';

    const TDS_CATEGORY_ID_CACHE_KEY = 'tds_category_id_list';
    const TDS_CATEGORY_ID_CACHE_TTL = 12 * 60 * 60;

    const MAX_COUNT_DATA_CONSISTENCY_CHECKER_PAYOUT_IDS = 1000;
    const DATA_CONSISTENCY_CHECKER_PAYOUTS_DETAIL_FETCH = 'data_consistency_checker_payouts_detail_fetch';

    const WFS_CONFIG_FETCH = 'wfs_config_fetch';

    const OWNER_BULK_REJECT_PAYOUTS = 'owner_bulk_reject_payouts';

    const FETCH_PENDING_PAYOUTS_SUMMARY = 'fetch_pending_payouts_summary';

    const PARTNER_BANK_HEALTH_NOTIFICATION = 'partner_bank_health_notification';

    const PARTNER_PAYOUT_APPROVAL_RULES = 'partner_payout_approval';

    const UPDATE_BALANCE_MANAGEMENT_CONFIG = 'update_balance_management_config';

    //
    // This is required for build. Currently, build does not
    // accept ruleName as a parameter. Hence, this list needs
    // to contain the master attributes. We run a different
    // validation for the actual operation.
    //
    protected static $createRules = [
        Entity::PURPOSE              => 'sometimes|string',
        Entity::AMOUNT               => 'sometimes|integer',
        Entity::CURRENCY             => 'sometimes|size:3',
        Entity::NOTES                => 'sometimes|notes',
        Entity::CUSTOMER_ID          => 'sometimes|public_id',
        Entity::DESTINATION          => 'sometimes|public_id',
        Entity::TYPE                 => 'sometimes|string',
        Entity::BALANCE_ID           => 'sometimes|string|size:14',
        Entity::FUND_ACCOUNT_ID      => 'sometimes|public_id',
        Entity::MODE                 => 'sometimes|nullable|string',
        Entity::REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Entity::NARRATION            => 'sometimes|nullable|string|max:30',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
        Entity::IDEMPOTENCY_KEY      => 'sometimes|nullable|string',
        Entity::SCHEDULED_AT         => 'sometimes|filled|epoch',
        Entity::ORIGIN               => 'sometimes|filled',
        PayoutDetailsEntity::TDS              => 'sometimes|filled|array',
        PayoutDetailsEntity::ATTACHMENTS      => 'sometimes|filled|array',
        PayoutDetailsEntity::SUBTOTAL_AMOUNT  => 'sometimes|integer'
    ];

    /**
     * @see Payout\Batch\Validator Need to change for payout rules if any changes are done here
     *
     * @var array
     */
    protected static $fundAccountPayoutCompositeRules = [
        Entity::PURPOSE                              => 'required|filled|string|max:30|alpha_dash_space',
        Entity::AMOUNT                               => 'required|integer|min:100|custom',
        Entity::CURRENCY                             => 'required|size:3|in:INR',
        Entity::NOTES                                => 'sometimes|notes',
        Entity::BALANCE_ID                           => 'sometimes|filled|size:14',
        Entity::MODE                                 => 'required|string|custom',
        Entity::REFERENCE_ID                         => 'sometimes|nullable|string|max:40',
        Entity::NARRATION                            => 'sometimes|nullable|string|max:30|regex:/^[a-zA-Z0-9 ]*$/',
        Entity::PAYOUT_LINK_ID                       => 'sometimes|filled|public_id',
        Entity::QUEUE_IF_LOW_BALANCE                 => 'sometimes|filled|boolean',
        Entity::SKIP_WORKFLOW                        => 'filled|boolean',
        Entity::FUND_ACCOUNT                         => 'required|filled|array|custom',
        Entity::FUND_ACCOUNT . "." . Entity::CONTACT => 'required|filled|array',
        Entity::ORIGIN                                             => 'sometimes|filled',
        Entity::SOURCE_DETAILS                                     => 'sometimes|filled|array',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::SOURCE_ID   => 'required|string',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::SOURCE_TYPE => 'required|string|',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::PRIORITY    => 'required|integer|min:1',
        PayoutDetailsEntity::TDS                                   => 'sometimes|filled|array',
        PayoutDetailsEntity::ATTACHMENTS                           => 'sometimes|filled|array',
        PayoutDetailsEntity::SUBTOTAL_AMOUNT                       => 'sometimes|integer',
        Entity::PG_MERCHANT_ID                                     => 'sometimes|unsigned_id',
    ];

    protected static $payoutServiceDataMigrationInputRules = [
        Entity::BALANCE_ID  => 'required|string|size:14',
        'from'              => 'required|epoch',
        'to'                => 'required|epoch',
    ];

    protected static $payoutServiceMailAndSmsInputRules = [
        Entity::ENTITY => 'required|in:payout,transaction',
        Entity::TYPE   => 'required',
        'entity_id'    => 'required|size:14',
        'metadata'     => 'array',
    ];

    protected static $payoutServiceTxnMailDataRules = [
        Entity::PAYOUT_ID  => 'required|size:14',
        Entity::AMOUNT     => 'required',
        Entity::CREATED_AT => 'required|epoch',
    ];

    protected static $payoutServiceDualWriteInputRules = [
        Entity::PAYOUT_ID => 'required|string|size:14',
        'timestamp'       => 'required|epoch',
    ];

    /**
     * @see Batch\Validator Need to change for payout rules if any changes are done here
     *
     * @see Payout\Batch\Validator A change needed here as well
     *
     * @var array
     */
    protected static $fundAccountPayoutRules = [
        Entity::PURPOSE                         => 'required|filled|string|max:30|alpha_dash_space',
        Entity::AMOUNT                          => 'required|integer|custom',
        Entity::CURRENCY                        => 'required|size:3|in:INR',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::BALANCE_ID                      => 'sometimes|filled|size:14',
        Entity::FUND_ACCOUNT_ID                 => 'required|public_id',
        Entity::MODE                            => 'required|string|custom',
        Entity::REFERENCE_ID                    => 'sometimes|nullable|string|max:40',
        Entity::NARRATION                       => 'sometimes|nullable|string|max:30|regex:/^[a-zA-Z0-9 ]*$/',
        Entity::IDEMPOTENCY_KEY                 => 'sometimes|nullable|string',
        Entity::PAYOUT_LINK_ID                  => 'sometimes|filled|public_id',
        Entity::QUEUE_IF_LOW_BALANCE            => 'sometimes|filled|boolean',
        Entity::SCHEDULED_AT                    => 'sometimes|filled|epoch|custom',
        Entity::ORIGIN                          => 'sometimes|filled',
        PayoutDetailsEntity::TDS                => 'sometimes|filled|array',
        PayoutDetailsEntity::ATTACHMENTS        => 'sometimes|filled|array',
        PayoutDetailsEntity::SUBTOTAL_AMOUNT    => 'sometimes|integer'
    ];

    protected static $customerWalletPayoutRules = [
        Entity::PURPOSE         => 'sometimes|filled|string|max:30|in:refund',
        Entity::AMOUNT          => 'required|integer|min:100|max:500000000',
        Entity::CURRENCY        => 'required|size:3|in:INR',
        Entity::NOTES           => 'sometimes|notes',
        Entity::BALANCE_ID      => 'sometimes|filled|size:14',
        Entity::FUND_ACCOUNT_ID => 'required|public_id',
        Entity::REFERENCE_ID    => 'sometimes|nullable|string|max:40',
        Entity::NARRATION       => 'sometimes|nullable|string|max:30|regex:/^[a-zA-Z0-9 ]*$/',
    ];

    protected static $beforeCreateFundAccountPayoutRules = [
        Entity::FUND_ACCOUNT_ID                                    => 'required_without:fund_account|public_id',
        Entity::FUND_ACCOUNT                                       => 'required_without:fund_account_id|array',
        Entity::ORIGIN                                             => 'sometimes|filled',
        Entity::SOURCE_DETAILS                                     => 'sometimes|filled|array',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::SOURCE_ID   => 'required|string',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::SOURCE_TYPE => 'required|string|',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::PRIORITY    => 'required|integer|min:1',
        Entity::ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT               => 'sometimes|boolean',
        PayoutDetailsEntity::TDS                                   => 'sometimes|filled|array',
        PayoutDetailsEntity::ATTACHMENTS                           => 'sometimes|filled|array',
        PayoutDetailsEntity::SUBTOTAL_AMOUNT                       => 'sometimes|integer'
    ];

    protected static $payoutServiceCreateRules = [
        Entity::ID                   => 'required|string|size:14',
        Entity::MERCHANT_ID          => 'required|string|size:14'
    ];

    protected static $payoutsSourceUpdateRules = [
        ENTITY::PAYOUT_ID               => 'required|string|size:14',
        ENTITY::SOURCE_DETAILS          => 'required|array',
        ENTITY::PREVIOUS_STATUS         => 'required|string',
        ENTITY::EXPECTED_CURRENT_STATUS => 'required|string'
    ];

    protected static $statusDetailsSourceUpdateRules = [
        ENTITY::PAYOUT_ID            => 'required|string|size:14',
        ENTITY::STATUS_DETAILS       => 'required|array',
        ENTITY::SOURCE_DETAILS       => 'required|array'
    ];

    protected static $decrementFreePayoutForPayoutsServiceRules = [
        Entity::MERCHANT_ID     => 'required|string|size:14',
        Entity::BALANCE_ID      => 'required|string|size:14',
        Entity::PAYOUT_ID       => 'required|string|size:14',
    ];


    protected static $beforeCreateFundAccountPayoutWithOtpRules = [
        Entity::ORIGIN                       => 'sometimes|filled|in:' . Entity::DASHBOARD,
        PayoutDetailsEntity::TDS             => 'sometimes|array',
        PayoutDetailsEntity::ATTACHMENTS_KEY => 'sometimes|array',
        PayoutDetailsEntity::SUBTOTAL_AMOUNT => 'sometimes|integer'
    ];

    protected static $bulkUpdateAttachmentsRules = [
        Entity::PAYOUT_IDS                  => 'required|array',
        PayoutDetailsEntity::UPDATE_REQUEST => 'required|array',
    ];

    protected static $updateRequestRules = [
        PayoutDetailsEntity::ATTACHMENTS_KEY => 'present|array',
    ];

    protected static $updateTaxPaymentRules = [
        PayoutDetailsEntity::TAX_PAYMENT_ID => 'required|string|size:19',
    ];

    protected static $beforeCreateFundAccountPayoutValidators = [
        'origin',
        'source_details',
        'tds_details',
        'attachments',
    ];

    protected static $fundAccountPayoutCompositeValidators = [
        'origin',
        'source_details',
        'amount',
        'amount_as_integer',
        'tds_details',
        'attachments',
    ];

    protected static $customerWalletPayoutValidators = [
        'amount_as_integer',
    ];

    protected static $fundAccountPayoutValidators = [
        'amount',
        'amount_as_integer',
        'tds_details',
        'attachments',
    ];

    protected static $beforeCreateFundAccountPayoutWithOtpValidators = [
        'source_details',
        'tds_details',
        'attachments',
    ];

    protected static $deductCreditsViaPayoutServiceValidators = [
        'status_for_credits_deduction_via_payout_service',
    ];

    // Both regular(type:default) and on demand(type:on_demand) payouts are validated through merchantPayoutRules.
    protected static $merchantPayoutRules = [
        Entity::PURPOSE    => 'required|string|max:30|in:payout',
        Entity::METHOD     => 'sometimes|string',
        Entity::AMOUNT     => 'required|integer',
        Entity::CURRENCY   => 'required|size:3',
        Entity::TYPE       => 'required|string|max:30|in:default,on_demand',
        Entity::BALANCE_ID => 'sometimes|filled|size:14',
    ];

    // On calling merchant/payout merchantRules gets used for validation of input.
    protected static $merchantRules = [
        Entity::MERCHANT_ID   => 'required|string|size:14',
        Entity::AMOUNT        => 'sometimes|integer|max:' . self::MAX_LIMIT_MERCHANT_PAYOUT_AMOUNT,
        Entity::MIN_AMOUNT    => 'sometimes|integer|min:100',
        Entity::MODULO        => 'sometimes|integer|min:100',
        Entity::BUFFER_AMOUNT => 'sometimes|integer|min:10000000'
    ];

    // On calling merchant/payout/demand merchantPayoutOnDemandRules gets used for validation of input.
    protected static $merchantPayoutOnDemandRules = [
        Entity::AMOUNT   => 'required|integer|min:100|max:' . self::MAX_LIMIT_MERCHANT_ON_DEMAND_PAYOUT_AMOUNT,
        Entity::CURRENCY => 'required|size:3',
    ];

    protected static $createPurposeRules = [
        Entity::PURPOSE      => 'required|filled|string|max:30|alpha_dash_space',
        Entity::PURPOSE_TYPE => 'required|filled|string|in:refund,settlement',
    ];

    protected static $calculateEsOnDemandFeesRules = [
        Entity::AMOUNT   => 'required|integer|min:100',
        Entity::CURRENCY => 'required|size:3|in:INR,',
    ];

    protected static $approvePayoutRules = [
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    protected static $approveIciciCaPayoutRules = [
        User\Entity::OTP             => 'required|filled|string|min:6|max:6',
        Entity::PAYOUT_ID            => 'required|filled|string',
        ActionChecker::USER_COMMENT  => 'sometimes|nullable|string|max:255',
    ];

    protected static $cancelPayoutRules = [
        Entity::REMARKS => 'sometimes|filled|string|max:255',
    ];

    protected static $bulkApproveRules = [
        Entity::PAYOUT_IDS           => 'required|array',
        Entity::PAYOUT_IDS . '.*'    => 'required|public_id|size:19',
        User\Entity::OTP             => 'required|filled|min:4',
        User\Entity::TOKEN           => 'required|unsigned_id',
        ActionChecker::USER_COMMENT  => 'sometimes|nullable|string|max:255',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    protected static $pendingPayoutApprovalReminderRules = [
        PayoutConstants::INCLUDE_MERCHANT_IDS           => 'sometimes|array',
        PayoutConstants::INCLUDE_MERCHANT_IDS . '.*'    => 'sometimes|string|size:14',
        PayoutConstants::EXCLUDE_MERCHANT_IDS           => 'sometimes|array',
        PayoutConstants::EXCLUDE_MERCHANT_IDS . '.*'    => 'sometimes|string|size:14',
    ];

    protected static $batchApproveRules = [
        Entity::PAYOUT_IDS           => 'required|array',
        Entity::PAYOUT_IDS . '.*'    => 'required|public_id|size:19',
    ];

    protected static $batchRejectRules = [
        Entity::PAYOUT_IDS          => 'required|array',
        Entity::PAYOUT_IDS . '.*'   => 'required|public_id|size:19',
    ];

    protected static $retryPayoutsOnServiceRules = [
        Entity::PAYOUT_IDS        => 'required|array',
        Entity::PAYOUT_IDS . '.*' => 'required|string|size:14',
    ];

    protected static $bulkRetryWorkflowRules = [
        Entity::PAYOUT_IDS          => 'required|array',
        Entity::PAYOUT_IDS . '.*'   => 'required|public_id|size:19',
    ];

    protected static $payoutsServiceCreateFailureProcessingCronRules = [
        Entity::COUNT          => 'required|int',
        Entity::DAYS           => 'required|int',
    ];

    protected static $payoutsServiceUpdateFailureProcessingCronRules = [
        Entity::COUNT          => 'required|int',
        Entity::DAYS           => 'required|int',
    ];

    protected static $bulkRejectRules = [
        Entity::PAYOUT_IDS          => 'required|array',
        Entity::PAYOUT_IDS . '.*'   => 'required|public_id|size:19',
        Entity::FORCE_REJECT        => 'filled|boolean',
        ActionChecker::USER_COMMENT => 'sometimes|nullable|string|max:255',
    ];

    protected static $ownerBulkRejectPayoutsRules = [
        Entity::PAYOUT_IDS              => 'required|array',
        Entity::PAYOUT_IDS . '.*'       => 'required|public_id|size:19',
        Entity::BULK_REJECT_AS_OWNER    => 'required|boolean',
        ActionChecker::USER_COMMENT     => 'sometimes|nullable|string|max:255',
    ];

    protected static $fetchPendingPayoutsSummaryRules = [
        PayoutConstants::ACCOUNT_NUMBERS          => 'required|array',
    ];

    protected static $processQueuedPayoutsInitiateRules = [
        Entity::BALANCE_IDS     => 'sometimes|array',
        Entity::BALANCE_IDS_NOT => 'sometimes|array',
    ];

    // Both regular and on demand payouts are validated through the merchantPayoutValidators.
    protected static $merchantPayoutValidators = [
        'type_and_amount',
        'amount_as_integer'
    ];

    protected static $processScheduledPayoutsRules = [
        Entity::BALANCE_IDS     => 'sometimes|array',
        Entity::BALANCE_IDS_NOT => 'sometimes|array',
    ];

    protected static $payoutStatusManualRules = [
        Entity::STATUS                  => 'required|string',
        Entity::FAILURE_REASON          => 'sometimes|string',
        Constants::FTS_ACCOUNT_TYPE     => 'sometimes|string|custom',
        Constants::FTS_FUND_ACCOUNT_ID  => 'sometimes|string'
    ];

    protected static $payoutStatusManualValidators = [
        'final_status',
    ];

    protected static $skipWorkflowRules = [
        Entity::SKIP_WORKFLOW   => 'filled|boolean'
    ];

    protected static $skipWorkflowValidators = [
        'skip_workflow'
    ];

    protected static $validatePayoutPurposeRules = [
        Entity::PURPOSE         => 'required|string',
    ];

    protected static $payoutBulkSampleFileRules = [
        Entity::FILE_TYPE       => 'required|string|in:sample_file,template_file',
        Entity::FILE_EXTENSION  => 'required|string|in:csv,xlsx',
    ];

    protected static $payoutBulkTemplateFileRules = [
        Entity::FILE_EXTENSION   => 'required|string|in:csv,xlsx',
        Entity::PAYOUT_METHOD    => 'required|string|in:'.Entity::BANK_TRANSFER.','.Entity::AMAZONPAY.','.Entity::UPI,
        Entity::BENEFICIARY_INFO => 'required|string|in:'.Entity::BENEFICIARY_ID.','.Entity::BENEFICIARY_DETAILS,
    ];

    protected static $payoutServiceFtsCreateRules = [
        Entity::ID => 'required|string|size:14',
    ];

    protected static $payoutServiceTransactionCreateRules = [
        Entity::ID                   => 'required|string|size:14',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    protected static $deductCreditsViaPayoutServiceRules = [
        Entity::PAYOUT_ID                   => 'required|alpha_num|size:14',
        Entity::FEES                        => 'required|int',
        Entity::TAX                         => 'required|int',
        Entity::STATUS                      => 'required|string',
        Entity::MERCHANT_ID                 => 'required|alpha_num|size:14',
        Entity::BALANCE_ID                  => 'required|alpha_num|size:14',
    ];

    protected static $deleteCardMetaDataPayoutServiceRules = [
        Entity::CARD_ID                     => 'required|string|size:19',
        Entity::VAULT_TOKEN                 => 'required|string',
    ];

    protected static $payoutServiceFetchPricingInfoRules = [
        Entity::PAYOUT_ID            => 'required|alpha_num|size:14',
        Entity::MERCHANT_ID          => 'required|alpha_num|size:14',
        Entity::BALANCE_ID           => 'required|alpha_num|size:14',
        Entity::AMOUNT               => 'required|integer|min:100',
        Entity::METHOD               => 'required|string',
        Entity::MODE                 => 'required|string',
        Entity::CHANNEL              => 'required|string',
        Entity::PURPOSE              => 'sometimes|nullable|string',
        Entity::USER_ID              => 'sometimes|nullable|string',
        Entity::FEE_TYPE             => 'sometimes|nullable|string'
    ];

    protected static $migrateFreePayoutPayoutsServiceRules = [
        EntityConstants::ACTION             => 'required|in:enable,disable',
        'ids'                               => 'required|array',
        'ids' . '.*.' . Entity::MERCHANT_ID => 'required|alpha_num|size:14',
        'ids' . '.*.' . Entity::BALANCE_ID  => 'required|alpha_num|size:14',
    ];

    protected static $rollbackFreePayoutPayoutsServiceRules = [
        Entity::MERCHANT_ID                                => 'required|alpha_num|size:14',
        Entity::BALANCE_ID                                 => 'required|alpha_num|size:14',
        EntityConstants::BALANCE_TYPE                      => 'required|string',
        CounterEntity::FREE_PAYOUTS_CONSUMED               => 'required|integer',
        CounterEntity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => 'required|epoch',
        Balance\FreePayout::FREE_PAYOUTS_COUNT             => 'sometimes|integer',
        Balance\FreePayout::FREE_PAYOUTS_SUPPORTED_MODES   => 'sometimes|array',
    ];

    protected static $payoutBulkStatusUpdateManualRules = [
        Entity::PAYOUT_IDS              => 'required|array',
        Entity::PAYOUT_IDS . '.*'       => 'required|string|size:14',
        Entity::STATUS                  => 'required|string',
        Entity::FAILURE_REASON          => 'sometimes|string',
        Constants::FTS_FUND_ACCOUNT_ID  => 'sometimes|string',
        Constants::FTS_ACCOUNT_TYPE     => 'sometimes|string|custom',
    ];

    protected static $payoutBulkStatusUpdateManualValidators = [
        'final_status',
    ];

    protected static $dataConsistencyCheckerPayoutsDetailFetchRules = [
        Entity::PAYOUT_IDS        => 'required|array|max:' . self::MAX_COUNT_DATA_CONSISTENCY_CHECKER_PAYOUT_IDS,
        Entity::PAYOUT_IDS . '.*' => 'required|string|size:14',
    ];

    protected static $wfsConfigFetchRules = [
        Entity::CONFIG_TYPE     => 'required|string|in:'.(Adapter\Constants::PAYOUT_APPROVAL_TYPE).','.(Adapter\Constants::ICICI_PAYOUT_APPROVAL_TYPE)
    ];

    protected static $payout2faOtpSendRequestRules = [
        Payout\Entity::PAYOUT_ID  => 'required|filled|string'
    ];

    protected static $partnerBankHealthNotificationRules = [
        'account_type'  => 'required|filled|string',
        'status'        => 'required|filled|string',
        'channel'       => 'required|filled|string',
        'mode'          => 'required|filled|string'
    ];

    protected static $partnerPayoutApprovalRules = [
        'remarks'       => 'required|filled|string'
    ];

    protected static $updateBalanceManagementConfigRules = [
        'channel'                     => 'required|filled|string',
        'neft_threshold'              => 'required|integer|min:100',
        'lite_balance_threshold'      => 'required|integer|min:100',
        'lite_deficit_allowed'        => 'required|integer',
        'fmp_consideration_threshold' => 'required|integer',
        'total_amount_threshold'      => 'required|integer|min:100',
    ];

    protected function validateFtsAccountType($attribute, $ftsAccountType)
    {
        if (in_array(strtolower($ftsAccountType), ['current', 'nodal'], true) === false)
        {
            throw new BadRequestValidationFailureException('Fts Account Type can be either current or nodal');
        }
    }

    protected function validateMethod($attribute, $method)
    {
        Method::validateMethod($method);
    }

    protected function validateMode($attribute, $value)
    {
        PayoutMode::validateMode($value);
    }

    protected function validateScheduledAt($attribute, $value)
    {
        Schedule::validateScheduledAt($value);
    }

    protected function validateTypeAndAmount($input)
    {

        // We have different limits for both regular and on demand payouts. They need to be validated accordingly.
        $type = $input[Entity::TYPE];

        $amount = $input[Entity::AMOUNT];

        // Validation in case of type 'on demand'
        if (($type === Entity::ON_DEMAND) and
            ($amount > self::MAX_LIMIT_MERCHANT_ON_DEMAND_PAYOUT_AMOUNT))
        {
            $message = 'The amount may not be greater than ' . self::MAX_LIMIT_MERCHANT_ON_DEMAND_PAYOUT_AMOUNT . '.';
            throw new Exception\BadRequestValidationFailureException(
                $message,
                Entity::AMOUNT,
                $amount
            );
        }

        // Validation in case of type 'default'
        if (($type === Entity::DEFAULT) and
            ($amount > self::MAX_LIMIT_MERCHANT_PAYOUT_AMOUNT))
        {
            $message = 'The amount may not be greater than ' . self::MAX_LIMIT_MERCHANT_PAYOUT_AMOUNT . '.';
            throw new Exception\BadRequestValidationFailureException(
                $message,
                Entity::AMOUNT,
                $amount
            );
        }
        // Noticed during dev that data field sent with the above calls to BadRequestValidationFailureException came out at other end (log/response) as null
    }

    public function validateFundAccountMode($input)
    {
        /** @var Entity $payout */
        $payout = $this->entity;
        //
        // We use mode from the entity and not from the input, because
        // in case of UPI, we set the mode to UPI in modifiers (called in build).
        // But this particular validateMode function is not called via build.
        // It's explicitly called later after build. Since we don't pass input by
        // reference to build, this function does not have the modified input.
        // Due to this, we would end up NOT validating mode for UPI.
        // Hence, we take the mode from the entity directly which would be filled by build.
        //
        $mode = $payout->getMode();

        $fundAccount = $payout->fundAccount;

        $accountType = $fundAccount->getAccountType();

        Mode::validateModeOfAccountType($mode, $accountType);

        $this->validateCardAccountType($payout);

        $this->blockAmazonPayPayoutsFromDirectAccounts($payout);

        $this->validateModeAndAmount($input, $payout);
    }

    protected function validateCardAccountType(Entity $payout)
    {
        $fundAccount = $payout->fundAccount;

        $mode = $payout->getMode();

        $accountType = $fundAccount->getAccountType();

        if ($accountType === FundAccount\Type::CARD)
        {
            $cardIssuer = $fundAccount->account->getIssuer();

            $app = App::getFacadeRoot();

            if (($fundAccount->account->isAmex() === true) and
                ($cardIssuer === null))
            {
                $cardIssuer = Constants::DEFAULT_ISSUER;
            }

            $networkCode = $fundAccount->account->getNetworkCode();

            if (($cardIssuer === Issuer::SCBL) and
                ((new Card\Core)->checkAllowedNetworksForSCBL($fundAccount->account) === false))
            {
                throw new BadRequestValidationFailureException(
                    Network::getFullName($networkCode) . " cards are not supported for issuer " . Issuer::SCBL,
                    null,
                    [
                        Card\Entity::TYPE       => $fundAccount->account->getType(),
                        Card\Entity::ISSUER     => $cardIssuer,
                        Card\Entity::NETWORK    => Network::getFullName($networkCode),
                        Entity::FUND_ACCOUNT_ID => $fundAccount->getId(),

                    ]);
            }

            $cardType    = $fundAccount->account->getType();
            $cardIin     = $fundAccount->account->getIin();
            $cardNetwork = $fundAccount->account->getNetwork();
            $tokenIin    = $fundAccount->account->getTokenIin();

            // This check is needed since the tokenIIN <> card IIN mapping might have changed and earlier
            // fund accounts created on saved card flow might not be supported any more
            if ((empty($tokenIin) === false) and
                ($cardIin === substr($tokenIin,0,6)))
            {
                $app['trace']->error(TraceCode::CARD_BIN_NOT_FOUND_FOR_TOKEN_PAN,
                                    [
                                        'token_iin'    => $tokenIin,
                                        'is_tokenised' => true
                                    ]);

                throw new Exception\BadRequestValidationFailureException(
                    "Fund account not supported for payout creation.",
                    null,
                    []
                );
            }

            if($mode === Mode::CARD)
            {
                $hasSupportedModes = false;
                $supportedModeConfigs = (new Mode)->getM2PSupportedChannelModeConfig(
                    $cardIssuer,
                    $cardNetwork,
                    $cardType,
                    $cardIin
                );

                foreach ($supportedModeConfigs as $supportedMode)
                {
                    if ($supportedMode[Mode::CHANNEL] === Settlement\Channel::M2P)
                    {
                        $hasSupportedModes = true;
                    }
                }

                if($hasSupportedModes === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Payout mode CARD is not supported for the fund account',
                        null,
                        [
                            'fund_account' => $fundAccount->getId(),
                            'card_issuer'  => $cardIssuer,
                            'card_network' => $networkCode,
                            'cardType'     => $cardType
                        ]);
                }

                return;
            }

            Mode::validateModeOfIssuer($mode, $cardIssuer, $networkCode);
        }
    }

    protected function validateModeAndAmount(array $input, Entity $payout)
    {
        $fundAccount = $payout->fundAccount;

        $mode = $payout->getMode();

        $amount = $input[Entity::AMOUNT];

        $minRtgsAmount       = NodalAccount::MIN_RTGS_AMOUNT * 100;
        $maxImpsAmount       = NodalAccount::MAX_IMPS_AMOUNT * 100;
        $maxUpiAmount        = FundAccount\Validator::MAX_UPI_AMOUNT;
        $maxAmazonPayAmount  = FundAccount\Validator::MAX_WALLET_ACCOUNT_AMAZON_PAY_AMOUNT;

        if ((($mode === Mode::RTGS) and ($amount < $minRtgsAmount)) or
            (($mode === Mode::IMPS) and ($amount > $maxImpsAmount)) or
            (($mode === Mode::UPI) and ($amount > $maxUpiAmount)) or
            (($mode === Mode::AMAZONPAY) and ($amount > $maxAmazonPayAmount)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
                null,
                [
                    'amount'                          => $amount,
                    'mode'                            => $mode,
                    'min_rtgs_amount'                 => $minRtgsAmount,
                    'max_imps_amount'                 => $maxImpsAmount,
                    'maxWalletAccountAmazonPayAmount' => $maxAmazonPayAmount,
                    'fund_account_id'                 => $fundAccount->getId(),
                    'account_type'                    => $fundAccount->getAccountType(),
                ]);
        }
    }

    public function validatePayoutAmount($input, $payment)
    {
        if (isset($input[Entity::AMOUNT]) === false)
        {
            return;
        }

        $payoutAmount = $input[Entity::AMOUNT];

        $payoutAmountPending = $payment->getAmount() - $payment->getAmountPaidout();

        if ($payoutAmountPending === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_PAIDOUT);
        }

        if ($payoutAmount > $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED);
        }

        if ($payoutAmount > $payoutAmountPending)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_PENDING);
        }
    }

    /**
     * Validate that a payout can be created on a particular payment
     *
     * @param array          $input
     * @param Payment\Entity $payment
     *
     * @throws Exception\BadRequestException
     */
    public function validatePaymentForPayout(array $input, Payment\Entity $payment)
    {
        //
        // If method is not sent in input, skip the
        // following validation and allow the call to
        // fail during Payout build
        //
        if (isset($input[Entity::METHOD]) === false)
        {
            return;
        }

        // Only validating for card payments
        if ($payment->isCard() === false)
        {
            return;
        }

        $card = $payment->card;

        if ($card->getType() === Card\Type::CREDIT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_FUND_TRANSFER_ON_CREDIT_CARD_PAYMENT);
        }
    }

    public function validatePayoutStatusForApproveOrReject()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_STATE,
                null,
                [
                    'id'     => $payout->getId(),
                    'status' => $payout->getStatus(),
                ]
            );
        }
    }

    public function validateRetryPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->hasPayment() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_RETRY_FOR_PAYMENT_NOT_ALLOWED,
                null,
                [
                    'payout_id'  => $payout->getId(),
                    'payment_id' => $payout->getPaymentId(),
                ]);
        }

        $payoutStatus = $payout->getStatus();

        if ($payout->isStatusReversedOrFailed() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_RETRY_NOT_IN_REVERSED,
                null,
                [
                    'payout_id'     => $payout->getId(),
                    'payout_status' => $payoutStatus,
                ]);
        }
    }

    public function validateVaToVaPayoutForReversal()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusProcessed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUT_ALREADY_PROCESSED,
                null,
                [
                    'payout_id'           => $payout->getId(),
                    'credit_transfer_id'  => $payout->getUtr()
                ]);
        }

        if ($payout->isStatusReversed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUT_ALREADY_REVERSED,
                null,
                [
                    'payout_id'      => $payout->getId(),
                    'payout_status'  => $payout->getStatus()
                ]);
        }
    }

    public function validateProcessingQueuedPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        // Already processed by another queue job due to overlap of cron runs.
        if ($payout->isStatusQueued() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_QUEUED_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);
    }

    public function validateOnHoldPayoutProcessing()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        // Already processed by another queue job due to overlap of cron runs.
        if ($payout->isStatusOnHold() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ON_HOLD,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }
    }

    public function validatePartnerBankDowntimeHoldPayoutProcessing()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        // Already processed by another queue job due to overlap of cron runs.
        if ($payout->isStatusOnHold() === false && $payout->getQueuedReason() === QueuedReasons::GATEWAY_DEGRADED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ON_HOLD,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }
    }

    /**
     * @param Entity $payout
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function validateProcessingScheduledPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        //
        // Triggered when payout was already processed by another queue job due to overlap of cron runs.
        // Or if the payout has not yet been approved. We are not going to throw an error for payout in pending state,
        // because we have a custom auto reject logic for that and we don't wish to throw any error here
        //
        if (($payout->isStatusScheduled() === true) or
            ($payout->isStatusPending() === true))
        {
            $this->validateIsFundAccountPayout($payout);

            return;
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYOUT_NOT_SCHEDULED_STATUS,
            null,
            [
                'payout_id' => $payout->getId(),
                'status'    => $payout->getStatus(),
            ]);
    }

    public function validateProcessingBatchProcessingPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusBatchSubmitted() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_BATCH_SUBMITTED_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);
    }

    public function validatePostCreateProcessPayout()
    {
        $payout = $this->entity;

        if ($payout->isStatusCreateRequestSubmitted() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_CREATE_REQUEST_SUBMITTED_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }
    }

    public function validateProcessingPendingPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_PENDING_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);

        $this->validateCancelOrApproveOrRejectRequestForScheduledPayouts($payout);
    }

    public function validateRejectPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_PENDING_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);

        $this->validateCancelOrApproveOrRejectRequestForScheduledPayouts($payout);
    }

    public function validateIsFundAccountPayout(Entity $payout)
    {
        if (($payout->hasFundAccount() === false) or
            ($payout->hasCustomer() === true))
        {
            throw new Exception\LogicException(
                'Payout is not of RX or not a proper fund_account type',
                null,
                [
                    'payout_id'       => $payout->getId(),
                    'balance_type'    => $payout->balance->getType(),
                    'fund_account_id' => $payout->getFundAccountId(),
                    'customer_id'     => $payout->getCustomerId(),
                ]);
        }
    }

    public function validateCancel()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if (($payout->isStatusQueued() === false) and
            ($payout->isStatusScheduled() === false) and
            ($payout->isStatusOnHold() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_QUEUED_OR_SCHEDULED_STATUS,
                null,
                [
                    'payout_id'         => $payout->getId(),
                    'current_status'    => $payout->getStatus(),
                    'next_status'       => Status::CANCELLED,
                ]);
        }

        $app = App::getFacadeRoot();

        if (($payout->isStatusScheduled() === true) and
            ($app['basicauth']->isProxyAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_AUTH_NOT_SUPPORTED,
                null,
                [
                    'payout_id' => $payout->getId(),
                ]);
        }

        $this->validateCancelOrApproveOrRejectRequestForScheduledPayouts($payout);
    }

    /**
     * @param array $input
     * Rate limit on number of payout creation in Bulk Route
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkPayoutCount(array $input)
    {
        if (count($input) > self::MAX_BULK_PAYOUTS_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Current batch size ' . count($input) . ', max limit of Bulk Contact is ' . self::MAX_BULK_PAYOUTS_LIMIT,
                null,
                null
            );
        }
    }

    public function validatePayoutStatusUpdateManually(Entity $payout,
                                                       string $toStatus,
                                                       $ftsFundAccountId = null,
                                                       $ftsFundAccountType = null)
    {
        // returning currently for CA payouts till Ledger starts
        // handling of CA payouts and this route is used heavily
        // for CA to different status transitions for which
        // solutioning will be needed in ledger
        if ($payout->balance->isAccountTypeShared() === false)
            return;

        $fromStatus = $payout->getStatus();

        if (($fromStatus === Status::INITIATED and $toStatus === Status::PROCESSED) or
        ($fromStatus === Status::PROCESSED and $toStatus === Status::REVERSED))
        {
            if ((empty($ftsFundAccountId) === true) or
                (empty($ftsFundAccountType) === true))
            {
                throw new Exception\BadRequestValidationFailureException(
                sprintf("Fts fund account id and type are required to move."
                 ."payout from %s to %s ", $fromStatus, $toStatus));
            }
        }
        else if (($fromStatus === Status::INITIATED and $toStatus === Status::REVERSED) and
        ((empty($ftsFundAccountId) === true) or (empty($ftsFundAccountType) === true)))
        {
            // doing no validation here as it's not clear if the debit credit actually
            // happened or not. Raising alert to just know if this happens

            $operation = 'Received initiated to reversed in manual ';

            $data[Entity::ID] = $payout->getId();

            (new SlackNotification)->send($operation, $data, null, 0, 'x-payouts-core-alerts');
        }
    }

    protected function validateFundAccount($attribute, $value)
    {
        if (isset($value[Entity::CONTACT_ID]) === true)
        {
            throw new ExtraFieldsException(
                 Entity::FUND_ACCOUNT . '.' . Entity::CONTACT_ID
            );
        }
    }

    protected function validateFinalStatus(array $input)
    {
        $status = $input[Entity::STATUS];

        $isValid = Status::isFinalState($status);

        if ($isValid === false)
        {
            throw new BadRequestValidationFailureException(
                'Payout can be updated to only a final status',
                null,
                [
                    'status' => $status
                ]
            );
        }
    }
    /**
     * @param Entity $payout
     *
     * @throws Exception\BadRequestException
     */
    private function validateCancelOrApproveOrRejectRequestForScheduledPayouts(Entity $payout)
    {
        if ($payout->toBeScheduled() === false)
        {
            return;
        }

        Schedule::validateCancelOrApproveOrRejectRequest($payout);
    }

    protected function validateSkipWorkflow($input)
    {
        $skipWorkflow = (bool) $input[Entity::SKIP_WORKFLOW];

        if ($skipWorkflow === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Only true is valid for skip_workflow key.",
                'skip_workflow');
        }
    }

    protected function validateOrigin($input)
    {
        if (isset($input[Entity::ORIGIN]) === true)
        {
            $origin = $input[Entity::ORIGIN];

            $this->validateIfFieldShouldBeSentWithCompositeApi($input, Entity::ORIGIN, $origin);

            $this->validateIfFieldShouldBeSentBasedOnAuth(Entity::ORIGIN, $origin);

            $origin               = strtolower($origin);
            $acceptedOriginValues = array_keys(Entity::ORIGIN_SERIALIZER);

            if (in_array($origin, $acceptedOriginValues) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid origin.",
                    Entity::ORIGIN,
                    [
                        Entity::ORIGIN               => $origin,
                        self::ACCEPTED_ORIGIN_VALUES => $acceptedOriginValues,
                    ]
                );
            }
        }
    }

    protected function validateIfFieldShouldBeSentWithCompositeApi(array $input, string $fieldName, $fieldValue)
    {
        // In settlements & XPayroll service, all payouts will be made via composite API,
        // so we'll allow composite API for these apps
        if (((new Service)->isSettlementsApp() === true) or
            ((new Service)->isXPayrollApp() === true) or
            ((new Service)->isScroogeApp() === true))
            //  check if this is required, since we are not using composite api
        {
            return;
        }

        if (isset($input[Entity::FUND_ACCOUNT]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                $fieldName . " is/are not required and should not be sent",
                $fieldName,
                [
                    $fieldName => $fieldValue,
                ]
            );
        }
    }

    protected function validateIfCardDetailsReceivedForScroogeAppOnly(array $input)
    {
        $isScroogeApp = (new Service)->isScroogeApp();

        //For instant refunds migration vault token will be received instead of card details.
        //Allowing vault token only for refund service for now.
        if ((isset($input[Entity::FUND_ACCOUNT]) === true) and
            (isset($input[Entity::FUND_ACCOUNT][Entity::CARD]) === true) and
            (isset($input[Entity::FUND_ACCOUNT][Entity::CARD][Card\Entity::TOKEN]) === true) and
            ($isScroogeApp === false)) {

            throw new Exception\BadRequestValidationFailureException(
                Entity::CARD . '.' . Card\Entity::TOKEN . " is/are not required and should not be sent"
            );
        }

        // For instant refunds FTA migration and MasterCard Send integration PG merchant id will be passed
        // in internal payout creation request for accessing the sub_merchant_id mapping for M2p payouts
        // that is payout with card mode only.
        if ((isset($input[Entity::PG_MERCHANT_ID]) === true) and
            (($input[Entity::MODE] !== Mode::CARD) or
             ($isScroogeApp === false)))
        {
            throw new Exception\BadRequestValidationFailureException(
                Entity::PG_MERCHANT_ID . " is/are not required and should not be sent"
            );
        }
    }

    protected function validateIfFieldShouldBeSentBasedOnAuth(string $fieldName, $fieldValue)
    {
        if ((new Service)->isAllowedInternalApp() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $fieldName . " is/are not required and should not be sent",
                $fieldName,
                [
                    $fieldName => $fieldValue,
                ]
            );
        }
    }

    protected function validateSourceDetails($input)
    {
        if (isset($input[Entity::SOURCE_DETAILS]) === true)
        {
            $sourceDetails = $input[Entity::SOURCE_DETAILS];

            $this->validateIfFieldShouldBeSentWithCompositeApi($input, Entity::SOURCE_DETAILS, $sourceDetails);

            $this->validateIfFieldShouldBeSentBasedOnAuth(Entity::SOURCE_DETAILS, $sourceDetails);

            $this->validatePrioritySequence($input[Entity::SOURCE_DETAILS]);

            $this->validateIfCardDetailsReceivedForScroogeAppOnly($input);
        }
    }

    protected function validatePrioritySequence(array $sourceDetails)
    {
        $priorities = [];

        foreach ($sourceDetails as $sourceDetail)
        {
            array_push($priorities, $sourceDetail[PayoutSource::PRIORITY]);
        }

        $priorities = array_unique($priorities);

        if (count($priorities) !== count($sourceDetails))
        {
            throw new Exception\BadRequestValidationFailureException(
                "source_details has sources with duplicate priorities",
                Entity::SOURCE_DETAILS,
                [
                    Entity::SOURCE_DETAILS => $sourceDetails,
                ]
            );
        }
    }

    public function validateChannelAndModeForPayouts(string $merchantId,
                                                     string $channel = null,
                                                     string $destinationType = null,
                                                     string $mode = null,
                                                     string $accountType = null) : bool
    {
        if (($accountType === Balance\AccountType::DIRECT) and
            ($mode === PayoutMode::UPI))
        {
            switch ($channel)
            {
                case Settlement\Channel::RBL :
                    if ($this->isUpiModeEnabledOnRblDirectAccountForMerchantId($merchantId) === false)
                    {
                        return false;
                    }
                    break;

                case Settlement\Channel::AXIS :
                case Settlement\Channel::ICICI :
                case Settlement\Channel::YESBANK :
                    if ((new PayoutModeConfig\Service())->checkIfUpiDirectAccountChannelEnabledForMerchant($merchantId, $channel) === false)
                    {
                        return false;
                    }
                    break;

            }
        }

        return PayoutMode::validateChannelAndModeForPayouts($channel, $destinationType, $mode, $accountType);
    }

    public function validateAndUpdateCardMode(array & $input)
    {
        if((isset($input[Entity::MODE]) === true) and
            (strtolower($input[Entity::MODE]) === PayoutMode::CARD))
        {
            $input[Entity::MODE] = PayoutMode::CARD;
        }

    }

    public function validateTdsDetails(array $input)
    {
        if (isset($input[PayoutDetailsEntity::TDS]) === true)
        {
            $tdsDetails = $input[PayoutDetailsEntity::TDS];

            $this->validateTdsDetailsForAuth();

            $this->validateTdsDetailsInput($tdsDetails);

            $this->validateTdsCategoryId($tdsDetails);

            $this->validateTdsAmount($input, $tdsDetails);
        }
    }

    protected function validateTdsDetailsForAuth()
    {
        $app = App::getFacadeRoot();

        // not allowed via private auth
        if($app['basicauth']->isStrictPrivateAuth() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_TDS);
        }
    }

    protected function validateTdsDetailsInput(array $tdsDetails)
    {
        (new PayoutDetailsValidator)->validateInput(PayoutDetailsValidator::TDS_DETAILS, $tdsDetails);
    }

    protected function validateTdsCategoryId(array $tdsDetails)
    {
        /*
         * 1. validate against the list from cache
         * 2. if not found, fetch the tds-categories from tax-payment service
         * 3. add the fetched list to cache
         * 4. validate against the freshly fetched list
         */
        $inputCategoryId = $tdsDetails[PayoutDetailsEntity::CATEGORY_ID];

        $app = App::getFacadeRoot();

        /*
         * cache data format: [{"id":1,"slab":7.5},{"id":2,"slab":3.75},{"id":4,"slab":0.75}]
         */
        $categoriesListFromCache = $app['cache']->get(self::TDS_CATEGORY_ID_CACHE_KEY);

        if (empty($categoriesListFromCache) === false)
        {
            foreach ($categoriesListFromCache as $categoryFromCache)
            {
                if ($categoryFromCache['id'] === $inputCategoryId)
                {
                    return;
                }
            }
        }

        $fetchedCategories = $app['tax-payments']->getTdsCategories();

        $fetchedCategoriesInfoList = array();

        foreach ($fetchedCategories as $fetchedCategory)
        {
            $categoryInfo = [
                'id'    => $fetchedCategory['id'],
                'slab'  => $fetchedCategory['slab'],
            ];

            array_push($fetchedCategoriesInfoList, $categoryInfo);
        }

        // cache put() expect ttl in seconds => 12hrs TTL
        $app['cache']->put(self::TDS_CATEGORY_ID_CACHE_KEY, $fetchedCategoriesInfoList, self::TDS_CATEGORY_ID_CACHE_TTL);

        foreach ($fetchedCategoriesInfoList as $fetchedCategoryInfo)
        {
            if ($fetchedCategoryInfo['id'] === $inputCategoryId)
            {
                return;
            }
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TDS_CATEGORY_ID);
    }

    protected function validateTdsAmount(array $input, array $tdsDetails)
    {
        $tdsAmount = $tdsDetails[PayoutDetailsEntity::TDS_AMOUNT];

        if ($input[Entity::AMOUNT] < $tdsAmount)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TDS_AMOUNT_GREATER_THAN_PAYOUT_AMOUNT);
        }
    }

    public function validateAttachments(array $input)
    {
        if (isset($input[PayoutDetailsEntity::ATTACHMENTS_KEY]) === true)
        {
            $attachments = $input[PayoutDetailsEntity::ATTACHMENTS_KEY];

            $this->validateAttachmentsForAuth();

            $this->validateAttachmentsInput($attachments);
        }
    }

    public function validateStatusForCreditsDeductionViaPayoutService(array $input)
    {
        $status = $input[Payout\Entity::STATUS];

        /*
        Check if status is pre-create only for credits deduction
        */
        if (!(($status === Status::CREATE_REQUEST_SUBMITTED) or
            ($status === Status::SCHEDULED) or
            ($status === Status::BATCH_SUBMITTED) or
            ($status === Status::QUEUED) or
            ($status === Status::PENDING) or
            ($status === Status::ON_HOLD)))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid status:' . $status . ' sent for merchant credits deduction via payout service');
        }
    }

    protected function validateAttachmentsForAuth()
    {
        $app = App::getFacadeRoot();

        // not allowed via private auth
        if($app['basicauth']->isStrictPrivateAuth() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_ATTACHMENTS);
        }
    }

    protected function validateAttachmentsInput(array $attachments)
    {
        $payoutDetailsValidator = new PayoutDetailsValidator;

        $app = App::getFacadeRoot();

        foreach ($attachments as $attachment)
        {
            $payoutDetailsValidator->validateInput(PayoutDetailsValidator::ATTACHMENT, $attachment);

            // validating file-hash for non-internal app auths
            if($app['basicauth']->isPayoutLinkApp() === false)
            {
                $payoutDetailsValidator->validateAttachmentFileIdHash($attachment);
            }
        }
    }

    public function blockAmazonPayPayoutsFromDirectAccounts(Payout\Entity $payout)
    {
        $balance = $payout->balance;

        if (($balance->getAccountType() === Balance\AccountType::DIRECT) and
            ($payout->getMode() === FundTransfer\Mode::AMAZONPAY))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AMAZONPAY_PAYOUT_NOT_ALLOWED_ON_DIRECT_ACCOUNT,
                null,
                [
                    Payout\Entity::MERCHANT_ID     => $payout->getMerchantId(),
                    Payout\Entity::BALANCE_ID      => $payout->getBalanceId()
                ]);
        }
    }

    protected function validateAmount($input)
    {
        $app = App::getFacadeRoot();

        $merchant = $app['basicauth']->getMerchant();

        if (isset($input[Entity::AMOUNT]) === true)
        {
            $maxPayoutAmountLimit = Entity::MAX_PAYOUT_LIMIT;

            if ((new Service)->isSettlementsApp() === true)
            {
                $maxPayoutAmountLimit = Entity::MAX_SETTLEMENT_PAYOUT_LIMIT;
            }

            if ((empty($merchant) === false) and
                ($merchant->isFeatureEnabled(Features::INCREASE_PAYOUT_LIMIT) === true))
            {
                $maxPayoutAmountLimit = Entity::MAX_INCREASED_PAYOUT_LIMIT;
            }

            if ($input[Entity::AMOUNT] > $maxPayoutAmountLimit)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The amount may not be greater than " . $maxPayoutAmountLimit . ".",
                    Entity::AMOUNT,
                    [
                        Entity::AMOUNT => $input[Entity::AMOUNT],
                    ]
                );
            }

            if ($input[Entity::AMOUNT] < self::MIN_TRANSACTION_AMOUNT_ALLOWED_IN_PAISE)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Minimum transaction amount should be 100 paise",
                    Entity::AMOUNT,
                    [
                        Entity::AMOUNT => $input[Entity::AMOUNT],
                    ]
                );
            }
        }
    }

    public function validateBeneStatusReceivedFromFts(string $status)
    {
        if($status != 'started' and $status != 'resolved')
        {
            throw new Exception\BadRequestValidationFailureException(
                "The status received from fts is " . $status . ".",
                null,
                [
                    'status' => $status,
                ]
            );
        }
    }

    public function validatePartnerBankHealthNotificationFromFTS($payload)
    {
        $this->setStrictFalse()->validateInput(Validator::PARTNER_BANK_HEALTH_NOTIFICATION, $payload);

        if (!(($payload['status'] === Events::STATUS_DOWNTIME) ||
              ($payload['status'] == Events::STATUS_UPTIME))) {
            throw new Exception\BadRequestValidationFailureException(
                "The status received from fts is " . $payload['status'] . ".",
                null,
                [
                    'status' => $payload['status'],
                ]
            );
        }

        if (($payload['status'] === Events::STATUS_DOWNTIME) &&
            (empty($payload['include_merchants']))) {
            throw new Exception\BadRequestValidationFailureException(
                "Empty include merchants list received from fts is " . $payload . ".",
                null,
                [
                    'payload' => $payload,
                ]
            );
        }
    }


    public function isUpiModeEnabledOnRblDirectAccountForMerchantId(string $merchantId)
    {
        $featureList = (new FeatureRepo())->findMerchantWithFeatures($merchantId, [Features::RBL_CA_UPI]);
        return (count($featureList) !== 0);
    }

    public function validatebulkPurposeCreation(int $count)
    {
        if($count >= 100 ){
            throw new Exception\BadRequestValidationFailureException(
                "The limit for max purpose creation in 1 call is 100, please reduce the number from ".$count."."
            );
        }
    }

    public function validateMerchantSlasForOnHoldPayouts(array $input) {
        $slas = array_keys($input);

        if(count($slas) == 0) {
            throw new Exception\BadRequestValidationFailureException(
                "There should be atleast one SLA => merchantIds key value pair in request body"
            );
        }

        foreach($slas as $sla) {
            if(!is_numeric($sla) || !is_int((int) $sla) || (int) $sla < 0) {
                throw new Exception\BadRequestValidationFailureException(
                    "sla value should be an integer greater than 0"
                );
            }
        }

        $allMerchantIds = [];
        $merchantIdsArrays = array_values($input);

        foreach($merchantIdsArrays as $merchantIdsArray) {
            if(!is_array($merchantIdsArray) || !is_sequential_array($merchantIdsArray)) {
                throw new Exception\BadRequestValidationFailureException(
                    "The value for a SLA should be a non-empty list of merchantIds"
                );
            }

            array_map(function($value) use (& $allMerchantIds) {
                if(!is_string($value) || empty($value)) {
                    throw new Exception\BadRequestValidationFailureException(
                        "merchantId should be a non-empty string"
                    );
                }

                array_push($allMerchantIds, $value);
            }, $merchantIdsArray);
        }

        if(count($allMerchantIds) !== count(array_flip($allMerchantIds))) {
            throw new Exception\BadRequestValidationFailureException(
                "all merchantIds should be a unique"
            );
        }
    }

    public function validatePayoutForApprovalViaOAuth()
    {
        $app = App::getFacadeRoot();

        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->getMerchantId() !== $app['basicauth']->getMerchantId())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_APPROVAL_TOKEN_INVALID,
                null,
                [
                    'id'     => $payout->getId(),
                    'status' => $payout->getStatus(),
                ]
            );
        }
    }
}
