<?php

namespace RZP\Models\Payout;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger;

use RZP\Constants;
use RZP\Http\Route;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Batch;
use RZP\Base\BuilderEx;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Workflow;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Models\PayoutLink;
use RZP\Models\Transaction;
use RZP\Models\FeeRecovery;
use RZP\Models\FundAccount;
use RZP\Constants\Timezone;
use RZP\Models\PayoutSource;
use RZP\Models\FundTransfer;
use RZP\Models\BankingAccount;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\Balance;
use RZP\Models\Admin\Permission;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Settlement\Channel;
use Razorpay\IFSC\IFSC as BaseIFSC;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Models\Payout\Batch as PayoutsBatch;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\SubVirtualAccount\Core as SubVaCore;
use RZP\Exception\UserWorkflowNotApplicableException;
use RZP\Models\PayoutSource\Core as PayoutSourceCore;
use RZP\Models\PayoutMeta\Entity as PayoutMetaEntity;
use Razorpay\OAuth\Application\Repository as AppRepo;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;
use RZP\Models\BankingAccountService\Channel as BASChannel;
use RZP\Models\PayoutsStatusDetails as PayoutsStatusDetails;
use RZP\Models\PayoutsDetails\Entity as PayoutsDetailsEntity;

/**
 * @property Customer\Entity        $customer
 * @property Merchant\Entity        $merchant
 * @property User\Entity            $user
 * @property FundAccount\Entity     $fundAccount
 * @property Transaction\Entity     $transaction
 * @property BankingAccount\Entity  $bankingAccount
 * @property Balance\Entity         $balance
 */
class Entity extends Base\PublicEntity
{
    use HasBalance;
    use NotesTrait;

    const ID                                    = 'id';
    const MERCHANT_ID                           = 'merchant_id';
    const CUSTOMER_ID                           = 'customer_id';
    const FUND_ACCOUNT_ID                       = 'fund_account_id';
    const METHOD                                = 'method';
    const BALANCE_ID                            = 'balance_id';
    const DESTINATION_ID                        = 'destination_id';
    const DESTINATION_TYPE                      = 'destination_type';
    const USER_ID                               = 'user_id';
    const PURPOSE                               = 'purpose';
    const PURPOSE_TYPE                          = 'purpose_type';
    const AMOUNT                                = 'amount';
    const BASE_AMOUNT                           = 'base_amount';
    const CURRENCY                              = 'currency';
    const NOTES                                 = 'notes';
    const FEES                                  = 'fees';
    const TAX                                   = 'tax';
    const PAYMENT_ID                            = 'payment_id';
    const TRANSACTION_ID                        = 'transaction_id';
    const TRANSACTION_TYPE                      = 'transaction_type';
    const BATCH_FUND_TRANSFER_ID                = 'batch_fund_transfer_id';
    const STATUS                                = 'status';
    const CHANNEL                               = 'channel';
    const ATTEMPTS                              = 'attempts';
    const UTR                                   = 'utr';
    const FAILURE_REASON                        = 'failure_reason';
    const RETURN_UTR                            = 'return_utr';
    const REMARKS                               = 'remarks';
    const PENDING_AT                            = 'pending_at';
    const PROCESSED_AT                          = 'processed_at';
    const REVERSED_AT                           = 'reversed_at';
    const FAILED_AT                             = 'failed_at';
    const REJECTED_AT                           = 'rejected_at';
    const QUEUED_AT                             = 'queued_at';
    const CANCELLED_AT                          = 'cancelled_at';
    const BATCH_SUBMITTED_AT                    = 'batch_submitted_at';
    const CREATE_REQUEST_SUBMITTED_AT           = 'create_request_submitted_at';
    const SETTLED_ON                            = 'settled_on';
    const TYPE                                  = 'type';
    const MODE                                  = 'mode';
    const REFERENCE_ID                          = 'reference_id';
    const NARRATION                             = 'narration';
    const FTS_TRANSFER_ID                       = 'fts_transfer_id';
    const BATCH_ID                              = 'batch_id';
    const IDEMPOTENCY_KEY                       = 'idempotency_key';
    const INITIATED_AT                          = 'initiated_at';
    const PAYOUT_LINK_ID                        = 'payout_link_id';
    const PRICING_RULE_ID                       = 'pricing_rule_id';
    const FEE_TYPE                              = 'fee_type';
    const WORKFLOW_FEATURE                      = 'workflow_feature';
    const ORIGIN                                = 'origin';
    const SOURCE_DETAILS                        = 'source_details';
    const IS_PAYOUT_SERVICE                     = 'is_payout_service';
    const IS_WORKFLOW_ACTIVATED                 = 'is_workflow_activated';
    const META                                  = 'meta';
    const REGISTERED_NAME                       = 'registered_name';
    const CANCELLATION_USER_ID                  = 'cancellation_user_id';
    const CANCELLATION_USER                     = 'cancellation_user';
    const QUEUED_REASON                         = 'queued_reason';
    const SOURCE_TYPE_EXCLUDE                   = 'source_type_exclude';
    const ON_HOLD_AT                            = 'on_hold_at';
    const PAYOUT_FETCH_MULTIPLE                 = 'payout_fetch_multiple';
    const VPA                                   = 'vpa';

    // string constants
    const PARTNER_APPLICATION    = 'partner_application';

    // status code send from bank side
    const STATUS_CODE            = 'status_code';

    const ERROR                  = 'error';

    // to send reason and description for queued state
    const QUEUEING_DETAILS       = 'queueing_details';

    // to send latest status details reason and description
   const STATUS_DETAILS          = 'status_details';

   // to show latest status details for  a payout (to be used at frontend side)
   const STATUS_SUMMARY          = 'status_summary';

   // to store latest status details id
   const STATUS_DETAILS_ID       = 'status_details_id';

    // scheduled_at is the timestamp for when the merchant schedules the payout to be processed
    const SCHEDULED_AT                          = 'scheduled_at';
    // scheduled_on is the timestamp of when the payout changes state to scheduled
    const SCHEDULED_ON                          = 'scheduled_on';
    // Public attribute
    const DESTINATION                           = 'destination';
    //transferred_at is the timestamp when payout request is sent to fts
    const TRANSFERRED_AT                        = 'transferred_at';

    // Constant for passing extra details like credits and fund_account for PS payouts.
    const EXTRA_INFO                   = 'extra_info';
    const CREDITS_INFO                 = 'credits_info';
    const FETCH_UNUSED_CREDITS_SUCCESS = 'fetch_unused_credits_success';
    const UNUSED_CREDITS               = 'unused_credits';

    const FETCH_FUND_ACCOUNT_INFO_SUCCESS = 'fetch_fund_account_info_success';
    const FUND_ACCOUNT_INFO               = 'fund_account_info';

    // These are used while creating merchant payouts.
    // Min amount refers to the minimum amount payout has to be
    // Modulo refers to the multiples in which amount should be
    // Buffer Amount specifies the remaining merchant balance (buffer balance) after the payout
    const MIN_AMOUNT                            = 'min_amount';
    const MODULO                                = 'modulo';
    const BUFFER_AMOUNT                         = 'buffer_amount';

    // Constants for payout types
    const DEFAULT     = 'default';
    const ON_DEMAND   = 'on_demand';
    const SUB_ACCOUNT = 'sub_account';

    //Constants for null types
    // These strings if passed by the merchant will be treated as null
    const NULL  = 'null';
    const NONE  = 'none';
    const EMPTY = 'empty';

    //Partial Search
    const CONTACT_PHONE_PS       = 'contact_phone_ps';
    const CONTACT_EMAIL_PS         = 'contact_email_ps';
    const FUND_ACCOUNT_NUMBER  = 'fund_account_number';

    // Additional input/output attributes
    const CONTACT_NAME  = 'contact_name';
    const CONTACT_PHONE = 'contact_phone';
    const CONTACT_ID    = 'contact_id';
    const CONTACT_EMAIL = 'contact_email';
    const CONTACT_TYPE  = 'contact_type';
    const REVERSED_FROM = 'reversed_from';
    const REVERSED_TO   = 'reversed_to';
    const PRODUCT       = 'product';
    const SOURCE_TYPE   = 'source_type';

    // Input/output for scheduled payouts
    const SCHEDULED_FROM = 'scheduled_from';
    const SCHEDULED_TO   = 'scheduled_to';
    const SORTED_ON      = 'sorted_on';

    const PENDING_ON_ME    = 'pending_on_me';
    const PENDING_ON_ROLES = 'pending_on_roles';
    const PENDING_ON_USER  = 'pending_on_user';

    // Keys for finding pending payouts from new workflow service tables,
    // The tables for new workflow service are joined with existing payouts tables
    // TO figure out this information.
    //
    // Usage: Front end sends query params as `pending_on_roles`, backend reads these fields,
    // queries the WF tables and sends back the request.
    // Check `getPendingPayoutsForRoles` function in Payout\Service
    const PENDING_ON_ME_VIA_WFS    = 'pending_on_me_via_wfs';
    const PENDING_ON_ROLES_VIA_WFS = 'pending_on_roles_via_wfs';
    const PENDING_ON_USER_VIA_WFS  = 'pending_on_user_via_wfs';

    // Input keys
    const ACCOUNT_NUMBER       = 'account_number';
    const QUEUE_IF_LOW_BALANCE = 'queue_if_low_balance';
    const PAYOUT_IDS           = 'payout_ids';
    const PAYOUT_ID            = 'payout_id';
    const SKIP_WORKFLOW        = 'skip_workflow';
    const FORCE_REJECT         = 'force_reject';
    const BULK_REJECT_AS_OWNER = 'bulk_reject_as_owner';
    //Input key to support search using reversal_id
    const REVERSAL_ID                           = 'reversal_id';

    // Workflow can be enabled for internal contacts by passing enable_workflow_for_internal_contact field in input.
    const ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT = 'enable_workflow_for_internal_contact';

    // Output keys
    const WORKFLOW_HISTORY   = 'workflow_history';
    const BANKING_ACCOUNT_ID = 'banking_account_id';

    // Used only for `visible` array
    const INTERNAL_STATUS = 'internal_status';

    const PAYOUT_MODE     = 'payout_mode';

    // Used for Queued and Scheduled Payout Processing
    const BALANCE_IDS     = 'balance_ids';
    const BALANCE_IDS_NOT = 'balance_ids_not';
    const BALANCES        = 'balances';

    // Relations
    const USER            = 'user';
    const CUSTOMER        = 'customer';
    const FUND_ACCOUNT    = 'fund_account';
    const TRANSACTION     = 'transaction';
    const REVERSAL        = 'reversal';
    const WORKFLOW_ACTION = 'workflow_action';

    const MAX_PAYOUT_LIMIT            = 10000000000;
    const MAX_SETTLEMENT_PAYOUT_LIMIT = 300000000000;
    const MAX_INCREASED_PAYOUT_LIMIT  = 50000000000;

    // Used for composite API request input
    const CONTACT = 'contact';
    const PAYOUT  = 'payout';
    const IFSC    = 'ifsc';
    const CARD    = 'card';
    const NUMBER  = 'number';

    // Used for delete card meta data route for Payout Service
    const CARD_ID     = 'card_id';
    const VAULT_TOKEN = 'vault_token';

    // The parameter is passed by Scrooge to us for M2p payouts, to access the sub_merchant_id
    // mapping at FTS for M2p.
    const PG_MERCHANT_ID = 'pg_merchant_id';

    // internal
    const IS_INTERNAL = "is_internal";

    // These are the modes for which we shall be throttling batch payouts from payout core side.
    // For other modes, we shall pass on the request normally.
    const BATCH_PAYOUTS_DELAYED_INITIATION_MODES = [
        PayoutMode::NEFT,
        PayoutMode::RTGS,
    ];

    // Used exclusively for Elasticsearch queries
    const CONTACT_EMAIL_RAW = 'contact_email.raw';
    const CONTACT_EMAIL_PARTIAL_SEARCH = 'contact_email.partial_search';

    // Constants for fee_type field
    const FREE_PAYOUT = 'free_payout';

    // Constants used for scheduled payout summary
    const TODAY         = 'today';
    const NEXT_TWO_DAYS = 'next_two_days';
    const NEXT_WEEK     = 'next_week';
    const NEXT_MONTH    = 'next_month';
    const ALL_TIME      = 'all_time';

    const COUNT = 'count';
    const DAYS  = 'days';

    const SCHEDULED_PAYOUTS_SUMMARY = [
        self::TODAY,
        self::NEXT_TWO_DAYS,
        self::NEXT_WEEK,
        self::NEXT_MONTH,
        self::ALL_TIME
    ];

    const IS_NULL = [
        self::NULL,
        self::NONE,
        self::EMPTY
    ];

    const API       = 'api';
    const DASHBOARD = 'dashboard';

    const ORIGIN_DESERIALIZER = [
        1 => self::API,
        2 => self::DASHBOARD,
    ];

    const ORIGIN_SERIALIZER = [
        self::API       => 1,
        self::DASHBOARD => 2,
    ];

    const RAZORX_RETRY_COUNT = 2;

    const QUEUE_PAYOUT_CREATE_REQUEST = 'queue_payout_create_request';

    // To be used for sample batch file download
    const FILE_TYPE         = 'file_type';
    const SAMPLE_FILE       = 'sample_file';
    const TEMPLATE_FILE     = 'template_file';
    const FILE_EXTENSION    = 'file_extension';

    const PAYOUT_METHOD     = 'payout_method';
    const BANK_TRANSFER     = 'bank_transfer';
    const AMAZONPAY         = 'amazonpay';
    const UPI               = 'upi';

    const BENEFICIARY_INFO      = 'beneficiary_info';
    const BENEFICIARY_ID        = 'id';
    const BENEFICIARY_DETAILS   = 'details';

    // To be used for bulk improvements project.
    const RUPEES                    = 'rupees';
    const PAISE                     = 'paise';
    const NEW_USER                  = 'new_user';
    const EXISTING_NON_BULK_USER    = 'existing_non_bulk_user';
    const EXISTING_BULK_USER_PAISE  = 'existing_bulk_user_paise';
    const EXISTING_BULK_USER_RUPEES = 'existing_bulk_user_rupees';
    const MERCHANT_IDS              = 'merchant_ids';

    //App Framework
    const BULK_PAYOUT_APP           = 'bulk_payout_app';

    const CONFIG_TYPE               = 'config_type';

    // Attribute exposed in public response for proxy auth for ICICI 2FA enabled merchants.
    // This attribute indicates the reason why a payout is in pending state.
    const PENDING_REASON            = 'pending_reason';

    // Source update constants
    const PREVIOUS_STATUS = 'previous_status';
    const EXPECTED_CURRENT_STATUS = 'expected_current_status';

    protected $queueFlag = false;

    protected $statusDetails = [
        'reason'        => null,
        'description'   => null,
    ];

    // this variable is used to locally identify if its a VA to VA payout.
    protected $isCreditTransferBasedPayout = null;

    // This flag will be used to decide if FTS fund transfer has to be async call.
    protected $syncFtsFundTransfer = false;

    /** @var FundTransfer\Attempt\Entity $fta */
    protected $fta = null;

    protected $composite = false;

    /*
     * We will check this flag while calling saveOrFail on payout.
     * If it is payout service payout and this flag is not set then we will not go ahead with saving the entity.
     */
    protected $savePayoutServicePayout = false;

    /*
    This variable is defined to store the expected fee type during payout create or payout process (for states like
    queued, pending, scheduled, etc. It is also used to decrement the counter for free payouts in async manner for the
    payouts where it was increased and then the payout didn't get the fee type as free payout.
     */
    protected $expectedFeeType = null;

    /*
    This variable is defined to store the source_details that came in the input request. It is later used to create the
    payout_source for all those details and associate them with the payout.
    Example:

    $inputSourceDetails = [
        [
            "source_id"   => "100000000000sa",
            "source_type" => "payout_link",
            "priority"    => 1
        ],
        [
            "source_id"   => "100000000001sa",
            "source_type" => "vendor_payment",
            "priority"    => 2
        ]
    ];
     */
    protected $inputSourceDetails = null;

    /*
    This variable is defined to store the output of razorx request to know if payout should be created in
    create_request_submitted state. This is done as we need to remove counter calls for free payouts for these merchants
    during payout create and thus, if we are anyways calling razorx once outside payout create db txn, we don't want to
    again call razorx inside. This will help in 2 ways -
    1. We will reduce one razorx call inside db txn for payout create.
    2. There won't be any discrepancy as in the case where the 1st call was successful and resulted in output as 'on'
    and during the second call, razorx went down or something and even after retries, we got the result as 'control', so
    in this case there is a possibility that a non free payout will be initiated even when free payouts are remaining
    for the merchant.
     */
    protected $queuePayoutCreateRequest = false;

    /*
    This flag is set to true when ledger response is awaited due to some failure on ledger
    In such cases this can be utilized to skip fts calls which will be handled later when ledger
    status is checked for success in async
    */
    protected $ledgerResponseAwaitedFlag = false;

    /**
     * @var bool
     *
     * This flag will be set to true if we have deducted the balance already for this payout. This will ensure that
     * we do not deduct the balance again in the transaction creation flow.
     */
    protected $balancePreDeducted = false;

    protected $transactionIdWhenBalancePreDeducted        = '';

    protected $transactionCreatedAtWhenBalancePreDeducted = 0;

    protected $closingBalanceWhenBalancePreDeducted       = 0;

    /**
     * In case of direct banking, we get the transactions directly from the bank. We don't create transactions
     * from our system. Sometimes, we are not able to map a transaction to one of the payouts in our system.
     * In these cases, we create the transaction against `external` entity. Later when we are able to map
     * the transaction to the payout entity, we create a dummy transaction to replace the original transaction's
     * attributes with the right payout transaction attributes. In this flow, we don't want to do any balance
     * related stuff since that would have already been taken care of when the original transaction was created.
     * This also ensures balance validations are not done, since they could fail because of double deductions - one
     * via external and now another via payout.
     *
     * @var bool
     */
    protected $shouldValidateAndUpdateBalancesFlag = true;

    /** @var Balance\Entity */
    protected $masterBalance = null;

    protected $entity = 'payout';

    protected $table  = Table::PAYOUT;

    protected $generateIdOnCreate = true;

    // Any changes to this sign will affect LedgerStatus Job as well
    protected static $sign = 'pout';

    protected static $generators = [
        self::ID
    ];

    protected $fillable = [
        self::ID,
        self::METHOD,
        self::PURPOSE,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::PROCESSED_AT,
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::SETTLED_ON,
        self::TYPE,
        self::MODE,
        self::REFERENCE_ID,
        self::NARRATION,
        self::IDEMPOTENCY_KEY,
        self::PRICING_RULE_ID,
        self::BATCH_SUBMITTED_AT,
        self::FEE_TYPE,
        self::SCHEDULED_AT,
        self::SCHEDULED_ON,
        self::ORIGIN,
        self::CREATE_REQUEST_SUBMITTED_AT,
        self::IS_PAYOUT_SERVICE,
        self::ON_HOLD_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::FUND_ACCOUNT_ID,
        self::DESTINATION,
        self::USER_ID,
        self::AMOUNT,
        self::BALANCE_ID,
        self::CURRENCY,
        self::NOTES,
        self::REVERSAL,
        self::PURPOSE,
        self::PURPOSE_TYPE,
        self::METHOD,
        self::FEES,
        self::TAX,
        self::PAYMENT_ID,
        self::TRANSACTION_ID,
        self::BATCH_FUND_TRANSFER_ID,
        self::STATUS,
        self::CHANNEL,
        self::ATTEMPTS,
        self::UTR,
        self::RETURN_UTR,
        self::FAILURE_REASON,
        self::REMARKS,
        self::PROCESSED_AT,
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::SETTLED_ON,
        self::TYPE,
        self::MODE,
        self::WORKFLOW_HISTORY,
        self::REFERENCE_ID,
        self::NARRATION,
        self::BATCH_ID,
        self::INTERNAL_STATUS,
        self::BANKING_ACCOUNT_ID,
        self::INITIATED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::IDEMPOTENCY_KEY,
        self::PRICING_RULE_ID,
        self::BATCH_SUBMITTED_AT,
        self::FEE_TYPE,
        self::WORKFLOW_FEATURE,
        self::SCHEDULED_AT,
        self::SCHEDULED_ON,
        self::ORIGIN,
        self::CREATE_REQUEST_SUBMITTED_AT,
        self::SOURCE_DETAILS,
        self::IS_PAYOUT_SERVICE,
        self::TRANSFERRED_AT,
        self::STATUS_CODE,
        self::META,
        self::REGISTERED_NAME,
        self::CANCELLATION_USER_ID,
        self::CANCELLATION_USER,
        self::QUEUEING_DETAILS,
        self::ON_HOLD_AT,
        self::STATUS_DETAILS_ID,
        self::STATUS_DETAILS,
        self::FTS_TRANSFER_ID,
        self::QUEUED_REASON,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::FUND_ACCOUNT_ID,
        self::FUND_ACCOUNT,
        self::AMOUNT,
        self::CURRENCY,
        self::TRANSACTION_ID,
        self::TRANSACTION,
        self::PENDING_ON_USER,
        self::WORKFLOW_HISTORY,
        self::NOTES,
        self::FEES,
        self::TAX,
        self::STATUS,
        self::INTERNAL_STATUS,
        self::PENDING_REASON,
        self::PURPOSE,
        self::UTR,
        self::USER_ID,
        self::USER,
        self::MODE,
        self::REFERENCE_ID,
        self::NARRATION,
        self::BATCH_ID,
        self::REVERSAL,
        self::CANCELLED_AT,
        self::QUEUED_AT,
        self::BANKING_ACCOUNT_ID,
        self::INITIATED_AT,
        self::PENDING_AT,
        self::PROCESSED_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::FAILURE_REASON,
        self::CREATED_AT,
        self::FEE_TYPE,
        self::SCHEDULED_AT,
        self::SCHEDULED_ON,
        self::ORIGIN,
        self::SOURCE_DETAILS,
        self::META,
        self::REGISTERED_NAME,
        self::REMARKS,
        self::CANCELLATION_USER_ID,
        self::CANCELLATION_USER,
        self::QUEUEING_DETAILS,
        self::ON_HOLD_AT,
        self::STATUS_DETAILS,
        self::MERCHANT_ID,
        self::STATUS_SUMMARY,
        self::STATUS_DETAILS_ID,
    ];

    protected $webhook = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::FUND_ACCOUNT_ID,
        self::FUND_ACCOUNT, // Adding this for payout approval via oauth, which will have bank_account details for trustees
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::FEES,
        self::TAX,
        self::STATUS,
        self::PURPOSE,
        self::UTR,
        self::MODE,
        self::REFERENCE_ID,
        self::REGISTERED_NAME,
        self::NARRATION,
        self::BATCH_ID,
        self::FAILURE_REASON,
        self::CREATED_AT,
        self::ERROR,
        self::QUEUEING_DETAILS,
        self::STATUS_DETAILS,
    ];

    protected static $modifiers = [
        self::NARRATION,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::STATUS,
        self::BALANCE_ID,
        self::DESTINATION,
        self::CUSTOMER_ID,
        self::USER_ID,
        self::FUND_ACCOUNT_ID,
        self::FUND_ACCOUNT,
        self::PENDING_ON_USER,
        self::WORKFLOW_HISTORY,
        self::BANKING_ACCOUNT_ID,
        self::REVERSAL,
        // We want to show the failure reason only if the status is reversed.
        // This is because we might have intermittent failure reasons even
        // when the payout is not completely processed (succeeded/failed)
        self::FAILURE_REASON,
        self::INITIATED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::PROCESSED_AT,
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::TRANSACTION_ID,
        self::BATCH_ID,
        self::TRANSACTION,
        self::SCHEDULED_ON,
        self::ORIGIN,
        self::SOURCE_DETAILS,
        self::REGISTERED_NAME,
        self::META,
        self::REMARKS,
        self::CANCELLATION_USER_ID,
        self::CANCELLATION_USER,
        self::QUEUEING_DETAILS,
        self::ON_HOLD_AT,
        self::STATUS_DETAILS,
        self::STATUS_SUMMARY,
        self::INTERNAL_STATUS,
        self::PENDING_REASON,
    ];

    // TODO: review all the public setters and remove fileds which are not required.
    protected $publicSettersListView = [
        self::ID,
        self::ENTITY,
        self::STATUS,
        self::BALANCE_ID,
        self::DESTINATION,
        self::CUSTOMER_ID,
        self::USER_ID,
        self::FUND_ACCOUNT_ID,
        self::FUND_ACCOUNT,
        self::BANKING_ACCOUNT_ID,
        self::REVERSAL,
        // If the merchant is still on the workflow system in api codebase, this flag will be set in the
        // public setter.
        // On the other hand, if the merchant is on WFS, an aggregated query will be run after all the
        // public setters to determine this flag for all the payouts in the payout list API response
        self::PENDING_ON_USER,
        // We want to show the failure reason only if the status is reversed.
        // This is because we might have intermittent failure reasons even
        // when the payout is not completely processed (succeeded/failed)
        self::FAILURE_REASON,
        self::INITIATED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::PROCESSED_AT,
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::TRANSACTION_ID,
        self::BATCH_ID,
        self::TRANSACTION,
        self::SCHEDULED_ON,
        self::ORIGIN,
        self::SOURCE_DETAILS,
        self::REGISTERED_NAME,
        self::META,
        self::REMARKS,
        self::CANCELLATION_USER_ID,
        self::CANCELLATION_USER,
        self::QUEUEING_DETAILS,
        self::ON_HOLD_AT,
        self::STATUS_DETAILS,
        self::STATUS_SUMMARY,
    ];

    protected $defaults = [
        self::USER_ID              => null,
        self::PURPOSE              => Purpose::REFUND,
        self::FUND_ACCOUNT_ID      => null,
        self::BATCH_ID             => null,
        self::NOTES                => [],
        self::ATTEMPTS             => 1,
        self::TYPE                 => self::DEFAULT,
        self::MODE                 => null,
        self::UTR                  => null,
        self::RETURN_UTR           => null,
        self::FAILURE_REASON       => null,
        self::REFERENCE_ID         => null,
        self::NARRATION            => null,
        self::FEES                 => 0,
        self::TAX                  => 0,
        self::IDEMPOTENCY_KEY      => null,
        self::PRICING_RULE_ID      => null,
        self::FEE_TYPE             => null,
        self::WORKFLOW_FEATURE     => null,
        self::ORIGIN               => self::API,
        self::STATUS_CODE          => null,
        self::CANCELLATION_USER_ID => null,
        self::STATUS_DETAILS_ID    => null,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::FEES,
        self::TAX,
    ];

    protected $casts = [
        self::AMOUNT      => 'int',
        self::FEES        => 'int',
        self::TAX         => 'int',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PROCESSED_AT,
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::INITIATED_AT,
        self::SETTLED_ON,
        self::BATCH_SUBMITTED_AT,
        self::SCHEDULED_AT,
        self::SCHEDULED_ON,
        self::CREATE_REQUEST_SUBMITTED_AT,
        self::ON_HOLD_AT,
    ];

    protected $appends = [
        self::INTERNAL_STATUS,
    ];

    protected $ignoredRelations = [
        'destination',
    ];

    public $payoutServiceResponse;

    // ============================= RELATIONS =============================

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function payoutLink()
    {
        return $this->belongsTo(PayoutLink\Entity::class);
    }

    public function destination()
    {
        return $this->morphTo();
    }

    public function fundTransferAttempts()
    {
        return $this->morphMany('RZP\Models\FundTransfer\Attempt\Entity', 'source');
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function fundAccount()
    {
        return $this->belongsTo(FundAccount\Entity::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment\Entity::class);
    }

    public function reversal()
    {
        return $this->belongsTo(Reversal\Entity::class, self::ID, Reversal\Entity::ENTITY_ID);
    }

    public function bankingAccount()
    {
        //
        // This defines payout's relation to banking_account
        // via balance's relation to banking_account.
        //
        return $this->balance->bankingAccount();
    }

    public function workflowActions()
    {
        return $this->morphMany(Workflow\Action\Entity::class, 'entity', 'entity_name');
    }

    /**
     * Can be customer_transaction (used for customer wallets) or just transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function transaction()
    {
        return $this->morphTo();
    }

    public function batchFundTransfer()
    {
        return $this->belongsTo(FundTransfer\Batch\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    /**
     * The batch which created this payout entity.
     *
     * @return null|\Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function batch()
    {
        return $this->belongsTo(Batch\Entity::class);
    }

    public function payoutSources()
    {
        return $this->hasMany(PayoutSource\Entity::class);
    }

    public function payoutMeta()
    {
        return $this->hasOne(PayoutMetaEntity::class);
    }

    public function payoutsDetails()
    {
        return $this->hasOne(PayoutsDetailsEntity::class);
    }

    public function payoutsStatusDetails()
    {
        return $this->hasMany(PayoutsStatusDetails\Entity::class);
    }
    // ============================= END RELATIONS =============================

    // ============================= GETTERS =============================

    public function getEntitySign(): string
    {
        return self::$sign;
    }

    public function getIsPayoutService(): bool
    {
        return ($this->getAttribute(self::IS_PAYOUT_SERVICE) === 1);
    }

    public function getPurpose()
    {
        return $this->getAttribute(self::PURPOSE);
    }

    public function getPayoutLinkId()
    {
        $payoutLinkId = $this->getAttribute(self::PAYOUT_LINK_ID);

        if(empty($payoutLinkId) === true)
        {
            return null;
        }

        return "poutlk_" . $payoutLinkId;
    }

    public function getPurposeType()
    {
        return $this->getAttribute(self::PURPOSE_TYPE);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getReferenceId()
    {
        return $this->getAttribute(self::REFERENCE_ID);
    }

    public function getNarration()
    {
        return $this->getAttribute(self::NARRATION);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getFailureReason()
    {
        return $this->getAttribute(self::FAILURE_REASON);
    }

    public function hasCustomer()
    {
        return ($this->isAttributeNotNull(self::CUSTOMER_ID) === true);
    }

    public function getFundAccountId()
    {
        return $this->getAttribute(self::FUND_ACCOUNT_ID);
    }

    public function hasFundAccount()
    {
        return ($this->isAttributeNotNull(self::FUND_ACCOUNT_ID) === true);
    }

    public function getBatchId()
    {
        return $this->getAttribute(self::BATCH_ID);
    }

    public function hasBatch(): bool
    {
        return $this->isAttributeNotNull(self::BATCH_ID);
    }

    public function isOfMerchantTransaction(): bool
    {
        return ($this->getAttribute(self::TRANSACTION_TYPE) === Constants\Entity::TRANSACTION);
    }

    public function isCustomerPayout(): bool
    {
        return ($this->getAttribute(self::TRANSACTION_TYPE) === Constants\Entity::CUSTOMER_TRANSACTION);
    }

    public function toBeQueued(): bool
    {
        return ($this->queueFlag === true);
    }

    public function toBeScheduled(): bool
    {
        $scheduledAt = $this->getScheduledAt();

        return (empty($scheduledAt) === false);
    }

    public function shouldValidateAndUpdateBalances(): bool
    {
        return ($this->shouldValidateAndUpdateBalancesFlag === true);
    }

    public function isBalancePreDeducted(): bool
    {
        return ($this->balancePreDeducted === true);
    }

    public function getTransactionIdWhenBalancePreDeducted(): string
    {
        return $this->transactionIdWhenBalancePreDeducted;
    }

    public function getClosingBalanceWhenBalancePreDeducted()
    {
        return $this->closingBalanceWhenBalancePreDeducted;
    }

    public function getTransactionCreatedAtWhenBalancePreDeducted(): int
    {
        return $this->transactionCreatedAtWhenBalancePreDeducted;
    }

    public function makeSyncFtsFundTransfer(): bool
    {
        return ($this->syncFtsFundTransfer === true);
    }

    public function getSavePayoutServicePayoutFlag()
    {
        return $this->savePayoutServicePayout;
    }

    public function getFta()
    {
        return $this->fta;
    }

    /**
     * FeeCalculator calls `$entity->getFee()` for all the pricing entity
     *
     * @return mixed
     */
    public function getFee()
    {
        return $this->getFees();
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getBatchFundTransferId()
    {
        return $this->getAttribute(self::BATCH_FUND_TRANSFER_ID);
    }

    public function getRemarks()
    {
        return $this->getAttribute(self::REMARKS);
    }

    public function getRegisteredName()
    {
        return $this->getAttribute(self::REGISTERED_NAME);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getReturnUtr()
    {
        return $this->getAttribute(self::RETURN_UTR);
    }

    public function getInitiatedAt()
    {
        return $this->getAttribute(self::INITIATED_AT);
    }

    public function getTransferredAt()
    {
        return $this->getAttribute(self::TRANSFERRED_AT);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    public function getPendingAt()
    {
        return $this->getAttribute(self::PENDING_AT);
    }

    public function getReversedAt()
    {
        return $this->getAttribute(self::REVERSED_AT);
    }

    public function getFailedAt()
    {
        return $this->getAttribute(self::FAILED_AT);
    }

    public function getRejectedAt()
    {
        return $this->getAttribute(self::REJECTED_AT);
    }

    public function getQueuedAt()
    {
        return $this->getAttribute(self::QUEUED_AT);
    }

    public function getCancelledAt()
    {
        return $this->getAttribute(self::CANCELLED_AT);
    }

    public function getBatchSubmittedAt()
    {
        return $this->getAttribute(self::BATCH_SUBMITTED_AT);
    }

    public function getCreateRequestSubmittedAt()
    {
        return $this->getAttribute(self::CREATE_REQUEST_SUBMITTED_AT);
    }

    public function getOnHoldAt()
    {
        return $this->getAttribute(self::ON_HOLD_AT);
    }

    public function getScheduledAt()
    {
        return $this->getAttribute(self::SCHEDULED_AT);
    }

    public function getScheduledOn()
    {
        return $this->getAttribute(self::SCHEDULED_ON);
    }

    public function getStatusDetailsId()
    {
        return $this->getAttribute(self::STATUS_DETAILS_ID);
    }

    public function getStatusEnterTimeStamp(string $status)
    {
        switch ($status)
        {
            case Status::CREATED:
                $timestampKey = self::INITIATED_AT;
                break;

            case Status::INITIATED:
                $timestampKey = self::TRANSFERRED_AT;
                break;

            case Status::SCHEDULED:
                $timestampKey = self::SCHEDULED_ON;
                break;

            default:
                $timestampKey = $status . '_at';
        }

        return $this->getAttribute($timestampKey);
    }

    public function getWorkflowFeature()
    {
        return $this->getAttribute(self::WORKFLOW_FEATURE);
    }

    public function getRawAttribute($key)
    {
        return $this->attributes[$key];
    }

    public function getStatusUpdatedAt()
    {
        switch ($this->getStatus())
        {
            case Status::PROCESSED:
                return $this->getProcessedAt();
            case Status::FAILED:
                return $this->getFailedAt();
            case Status::REVERSED:
                return $this->getReversedAt();
            case Status::REJECTED:
                return $this->getRejectedAt();
            case Status::CANCELLED:
                return $this->getCancelledAt();
        }
    }

    public function hasBeenQueued()
    {
        return ($this->isAttributeNotNull(self::QUEUED_AT) === true);
    }

    public function hasBeenProcessed()
    {
        return ($this->isAttributeNotNull(self::PROCESSED_AT) === true);
    }

    public function isStatusCreated(): bool
    {
        return ($this->getStatus() === Status::CREATED);
    }

    public function isStatusProcessed(): bool
    {
        return ($this->getStatus() === Status::PROCESSED);
    }

    public function isStatusReversed()
    {
        return ($this->getStatus() === Status::REVERSED);
    }

    public function isStatusOnHold()
    {
        return ($this->getStatus() === Status::ON_HOLD);
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement either `isStatusFailed` or `isStatusReversedOrFailed`
     * function, to send out summary emails and stuff in bulkRecon.
     *
     * @return bool
     */
    public function isStatusReversedOrFailed()
    {
        return ($this->isStatusReversed() or $this->isStatusFailed());
    }

    public function isStatusQueued()
    {
        return ($this->getStatus() === Status::QUEUED);
    }

    public function isStatusBatchSubmitted()
    {
        return ($this->getStatus() === Status::BATCH_SUBMITTED);
    }

    public function isStatusCreateRequestSubmitted()
    {
        return ($this->getStatus() === Status::CREATE_REQUEST_SUBMITTED);
    }

    public function isStatusScheduled()
    {
        return ($this->getStatus() === Status::SCHEDULED);
    }

    public function isStatusCancelled()
    {
        return ($this->getStatus() === Status::CANCELLED);
    }

    public function isStatusBeforeCreate()
    {
        return ($this->ledgerResponseAwaitedFlag ||
            (in_array($this->getStatus(), Status::$preCreateStatuses, true) === true));
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement either `isStatusFailed` or `isStatusReversedOrFailed`
     * function, to send out summary emails and stuff in bulkRecon.
     *
     * @return bool
     */
    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function isStatusProcessedOrReversed(): bool
    {
        return ($this->isStatusProcessed() or $this->isStatusReversed());
    }

    public function isStatusInitiated()
    {
        return ($this->getStatus() === Status::INITIATED);
    }

    public function isStatusPending()
    {
        return ($this->getStatus() === Status::PENDING);
    }

    public function isPendingReconciliation()
    {
        return $this->isStatusInitiated();
    }

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getQueuedReason()
    {
        return $this->getAttribute(self::QUEUED_REASON);
    }

    public function getDestinationId()
    {
        return $this->getAttribute(self::DESTINATION_ID);
    }

    public function getDestinationType()
    {
        return $this->getAttribute(self::DESTINATION_TYPE);
    }

    public function getPayoutType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function hasPayment()
    {
        return ($this->isAttributeNotNull(self::PAYMENT_ID) === true);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getFTSTransferId()
    {
        return $this->getAttribute(self::FTS_TRANSFER_ID);
    }

    public function getPricingRuleId()
    {
        return $this->getAttribute(self::PRICING_RULE_ID);
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID) === true);
    }

    public function getIdempotencyKey()
    {
        return $this->getAttribute(self::IDEMPOTENCY_KEY);
    }

    public function isComposite()
    {
        return ($this->composite === true);
    }

    public function getPricingFeatures()
    {
        return [];
    }

    public function getSourceFtsFundAccountId($isSubAccountPayout = false)
    {
        $payoutBalance = $this->balance;

        $channel = $this->balance->getChannel();

        $app = App::getFacadeRoot();

        if (($isSubAccountPayout === true) and
            ($payoutBalance->isAccountTypeShared() === true))
        {
            $directBalance = (new SubVaCore())->getDirectBalanceOfMasterMerchantForSubAccountPayout($this->balance->getAccountNumber(), $this->getMerchantId());

            $this->balance()->associate($directBalance);

            $channel = $this->balance->getChannel();
        }

        $bankingAccount = $this->balance->bankingAccount;

        $ftsFundAccountId = optional($bankingAccount)->getFtsFundAccountId();

        if (empty($ftsFundAccountId) === true and
            ($this->isBalanceAccountTypeDirect() === true) and
            (in_array($channel, BankingAccount\Core::$directChannelsForConnectBanking) === true))
        {
            $accountNumber = $this->balance->getAccountNumber();

            $merchantId    = $this->balance->getMerchantId();

            $ftsFundAccountId = $app['banking_account_service']->fetchFtsFundAccountIdFromBas($merchantId, $channel, $accountNumber);
        }

        //Need to set the balance back to the shared balance as we do not want fee recovery entity being created for this payout
        if ($isSubAccountPayout === true)
        {
            $this->balance()->associate($payoutBalance);
        }

        return $ftsFundAccountId;
    }

    public function getFeeType()
    {
        return $this->getAttribute(self::FEE_TYPE);
    }

    public function getExpectedFeeType()
    {
        return $this->expectedFeeType;
    }

    public function getOrigin()
    {
        return $this->getAttribute(self::ORIGIN);
    }

    public function getSourceDetails()
    {
        return $this->getAttribute(self::SOURCE_DETAILS);
    }

    public function getInputSourceDetails()
    {
        return $this->inputSourceDetails;
    }

    public function getStatusCode()
    {
        return $this->getAttribute(self::STATUS_CODE);
    }

    public function getQueuePayoutCreateRequest()
    {
        return $this->queuePayoutCreateRequest;
    }

    public function getIsCreditTransferBasedPayout()
    {
        return $this->isCreditTransferBasedPayout;
    }

    public function getCancellationUserId()
    {
        return $this->getAttribute(self::CANCELLATION_USER_ID);
    }

    public function getMasterBalance()
    {
        return $this->masterBalance;
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setledgerResponseAwaitedFlag(bool $flag)
    {
        $this->ledgerResponseAwaitedFlag = $flag;
        return $this;
    }

    public function setIsPayoutService(int $isPayoutService = 0)
    {
        $this->setAttribute(self::IS_PAYOUT_SERVICE, $isPayoutService);
    }

    public function setQueueFlag($flag)
    {
        $this->queueFlag = $flag;
    }

    public function setIsCreditTransferBasedPayout($isCt)
    {
        $this->isCreditTransferBasedPayout = $isCt;
    }

    public function setSyncFtsFundTransferFlag($flag)
    {
        $this->syncFtsFundTransfer = $flag;
    }

    public function setSavePayoutServicePayoutFlag(bool $value)
    {
        $this->savePayoutServicePayout = $value;
    }

    public function setFta (FundTransfer\Attempt\Entity $fta)
    {
        $this->fta = $fta;
    }

    public function setPayoutLinkId($payoutlinkid)
    {
        $this->setAttribute(self::PAYOUT_LINK_ID, str_replace("poutlk_", "", $payoutlinkid));
    }

    public function setShouldValidateAndUpdateBalancesFlag($flag)
    {
        $this->shouldValidateAndUpdateBalancesFlag = $flag;
    }

    public function setBalancePreDeductedFlag($flag)
    {
        $this->balancePreDeducted = $flag;
    }

    public function setTransactionIdWhenBalancePreDeducted(string $id)
    {
        $this->transactionIdWhenBalancePreDeducted = $id;
    }

    public function setClosingBalanceWhenBalancePreDeducted($balance)
    {
        $this->closingBalanceWhenBalancePreDeducted = $balance;
    }

    public function setTransactionCreatedAtWhenBalancePreDeducted(int $timestamp)
    {
        $this->transactionCreatedAtWhenBalancePreDeducted = $timestamp;
    }

    public function setMasterBalance(Balance\Entity $masterBalance)
    {
        $this->masterBalance = $masterBalance;
    }

    public function setChannel($channel)
    {
        Channel::validate($channel);

        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function setCurrency($currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setTax($tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setFees($fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function setMethod($method)
    {
        $this->setAttribute(self::METHOD, $method);
    }

    public function setNarration($narration)
    {
        $this->setAttribute(self::NARRATION, $narration);
    }

    public function setMode($mode)
    {
        if ($mode !== null)
        {
            FundTransfer\Mode::validateMode($mode);
        }

        $this->setAttribute(self::MODE, $mode);
    }

    public function setStatus($status)
    {
        Status::validate($status);

        $currentStatus = $this->getStatus();

        $shouldUnsetExpectedFeeType =
            (new CounterHelper)->decreaseFreePayoutsConsumedIfApplicable($this, CounterHelper::SET_STATUS);

        if ($shouldUnsetExpectedFeeType === true)
        {
            $this->setExpectedFeeType(null);
        }

        //
        // In code, we could call it multiple times for the same status update.
        // We do not want to update the status timestamp with the new value
        // and hence return it back from here itself if we are updating with same status.
        //
        if ($currentStatus === $status)
        {
            return;
        }

        // We need to create a fee_recovery entity for every payout when it goes from created to initiated state.
        // Keeping this code here because this status change is allowed only once and there is no chance of this
        // getting triggered twice
        if (($currentStatus === Status::CREATED) and
            ($status === Status::INITIATED) and
            ($this->isBalanceAccountTypeDirect() === true) and
            ($this->getFeeType() !== Transaction\CreditType::REWARD_FEE) and
            ($this->getIsPayoutService() === false))
        {
            (new FeeRecovery\Core)->createFeeRecoveryEntityForSource($this);
        }

        $this->setAttribute(self::STATUS, $status);

        if ($this->getIsPayoutService() === true)
        {
            return;
        }

        // pushing a message in the queue to update the source for payout
        $mode = app('rzp.mode') ? app('rzp.mode') : Mode::LIVE;

        SourceUpdater::dispatchToQueue($mode, $this, $currentStatus, $status);
    }

    public function setInitiatedAt()
    {
        $currentTime = Carbon::now()->getTimestamp();

        $this->setAttribute(self::INITIATED_AT, $currentTime);
    }

    public function setScheduledAt($scheduledAt)
    {
        $this->setAttribute(self::SCHEDULED_AT, $scheduledAt);
    }

    public function setRegisteredName(string $registeredName = null)
    {
        $this->setAttribute(self::REGISTERED_NAME, $registeredName);
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement `setUtr`
     * function, to set the utr.
     *
     * @param string|null $utr
     */
    public function setUtr(string $utr = null)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setQueuedReason($queuedReason)
    {
        $this->setAttribute(self::QUEUED_REASON, $queuedReason);
    }

    // TODO: check how to handle this
    // JIRA: https://razorpay.atlassian.net/browse/RX-696
    public function setReturnUtr(string $returnUtr = null)
    {
        $this->setAttribute(self::RETURN_UTR, $returnUtr);
    }

    public function setFailureReason($reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement `setRemarks`
     * function, to set the bank remarks.
     *
     * Now also being used by payouts to store comments when a user cancels a scheduled or queued payout
     *
     * @param string|null $remarks
     */
    public function setRemarks(string $remarks = null)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function setProcessedAt($date)
    {
        $this->setAttribute(self::PROCESSED_AT, $date);
    }

    public function setCreateRequestSubmittedAt($date)
    {
        $this->setAttribute(self::CREATE_REQUEST_SUBMITTED_AT, $date);
    }

    public function setOnHoldAt($date)
    {
        $this->setAttribute(self::ON_HOLD_AT, $date);
    }

    public function setPendingAt($date)
    {
        $this->setAttribute(self::PENDING_AT, $date);
    }

    public function setReversedAt($date)
    {
        $this->setAttribute(self::REVERSED_AT, $date);
    }

    public function setFailedAt($date)
    {
        $this->setAttribute(self::FAILED_AT, $date);
    }

    public function setRejectedAt(int $date = null)
    {
        $this->setAttribute(self::REJECTED_AT, $date);
    }

    public function setQueuedAt($date)
    {
        $this->setAttribute(self::QUEUED_AT, $date);
    }

    public function setCancelledAt($date)
    {
        $this->setAttribute(self::CANCELLED_AT, $date);
    }

    public function setScheduledOn($date)
    {
        $this->setAttribute(self::SCHEDULED_ON, $date);
    }

    public function setPurpose(string $purpose)
    {
        $this->setAttribute(self::PURPOSE, $purpose);
    }

    public function setPurposeType(string $purposeType)
    {
        $this->setAttribute(self::PURPOSE_TYPE, $purposeType);
    }

    public function setSettledOn($date)
    {
        $this->setAttribute(self::SETTLED_ON, $date);
    }

    public function setComposite(bool $composite)
    {
        $this->composite = $composite;

        return $this;
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setFTSTransferId($ftsTransferId)
    {
        $this->setAttribute(self::FTS_TRANSFER_ID, $ftsTransferId);
    }

    public function setPricingRuleId($pricingRuleId)
    {
        $this->setAttribute(self::PRICING_RULE_ID, $pricingRuleId);
    }

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }

    public function setBatchId(string $batchId)
    {
        $this->setAttribute(self::BATCH_ID,$batchId);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setFeeType($feeType)
    {
        $this->setAttribute(self::FEE_TYPE, $feeType);
    }

    public function setExpectedFeeType($expectedFeeType)
    {
        $this->expectedFeeType = $expectedFeeType;

        return $this;
    }

    public function setInputSourceDetails($inputSourceDetails)
    {
        $this->inputSourceDetails = $inputSourceDetails;

        return $this;
    }

    public function setWorkflowFeature($workflowFeature)
    {
        if ($workflowFeature !== null)
        {
            $tinyIntForFeature = WorkflowFeature::getIntValueFromWorkflowFeature($workflowFeature);

            $this->setAttribute(self::WORKFLOW_FEATURE, $tinyIntForFeature);
        }
    }

    public function setOrigin($origin)
    {
        $this->setAttribute(self::ORIGIN, $origin);
    }

    public function setStatusCode($statusCode)
    {
        $this->setAttribute(self::STATUS_CODE, $statusCode);
    }

    public function setQueuePayoutCreateRequest($queuePayoutCreateRequest)
    {
        $this->queuePayoutCreateRequest = $queuePayoutCreateRequest;

        return $this;
    }

    public function setCancellationUserId($cancellationUserId)
    {
        $this->setAttribute(self::CANCELLATION_USER_ID, $cancellationUserId);
    }

    public function setId($id)
    {
        $this->setAttribute(self::ID, $id);
    }

    public function setIdempotencyKey($idempotencyKey)
    {
        $this->setAttribute(self::IDEMPOTENCY_KEY, $idempotencyKey);
    }

    public function setTransactionId($txnId)
    {
        return $this->setAttribute(self::TRANSACTION_ID, $txnId);
    }

    public function setMerchantId($merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setBalanceId($balanceId)
    {
        return $this->setAttribute(self::BALANCE_ID, $balanceId);
    }

    public function setFundAccountId($fundAccountId)
    {
        return $this->setAttribute(self::FUND_ACCOUNT_ID, $fundAccountId);
    }

    public function setReferenceId($referenceId)
    {
        $this->setAttribute(self::REFERENCE_ID, $referenceId);
    }

    public function setUserId($userId)
    {
        $this->setAttribute(self::USER_ID, $userId);
    }

    public function setStatusDetailsId($id)
    {
        $this->setAttribute(self::STATUS_DETAILS_ID, $id);
    }

    public function setRawAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;
    }

    // ============================= END SETTERS =============================

    // ============================= MUTATORS =============================

    protected function setStatusAttribute($status)
    {
        $previousStatus = $this->getStatus();

        $this->attributes[self::STATUS] = $status;

        if (in_array($status, Status::$timestampedStatuses, true) === true)
        {
            $timestampKey = $status . '_at';

            //
            // In case of queued, the payout moves from queued -> created.
            // created_at is set when payout entity is created.
            // But we want to know when payout moves to `created` state.
            // We keep a track of this using `initiated_at`.
            //
            if ($status === Status::CREATED)
            {
                $timestampKey = self::INITIATED_AT;
            }

            // When payout request is sent to FTS the status is initiated
            //  We keep a track of this using 'transferred_at'

            if ($status === Status::INITIATED)
            {
                $timestampKey = self::TRANSFERRED_AT;
            }

            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }

        if (in_array($status, Status::$timestampedStatuses2, true) === true)
        {
            $timestampKey = $status . '_on';

            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }

        Metric::pushStatusChangeMetrics($this, $previousStatus);
    }

    public function setOriginAttribute($origin)
    {
        $origin = self::ORIGIN_SERIALIZER[$origin];

        $this->attributes[self::ORIGIN] = $origin;
    }

    // ============================= END MUTATORS =============================

    // ============================= ACCESSORS =============================

    public function getLedgerResponseAwaitedFlag()
    {
        return $this->ledgerResponseAwaitedFlag;
    }

    protected function getSettledOnAttribute()
    {
        $timestamp = $this->attributes[self::SETTLED_ON];

        if ($timestamp !== null)
        {
            return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
        }

        return null;
    }

    public function getInternalStatusAttribute()
    {
        return $this->getStatus();
    }

    public function getOriginAttribute()
    {
        $origin = $this->attributes[self::ORIGIN];

        return self::ORIGIN_DESERIALIZER[$origin];
    }

    public function getSourceDetailsAttribute()
    {
        $visibleKeys = [
            PayoutSource\Entity::SOURCE_ID,
            PayoutSource\Entity::SOURCE_TYPE,
            PayoutSource\Entity::PRIORITY,
        ];

        $sourceDetails = $this->payoutSources()->select($visibleKeys)
                              ->orderBy(PayoutSource\Entity::PRIORITY)
                              ->get();

        if ($this->getIsPayoutService() === true)
        {
            $details = (new PayoutSource\Repository())->getPayoutServiceSources(
                $this->getId(),
                $visibleKeys,
                PayoutSource\Entity::PRIORITY);

            $sourceDetails = new PublicCollection();

            foreach ($details as $key => $value)
            {
                $payoutSource = new PayoutSource\Entity;

                $payoutSource->setSourceType($value->source_type);
                $payoutSource->setSourceId($value->source_id);
                $payoutSource->setPriority($value->priority);

                $sourceDetails->push($payoutSource);
            }
        }


        return $sourceDetails;
    }

    public function getPayoutMeta()
    {
        $meta = array();

        $appId = $this->payoutMeta()->pluck(PayoutMetaEntity::APPLICATION_ID)->toArray();

        if (empty($appId) === false)
        {
            $application = (new AppRepo())->findOrFail($appId)->first()->toArrayPublic();

            $allowedKeys = ['id', 'merchant_id', 'name'];

            $appInfo  = array_intersect_key($application, array_flip($allowedKeys));

            $meta[self::PARTNER_APPLICATION] = $appInfo;
        }

        /*
         * 1. $payoutDetailsKeysToFetch actual DB columns that we want to fetch
         * 2. $payoutDetails->toArrayPublic() will do data transformation via $publicSetters
         * i.e. prepare data in format "tds": {"category_id":<>, "amount":<>}, "attachments": [{}, {}], "subtotal_amount": 123
         * 3. now we pull the required key from toArrayPublic() using array intersection with $visibleKeys
         */
        $payoutDetailsKeysToFetch = [
            PayoutsDetailsEntity::TDS_CATEGORY_ID,
            PayoutsDetailsEntity::ADDITIONAL_INFO,
            PayoutsDetailsEntity::TAX_PAYMENT_ID,
        ];

        /** @var PayoutsDetailsEntity $payout */
        $payoutDetails = $this->payoutsDetails()->first($payoutDetailsKeysToFetch);

        if (empty($payoutDetails) === false)
        {
            $payoutDetailsArray = $payoutDetails->toArrayPublic();

            $visibleKeys = [
                PayoutsDetailsEntity::TDS,
                PayoutsDetailsEntity::ATTACHMENTS,
                PayoutsDetailsEntity::SUBTOTAL_AMOUNT,
                PayoutsDetailsEntity::TAX_PAYMENT_ID,
            ];

            $visiblePayoutDetails = array_intersect_key($payoutDetailsArray, array_flip($visibleKeys));

            $meta = array_merge($meta, $visiblePayoutDetails);
        }
        else
        {
            $defaultValues = [
                PayoutsDetailsEntity::TDS               => null,
                PayoutsDetailsEntity::ATTACHMENTS       => [],
                PayoutsDetailsEntity::SUBTOTAL_AMOUNT   => null,
                PayoutsDetailsEntity::TAX_PAYMENT_ID    => null,
            ];

            $meta = array_merge($meta, $defaultValues);
        }

        return $meta;
    }

    // ============================= END ACCESSORS =============================

    // ============================= PUBLIC SETTERS =============================

    public function setPublicPendingOnUserAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if (($basicAuth->isStrictPrivateAuth() === true) or
            ($basicAuth->isAdminAuth() === true))
        {
            unset($attributes[self::PENDING_ON_USER]);

            return;
        }

        // Workflows are not enabled on test mode for now
        if ((app('rzp.mode') === Mode::TEST) or
            ($basicAuth->getUser() === null) or
            ($this->merchant === null) or
            ($this->merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS) === false))
        {
            return;
        }

        /** @var RepositoryManager $repo */
        $repo = app('repo');

        $workflowViaWorkflowService = $repo->workflow_entity_map->isPresent(self::PAYOUT, $this->getId());

        // The pending on user field is handled differently in the workflow service.
        // We don't have the sufficient information to populate pending on user at this point.
        // Hence, returning.
        // If the API being called is payout list, this flag will be populated for all payouts in the response
        // by an aggregated query run outside after all setters
        // Else, this field will be populated by the workflow history attribute.
        if ($workflowViaWorkflowService === true)
        {
            return;
        }

        $user = $basicAuth->getUser();

        $userRoleId = [];

        try
        {
            // If the entity is a user(which implies the product is banking),
            // then the role id for that user for the merchant in context
            // will have to be fetched from the merchant_users table.
            // This is because the role_map table doesn't have any merchant context.
            $merchantId = $this->merchant->getId();

            $userRoleId = (new User\Core())->getUserRoleIdInMerchantForWorkflow($user->getId(), $merchantId);
        }
        catch (UserWorkflowNotApplicableException $exception)
        {
            // If user role is not a workflow role
        }

        $permissionId = $repo->permission
                             ->retrieveIdsByNamesAndOrg(Permission\Name::CREATE_PAYOUT, Org\Entity::RAZORPAY_ORG_ID)
                             ->first();

        $pendingActions = $repo->workflow_action
                               ->getPendingActionsOnRoleIds($user->getId(), $this, $permissionId, $userRoleId);

        $attributes[self::PENDING_ON_USER] = ($pendingActions->count() > 0);
    }

    public function setPublicWorkflowHistoryAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        // Workflows are not enabled on test mode for now
        if ((app('rzp.mode') === Mode::TEST) or
            (($basicAuth->isStrictPrivateAuth() === true) and
            ($basicAuth->isSlackApp() === false and $basicAuth->isAppleWatchApp() === false)))
        {
            unset($attributes[self::WORKFLOW_HISTORY]);

            return;
        }

        // Since this is a network call for Workflow Service, fetch data only once
        if (empty($attributes[self::WORKFLOW_HISTORY]) === true)
        {
            $attributes[self::WORKFLOW_HISTORY] = $this->getWorkflowHistoryData($attributes);
        }
    }

    public function setPublicBankingAccountIdAttribute(array & $attributes)
    {
        if (app('basicauth')->isProxyOrPrivilegeAuth() === false and
            (app('basicauth')->isSlackApp() === false and app('basicauth')->isAppleWatchApp() === false))
        {
            unset ($attributes[self::BANKING_ACCOUNT_ID]);

            return;
        }

        $attributes[self::BANKING_ACCOUNT_ID] = optional($this->bankingAccount)->getPublicId();

        //In case of CAs implemented in BAS (ICICI, Axis, Yesbank) banking_account_id is fetched from banking account service.
        //banking_account_id is cached for subsequent calls
        if (empty($attributes[self::BANKING_ACCOUNT_ID]) === true and
            $this->isBalanceAccountTypeDirect() === true and
            in_array($this->balance->getChannel(), BASChannel::getDirectTypeChannels()))
        {
            $attributes[self::BANKING_ACCOUNT_ID] = app('banking_account_service')->fetchBankingAccountId($attributes[self::BALANCE_ID]);
        }
    }

    public function setPublicQueueingDetailsAttribute(array &$attributes)
    {
        if ($this->merchant->isFeatureEnabled(Features::PAYOUTS_ON_HOLD) === true)
        {
            $queuedReason = null;

            $description = null;

            if (($this->isStatusOnHold()) or
                ($this->isStatusQueued())) {
                $queuedReason = $this->getQueuedReason();

                $description = ($queuedReason === null) ? null : $this->getDescriptionForQueuedReason($queuedReason);
            }

            $queueingDetailsArray =
                [
                    'reason' => $queuedReason,
                    'description' => $description,
                ];

            $attributes[self::QUEUEING_DETAILS] = $queueingDetailsArray;
        }
        else
        {
            unset($attributes[self::QUEUEING_DETAILS]);
        }
    }

    public function setPublicDestinationAttribute(array & $attributes)
    {
        $type = $this->getDestinationType();

        // Type (destination) will be null in case of Business Banking payouts.
        // We will be deprecating this soon, in favor of fund accounts.
        if ($type === null)
        {
            return;
        }

        $entity = Constants\Entity::getEntityClass($type);

        $id = $this->getDestinationId();

        $attributes[self::DESTINATION] = $entity::getSignedId($id);
    }

    public function setPublicCustomerIdAttribute(array & $attributes)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        //
        // customer_id is used only in the openwallet payout flow. We do not want
        // to expose this field in general
        //
        if ($customerId === null)
        {
            unset($attributes[self::CUSTOMER_ID]);

            return;
        }

        $attributes[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    public function setPublicUserIdAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isStrictPrivateAuth() === true and
            ($basicAuth->isSlackApp() === false and $basicAuth->isAppleWatchApp() === false))
        {
            unset($attributes[self::USER_ID]);
        }
    }

    public function setPublicFundAccountIdAttribute(array & $attributes)
    {
        $fundAccountId = $this->getAttribute(self::FUND_ACCOUNT_ID);

        $attributes[self::FUND_ACCOUNT_ID] = FundAccount\Entity::getSignedIdOrNull($fundAccountId);
    }

    public function setPublicBatchIdAttribute(array & $attributes)
    {
        $batchId = $this->getAttribute(self::BATCH_ID);

        $attributes[self::BATCH_ID] = Batch\Entity::getSignedIdOrNull($batchId);
    }

    public function setPublicFundAccountAttribute(array & $attributes)
    {
        //
        // We never want to expose fund_account on private.
        // The correct way to do this would be to not add it in $public array.
        // But, we want to expose it in proxy auth (via expands). Hence, we
        // cannot remove it from $public array.
        // It's possible that the fund_account is loaded in some flow. This check
        // ensures that it's always removed before sending out the response.
        //
        // Don't forget fund_account if a composite payout request is made through strictPrivateAuth as we need to
        // show fund_account in the response of composite payout.

        if ((app('basicauth')->isStrictPrivateAuth() === true) and
            !(($this->isComposite() === true) or
              app('basicauth')->isSlackApp() === true or
              app('basicauth')->isAppleWatchApp() === true or
              $this->merchant->isFeatureEnabled(Features::ENABLE_APPROVAL_VIA_OAUTH) === true)
        )
        {
            array_forget($attributes, self::FUND_ACCOUNT);

            return;
        }
    }

    public function setPublicReversalAttribute(array & $attributes)
    {
        //
        // We never want to expose reversal on private.
        // The correct way to do this would be to not add it in $public array.
        // But, we want to expose it in proxy auth (via expands). Hence, we
        // cannot remove it from $public array.
        // It's possible that the reversal is loaded in some flow. This check
        // ensures that it's always removed before sending out the response.
        //
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            array_forget($attributes, self::REVERSAL);

            return;
        }
    }

    public function setPublicStatusAttribute(array & $attributes)
    {
        $internalStatus = $this->getAttribute(self::STATUS);

        $externalStatus = Status::getPublicStatusFromInternalStatus($internalStatus);

        $attributes[self::STATUS] = $externalStatus;
    }

    public function setPublicRegisteredNameAttribute(array & $attributes)
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'];

        if ($this->merchant->isFeatureEnabled(Features::BENE_NAME_IN_PAYOUT) === true)
        {
            $attributes[self::REGISTERED_NAME] = $this->getRegisteredName();
        }
        else
        {
            unset($attributes[self::REGISTERED_NAME]);
        }
    }

    public function setPublicFailureReasonAttribute(array & $attributes)
    {
        if ($this->isStatusReversedOrFailed() === false)
        {
            $attributes[self::FAILURE_REASON] = null;
        }
    }

    public function setPublicTransactionIdAttribute(array & $attributes)
    {
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::TRANSACTION_ID]);

            return;
        }

        $attributes[self::TRANSACTION_ID] = Transaction\Entity::getSignedIdOrNull($this->getTransactionId());
    }

    public function setPublicTransactionAttribute(array & $attributes)
    {
        //
        // We never want to expose transactions on private.
        // The correct way to do this would be to not add it in $public array.
        // But, we want to expose it in proxy auth (via expands). Hence, we
        // cannot remove it from $public array.
        // It's possible that the transactions is loaded in some flow. This check
        // ensures that it's always removed before sending out the response.
        //
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            array_forget($attributes, self::TRANSACTION);

            return;
        }

        $transaction = array_pull($attributes, self::TRANSACTION);

        //
        // We don't want to expose customer_transactions as of now.
        //
        if ((empty($transaction) === false) and
            (($this->transaction instanceof Transaction\Entity)))
        {
            $attributes[self::TRANSACTION] = $this->transaction->toStatement()->toArrayPublic();
        }
    }

    public function setPublicInitiatedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::INITIATED_AT]);
        }
    }

    public function setPublicScheduledOnAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::SCHEDULED_ON]);
        }
    }

    public function setPublicQueuedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        $internalStatus = $this->getAttribute(self::STATUS);

        if($internalStatus === STATUS::ON_HOLD)
        {
            $attributes[self::QUEUED_AT] = $this->getAttribute(self::ON_HOLD_AT);
        }

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::QUEUED_AT]);
        }
    }

    public function setPublicOnHoldAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::ON_HOLD_AT]);
        }
    }

    public function setPublicCancelledAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::CANCELLED_AT]);
        }
    }

    public function setPublicProcessedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::PROCESSED_AT]);
        }
    }

    public function setPublicPendingAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //
        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::PENDING_AT]);
        }
    }

    public function setPublicReversedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::REVERSED_AT]);
        }
    }

    public function setPublicFailedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.

        //in rbl payouts the payouts go from failed->processed->reversed state so
        // on dashboard the merchant finds it confusing so removing the failed_At when reversed_At is set
        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false
            or (app('basicauth')->isProxyOrPrivilegeAuth() === true
                and isset($attributes[self::REVERSED_AT])))
        {
            unset($attributes[self::FAILED_AT]);
        }
    }

    public function setPublicRejectedAtAttribute(array & $attributes)
    {
        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::REJECTED_AT]);
        }
    }

    public function setPublicOriginAttribute(array & $attributes)
    {
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::ORIGIN]);
        }
    }

    public function setPublicSourceDetailsAttribute(array & $attributes)
    {
        if (app('basicauth')->isStrictPrivateAuth() === false)
        {
            $attributes[self::SOURCE_DETAILS] = $this->getSourceDetailsAttribute();
        }
    }

    public function setPublicMetaAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if (($basicAuth->isProxyAuth() === true)
            or ($basicAuth->isPayoutLinkApp() === true))
        {
            $attributes[self::META] = $this->getPayoutMeta();
        }
    }

    public function setPublicRemarksAttribute(array & $attributes)
    {
        if (app('basicauth')->isStrictPrivateAuth() === false)
        {
            $attributes[self::REMARKS] = $this->getRemarks();
        }
        else
        {
            unset($attributes[self::REMARKS]);
        }
    }

    public function setPublicCancellationUserIdAttribute(array & $attributes)
    {
        if (app('basicauth')->isStrictPrivateAuth() === false)
        {
            $attributes[self::CANCELLATION_USER_ID] = $this->getCancellationUserId();
        }
        else
        {
            unset($attributes[self::CANCELLATION_USER_ID]);
        }
    }

    public function setPublicCancellationUserAttribute(array & $attributes)
    {
        if (app('basicauth')->isStrictPrivateAuth() === false)
        {
            $cancellationUserId = $this->getCancellationUserId();

            $attributes[self::CANCELLATION_USER] = [];

            if (empty($cancellationUserId) === false)
            {
                /** @var User\Entity $cancellationUser */
                $cancellationUser = (new User\Repository)->findOrFail($cancellationUserId);

                $attributes[self::CANCELLATION_USER] = $cancellationUser->toArrayPublic();
            }
        }
    }

    public function setPublicStatusDetailsAttribute(array &$attributes)
    {
        if ($this->getStatusDetailsId() === null)
        {
            $statusDetailsArray = [
                'reason'      => null,
                'description' => null,
                'source'      => null,
            ];
        }
        else
        {
            $statusDetails = (new PayoutsStatusDetails\Repository())->fetchStatusDetailsFromStatusDetailsId($this->getStatusDetailsId());

            if ($statusDetails !== null)
            {
                $source = $this->getSourceForStatusDetails($statusDetails);

                $statusDetailsArray =
                    [
                        'reason'      => $statusDetails['reason'],
                        'description' => $statusDetails['description'],
                        'source'      => $source,
                    ];
            }
            else
            {
                $statusDetailsArray = [
                    'reason'      => null,
                    'description' => null,
                    'source'      => null,
                ];
            }
        }

        $attributes[self::STATUS_DETAILS] = $statusDetailsArray;
    }

    public function setPublicStatusSummaryAttribute(array &$attributes)
    {
       $statusSummary = null;

        if (app('basicauth')->isProxyAuth() === true)
        {
                $statusDetails = (new PayoutsStatusDetails\Repository())->fetchPayoutStatusDetailsLatest($this->getId());

                // if status details is not null , then only we will populate the status summary
                // object otherwise we will keep it as null
                if($statusDetails !== null)
                {
                    $source = $this->getSourceForStatusDetails($statusDetails);

                    $statusSummary [$statusDetails['status']] [] =
                        [
                            PayoutsStatusDetails\Entity::REASON         => $statusDetails['reason'],
                            PayoutsStatusDetails\Entity::DESCRIPTION    => $statusDetails['description'],
                            'timestamp'                                 => $statusDetails['created_at'],
                            'source'                                    => $source,
                        ];
                }

                $attributes[self::STATUS_SUMMARY] = $statusSummary;
        }

        else
        {
            unset($attributes[self::STATUS_SUMMARY]);
        }
    }

    public function setPublicInternalStatusAttribute(array & $attributes)
    {
        $isProxyOrPrivilegeAuth = app('basicauth')->isProxyOrPrivilegeAuth();

        $isAdminAuth = app('basicauth')->isAdminAuth();

        $isStatusPending = Status::getPublicStatusFromInternalStatus($this->getStatus()) == Status::PENDING;

        if ((empty($this->balance) === true) or (empty($this->merchant) === true))
        {
            $isMerchantEnabled = false;
        }
        else
        {
            $isMerchantEnabled = Core::checkIfMerchantIsAllowedForIciciDirectAccountPayoutWith2Fa(
                                    $this->balance, $this->merchant);
        }

        if ($isAdminAuth or ($isProxyOrPrivilegeAuth and $isStatusPending and $isMerchantEnabled))
        {
            $attributes[self::INTERNAL_STATUS] = $this->getStatus();
        }
        else
        {
            unset($attributes[self::INTERNAL_STATUS]);
        }
    }

    public function setPublicPendingReasonAttribute(array & $attributes)
    {
        $isProxyOrPrivilegeAuth = app('basicauth')->isProxyOrPrivilegeAuth();

        $isAdminAuth = app('basicauth')->isAdminAuth();

        $isStatusPending = $this->getStatus() == Status::PENDING;

        if ((empty($this->balance) === true) or (empty($this->merchant) === true))
        {
            $isMerchantEnabled = false;
        }
        else
        {
            $isMerchantEnabled = Core::checkIfMerchantIsAllowedForIciciDirectAccountPayoutWith2Fa(
                                    $this->balance, $this->merchant);
        }

        if ($isAdminAuth or ($isProxyOrPrivilegeAuth and $isStatusPending and $isMerchantEnabled))
        {
            $attributes[self::PENDING_REASON] =
                ErrorCodeMapping::$pendingReasonMapping[$this->getStatusCode()] ?? null;
        }
        else
        {
            unset($attributes[self::PENDING_REASON]);
        }
    }

    // ============================= END PUBLIC SETTERS =============================

    // ============================= MODIFIERS =============================

    protected function modifyNarration(& $input)
    {
        $narration = $input[self::NARRATION] ?? null;

        if (((empty($narration) === false) and
             (in_array($narration, self::IS_NULL, true) === false)) or
            ($this->merchant->isFeatureEnabled(Features::NULL_NARRATION_ALLOWED)))
        {
            if(in_array($narration, self::IS_NULL, true) === true)
            {
                $input[self::NARRATION] = null;
            }

            return;
        }

        $merchant = $this->merchant;

        $merchantBillingLabel = $merchant->getBillingLabel();

        // Remove all characters other than a-z, A-Z, 0-9 and space
        $formattedLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantBillingLabel);

        // If formattedLabel is non-empty, pick the first 30 chars, else fallback to 'Razorpay'
        $formattedLabel = ($formattedLabel ? $formattedLabel : 'Razorpay');

        $narration = $formattedLabel . ' Fund Transfer';

        $narration = str_limit($narration, 30, '');

        $input[self::NARRATION] = $narration;
    }

    // ============================= END MODIFIERS =============================

    public function shouldNotifyTxnViaSms(): bool
    {
        return false;
    }

    public function shouldNotifyTxnViaEmail(): bool
    {
        // Mail only for processed and reversed payouts
        // Ref: \RZP\Mail\Transaction\Payout::getSubject
        if ($this->isBalanceTypeBanking() === false)
        {
            return false;
        }

        if (Core::isHighTpsMerchant($this) === true)
        {
            return false;
        }

        return ($this->merchant->isFeatureEnabled(Features::SKIP_PAYOUT_EMAIL) === false) and
                (in_array($this->getStatus(), [Status::PROCESSED, Status::REVERSED], true) === true);
    }

    /**
     * check is new error feature is enabled for payout merchant and
     * gives payout error array using status code and payout status.
     */
    public function getErrorDetails()
    {
        if (($this->merchant === null) or
            ($this->merchant->isFeatureEnabled(Features::NEW_BANKING_ERROR) === false))
        {
            return null;
        }

        return new PayoutError($this);
    }

    /**
     * {@inheritDoc}
     */
    public function toArrayPublic()
    {
        /** @var Route $route */
        $route = app('api.route');

        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        $this->removeRecursiveRelation();

        $routeName = $route->getCurrentRouteName();

        if (($routeName === self::PAYOUT_FETCH_MULTIPLE) and
            ($basicAuth->isSlackApp() === false))
        {
            $this->publicSetters = $this->publicSettersListView;
        }

        $payoutArray = parent::toArrayPublic();

        $errorObj = $this->getErrorDetails();

        if (is_null($errorObj) === false)
        {
            $payoutArray[self::ERROR] = $errorObj->toPublicErrorResponse();
        }

        return $payoutArray;
    }

    public function toArrayPublicPayoutServiceWithNewBankingError()
    {
        $errorObj = $this[self::ERROR];

        $this->removeRecursiveRelation();

        $payoutArray = parent::toArrayPublic();

        $payoutArray[self::ERROR] = $errorObj;

        return $payoutArray;
    }

    /**
     * {@inheritDoc}
     */
    public function toArrayWebhook()
    {
        if ($this->merchant->isFeatureEnabled(Features::ENABLE_APPROVAL_VIA_OAUTH) === true)
        {
            $this->load('fundAccount.contact');
        }

        $filteredAttributes = parent::toArrayWebhook();

        // Add new fields in webhook for MFN only when the payout was created within a batch
        if (($this->merchant->isFeatureEnabled(Features::PAYOUTS_BATCH)) and
            ($this->merchant->isFeatureEnabled(Features::MFN)) and
            (empty($filteredAttributes[self::BATCH_ID]) === false))
        {
            return (new PayoutsBatch\Core())->fillOtherPayoutWebhooksWithDataRequiredForMfn($filteredAttributes, $this);
        }

        return $filteredAttributes;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        $this->removeRecursiveRelation();

        $payoutArray = parent::toArray();

        $errorObj = $this->getErrorDetails();

        if (is_null($errorObj) === false)
        {
            $payoutArray[self::ERROR] = $errorObj->toPublicErrorResponse();
        }

        return $payoutArray;
    }

    public function scopeStatus(BuilderEx $query, string $status)
    {
        $query->where(Entity::STATUS, $status);
    }

    /**
     * This removes the recursive relations caused by using the same entity to associate.
     * Relations' mind is blown when this happens.
     * This happens in POST /payouts. In that, we create a transaction and associate the
     * payout created to the newly created transaction and then associate this newly created
     * transaction to the same payout. Since here the payout has transaction loaded and
     * transaction has the same payout loaded, recursion is spawned.
     */
    protected function removeRecursiveRelation()
    {
        if ($this->hasRelation(Entity::TRANSACTION) === true)
        {
            $txn = $this->transaction;
            $relations = array_except($txn->getRelations(), Transaction\Entity::SOURCE);
            $txn->setRelations($relations);
        }
    }

    /**
     * Retrieves the workflow history from either the workflow service or the api workflow system.
     *
     * If the payout is present in the entity_map table, the history is picked
     * from the workflow service, otherwise the api workflow system.
     *
     * Optionally enriches the pending_on_user field
     *
     * @param array|null $attributes
     * @return array
     * @throws ServerErrorException
     */
    private function getWorkflowHistoryData(array &$attributes = null): array
    {
        /** @var RepositoryManager $repo */
        $repo = app('repo');

        $workflowViaWorkflowService = $repo->workflow_entity_map->isPresent(self::PAYOUT, $this->getId());

        if ($workflowViaWorkflowService === true)
        {
            $data = $this->getWorkflowDetailsFromWorkflowService();

            if (empty($attributes) === false)
            {
                $this->enrichPendingOnUser($data, $attributes);
            }

            return $data;
        }

        // TODO: Only get actions for the create_payout permission
        $workflowActions = $this->workflowActions()
                                ->with(['workflow', 'workflow.steps', 'workflow.steps.role'])
                                ->get();

        if ($workflowActions->count() > 1)
        {
            // Should not exist for the create_payout permission.
            // Trace for debug and fail
        }

        $workflowAction = $workflowActions->first();

        if ($workflowAction === null)
        {
            return [];
        }

        $workflowAction = $repo->workflow_action->getActionDetailsPublic($workflowAction->getId(), Org\Entity::RAZORPAY_ORG_ID);

        $workflowAction = $workflowAction->first()->toArray();

        $steps = $workflowAction['workflow']['steps'] ?? [];

        $data = [
            'current_level' => $workflowAction['current_level'],
            'steps'         => self::serializeWorkflowSteps($steps),
        ];

        return $data;
    }

    public function getDescriptionForQueuedReason(String $reason)
    {
        $description = QueuedReasons::QUEUED_REASONS_WITH_DESCRIPTION[$reason];

        if (($reason === QueuedReasons::BENE_BANK_DOWN) and
            (str_contains($description, '%')))
        {
            $ifsc= $this->fundAccount->account->getIfscCode();

            $beneBank= BaseIFSC::getBankName($ifsc);

            if($beneBank === null)
            {
                return null;
            }

            $description = str_replace('%', $beneBank, $description);
        }

        return $description;
    }

    /**
     * @return array|mixed
     * @throws ServerErrorException
     */
    public function getWorkflowDetailsFromWorkflowService()
    {
        /** @var RepositoryManager $repo */
        $repo = app('repo');

        $workflowEntityMap = $repo->workflow_entity_map->findByEntityIdAndEntityType(self::PAYOUT, $this->getId());

        $workflowId = optional($workflowEntityMap)->getWorkflowId();

        if (empty($workflowId) === true)
        {
            return [];
        }

        return (new Workflow\Service\Client)->getWorkflowById($workflowId);
    }

    public static function serializeWorkflowSteps(array $steps): array
    {
        $data = [];

        foreach ($steps as $step)
        {
            $level = $step['level'];

            $roleData = self::serializeWorkflowStepRoles($step);

            $step = array_only($step, ['id', 'level', 'op_type']);

            if (empty($data[$level - 1]) === true)
            {
                $data[$level - 1] = $step;
            }

            $totalReviewersForStep = $data[$level - 1]['total_reviewer_count'] ?? 0;

            $data[$level - 1]['total_reviewer_count'] = $totalReviewersForStep + $roleData['reviewer_count'];
            $data[$level - 1]['roles'][] = $roleData;
        }

        ksort($data);

        return $data;
    }

    public function shouldDelayInitiationForBatchPayout()
    {
        if ((empty($this->getBatchId()) === false) and
            (in_array($this->getMode(), self::BATCH_PAYOUTS_DELAYED_INITIATION_MODES, true) === true))
        {
            return true;
        }

        return false;
    }

    protected static function serializeWorkflowStepRoles(array $step): array
    {
        $stepRole = $step['role'];

        $role = [
            'id'             => $stepRole['id'],
            'name'           => $stepRole['name'],
            'reviewer_count' => $step['reviewer_count'],
        ];

        $checkersData = [];

        $checkers = $step['checkers'] ?? [];

        foreach ($checkers as $checker)
        {
            $userData = $checker['checker'] ?? [];

            if (empty($userData) === true)
            {
                continue;
            }

            $checkersData[] = [
                'id'           => $checker['id'],
                'user_id'      => $userData['id'],
                'name'         => $userData['name'] ?? '',
                'email'        => $userData['email'] ?? '',
                'approved'     => $checker['approved'],
                'user_comment' => $checker['user_comment'],
            ];
        }

        $role['checkers'] = $checkersData;

        return $role;
    }

    /**
     * Scheduled_for is what we show in emails and FE. This function is called by the email template and returns the
     * date in the format :
     *
     * 1 July 2020, 1pm - 2pm
     *
     */
    public function getFormattedScheduledFor()
    {
        $scheduledAt = $this->getScheduledAt();

        $dateAndTime = Carbon::createFromTimestamp($scheduledAt, Timezone::IST);

        // englishMonth is not supported
        $month = $dateAndTime->format('F');

        $year = $dateAndTime->year;

        $day = $dateAndTime->day;

        $hour = $dateAndTime->hour;

        $formattedScheduledTimeSlot = '';

        //
        // Carbon has a lot of format functions, but they only work for a point of time and not for a period,
        // hence having to do this manually.
        //
        if (($hour >= 1) and
            ($hour <= 10))
        {
            $formattedScheduledTimeSlot = sprintf('%sam - %sam', $hour, $hour+1);
        }
        else if ($hour == 11)
        {
            $formattedScheduledTimeSlot = sprintf('%sam - %spm', $hour, $hour+1);
        }
        else if ($hour == 12)
        {
            $formattedScheduledTimeSlot = sprintf('%spm - %spm', $hour, $hour-12);
        }
        else if (($hour >= 13) and
                 ($hour <= 22))
        {
            $formattedScheduledTimeSlot = sprintf('%spm - %spm', $hour-12, $hour-11);
        }
        else if ($hour == 23)
        {
            $formattedScheduledTimeSlot = sprintf('%spm - %sam', $hour-12, 12);
        }
        else if ($hour == 0)
        {
            $formattedScheduledTimeSlot = sprintf('%sam - %sam', 12, $hour+1);
        }

        return sprintf('%s %s %s, %s ', $day, $month, $year, $formattedScheduledTimeSlot);
    }

    private function enrichPendingOnUser(array $data, array &$attributes)
    {
        if ((empty($data) === false) and
            (isset($data[self::PENDING_ON_USER]) === true))
        {
            $attributes[self::PENDING_ON_USER] = $data[self::PENDING_ON_USER];
        }
    }

    public function provideBeneBankName()
    {
        $entity = $this->fundAccount->account->getEntity();

        if($entity === "bank_account")
        {
            $ifsc = $this->fundAccount->account->getIfscCode();

            $ifscCode = substr($ifsc,0,4);

            $beneBank = BaseIFSC::getBankName($ifscCode);
        }

        else
        {
            $beneBank = "beneficiary bank";
        }

        return $beneBank;

    }

    public function getSourceForStatusDetails(PayoutsStatusDetails\Entity $statusDetails)
    {
        $source = PayoutsStatusDetails\ReasonSourceMap::$statusDetailsReasonToSourceMap[$statusDetails['reason']] ?? null;

        // if source mapping is not present . then use the json file to get source .
        if($source === null)
        {
            $error        = new PayoutError($this);

            $errorDetails = $error->getErrorDetails();

            $source       = $errorDetails['source'] ?? null;
        }

        return $source;
    }

    // returns true for va to va payouts which are to be handled internally
    public function isVaToVaPayout()
    {
        return $this->getChannel() === Channel::RZPX;
    }

    /**
     * A payout is an inter account payout if
     * 1. the payout purpose = inter_account_payout meaning it was initiated for inter nodal transfer by FinOps.
     * 2. Or, the merchant associated with the payout must have the feature flag inter_account_test_payout enabled,
     *    and the beneficiary account_number/vpa must be present in the config maintained for whitelisted bene accounts.
     *    This means the payout was meant to be an inter account test payout for testing purposes.
     * Ref doc link : https://docs.google.com/document/d/1d2Lag8ox1TRaroKNdw2J0dHARb06scu9pXUGPE1vryE/edit#
     *
     * @return bool
     */
    public function isInterAccountPayout() : bool
    {
        // return true if payout is an inter account payout
        if ($this->getPurpose() === Purpose::INTER_ACCOUNT_PAYOUT)
        {
            return true;
        }

        // else, return false if merchant does not have the feature enabled
        if ($this->merchant->isFeatureEnabled(Features::INTER_ACCOUNT_TEST_PAYOUT) === false)
        {
            return false;
        }

        // else return true or false based on whether the bene mid is present in redis config or not
        list($beneBankName, $beneMerchantId) = (new \RZP\Models\Internal\Service())->getBeneBankNameAndMerchantIdIfBeneficiaryAccountIsWhitelistedForPayout($this);

        if ($beneMerchantId === null)
        {
            return false;
        }

        return true;
    }

    public function isSubAccountPayout() : bool
    {
        return ($this->getPayoutType() === self::SUB_ACCOUNT);
    }

    public function isVendorPayment() :bool
    {
        $sourceDetails = $this->payoutSources()
            ->where(PayoutSource\Entity::SOURCE_TYPE, PayoutSource\Entity::VENDOR_PAYMENTS)
            ->get();

        return $sourceDetails->isEmpty() === false;
    }

    public function reload()
    {
        if ($this->getIsPayoutService() === false)
        {
            return parent::reload();
        }

        $instance = (new Core)->getAPIModelPayoutFromPayoutService($this->getId());

        $this->attributes = $instance->attributes;

        $this->original = $instance->original;

        return $this;
    }

    public function setPayoutStatusAfterLedgerFailureAndDispatchEvent(string $errorCode = null, string $errorReason = null)
    {
        $app = App::getFacadeRoot();

        if ($errorCode === ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING)
        {
            // We are not going to send payout.reversed event for insufficient balance for now
            // TODO: waiting for product to come up with a clear way for this
            $isFailedWebhookEnabled = true;
        }
        else
        {
            $isFailedWebhookEnabled = (new Core())->checkIfPayoutFailedWebhookIsSubscribed($this->getMerchantId());
        }

        $status = $isFailedWebhookEnabled ? Status::FAILED : Status::REVERSED;

        $app['trace']->info(
            TraceCode::PAYOUT_FAILED_WEBHOOK_SUBSCRIPTION_STATUS,
            [
                'merchant_id'         => $this->getMerchantId(),
                'subscription_status' => $isFailedWebhookEnabled,
                'payout_id'           => $this->getId(),
            ]
        );

        if ($isFailedWebhookEnabled === false)
        {
            $reversal = (new Reversal\Core())->createReversalWithoutTransactionForLedgerServiceHandling($this);
        }

        if (empty($errorReason) === true)
        {
            $errorReason = 'Payout failed. Contact support for help.';
        }

        if (empty($errorCode) === true)
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYOUT_FAILED_UNKNOWN_ERROR;
        }

        $this->setStatus($status);
        $this->setFailureReason($errorReason);
        $this->setStatusCode($errorCode);
        (new PayoutsStatusDetails\Core())->create($this);

        $event = $isFailedWebhookEnabled ? 'api.payout.failed' : 'api.payout.reversed';
        $app->events->dispatch($event, [$this]);

        $app['trace']->info(
            TraceCode::PAYOUT_FAILED_IN_LEDGER_FLOW,
            [
                'payout_id'      => $this->getId(),
                'transaction_id' => $this->getTransactionId(),
                'payout_status'  => $this->getStatus(),
                'failure_reason' => $this->getFailureReason(),
            ]);
    }

    public function isVanillaPayout() :bool
    {
        // Vanilla payout will not have an corresponding entry in the payout_sources table
        $payoutSource = (new PayoutSourceCore())->getPayoutSource($this->getId());

        return $payoutSource == null;
    }
}
