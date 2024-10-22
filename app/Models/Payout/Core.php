<?php

namespace RZP\Models\Payout;

use App;
use Mail;
use Carbon\Carbon;

use Monolog\Logger;
use RZP\Exception;
use RZP\Constants;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\RuntimeException;
use RZP\Http\Route;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\FundAccount\Type;
use RZP\Trace\Tracer;
use RZP\Models\Admin;
use RZP\Models\State;
use DeepCopy\DeepCopy;
use RZP\Models\Contact;
use RZP\Diag\EventCode;
use RZP\Models\Counter;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Services\Mutex;
use RZP\Models\Settings;
use RZP\Models\Customer;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\External;
use RZP\Models\Workflow;
use RZP\Trace\TraceCode;
use RZP\Traits\TrimSpace;
use RZP\Models\Admin\Org;
use RZP\Http\OAuthScopes;
use RZP\Jobs\LedgerStatus;
use RZP\Constants\Product;
use RZP\Jobs\Transactions;
use RZP\Jobs\FundTransfer;
use RZP\Models\Settlement;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Jobs\QueuedPayouts;
use RZP\Models\Transaction;
use RZP\Models\FeeRecovery;
use RZP\Constants\Timezone;
use RZP\Constants\HyperTrace;
use RZP\Models\VirtualAccount;
use RZP\Models\CreditTransfer;
use RZP\Models\IdempotencyKey;
use RZP\Models\BankingAccount;
use RZP\Jobs\PayoutsAutoExpire;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\PayoutService;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Credits;
use RZP\Models\Admin\Permission;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Jobs\BatchPayoutsProcess;
use RZP\Models\Currency\Currency;
use RZP\Mail\PayoutLink\Approval;
use RZP\Exception\LogicException;
use RZP\Jobs\OnHoldPayoutsProcess;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\QueuedPayoutsInitiate;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Payout\Notifications;
use RZP\Jobs\PayoutServiceDualWrite;
use RZP\Models\Base\PublicCollection;
use RZP\Mail\Payout\PendingApprovals;
use RZP\Jobs\ScheduledPayoutsProcess;
use RZP\Models\Transaction\CreditType;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccountStatement;
use RZP\Services\BankingAccountService;
use RZP\Exception\ServerErrorException;
use RZP\Models\Workflow\Service\Adapter;
use RZP\Constants\Mode as ModeConstants;
use RZP\Models\PartnerBankHealth\Events;
use RZP\Jobs\PayoutServiceDataMigration;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Workflow\PayoutAmountRules;
use RZP\Models\Workflow\Service\EntityMap;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Constants\Entity as EntityConstant;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Jobs\PartnerBankDowntimeHoldPayouts;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\FundTransfer\Attempt\Initiator;
use RZP\Services\FTS\Constants as FTSConstants;
use RZP\Jobs\PayoutPostCreateProcessLowPriority;
use RZP\Jobs\BankingAccountStatementSourceLinking;
use RZP\Jobs\FreePayoutMigrationForPayoutsService;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Transaction\Core as TransactionCore;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Services\Pagination\Entity as PaginationEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;
use RZP\Models\BankingAccountStatement\Entity as BASEntity;
use RZP\Jobs\FundManagementPayouts\FundManagementPayoutCheck;
use RZP\Models\Payout\Batch\Constants as BatchPayoutConstants;
use RZP\Jobs\FundManagementPayouts\FundManagementPayoutInitiate;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;
use RZP\Models\Workflow\Service\Adapter\Constants as WorkflowConstants;
use RZP\Models\Payout\DataMigration\Processor as DataMigrationProcessor;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;
use RZP\PushNotifications\Payout\PendingApprovals as PendingApprovalsPN;
use RZP\Models\Workflow\Service\Config\Service as WorkflowConfigService;
use RZP\Models\PayoutsStatusDetails\Core as PayoutsStatusDetailsCore;
use RZP\Services\Mock\BankingAccountService as MockBankingAccountService;
use RZP\Models\Transaction\Processor\Ledger\Payout as PayoutsLedgerProcessor;

/**
 * Class Core
 *
 * IMPORTANT: None of the flows in payout should rely on Basic Auth's merchant
 * since payout creation can happen via admin route too (retry_payouts)
 * In this class, everything should be taken in the input only.
 *
 * @package RZP\Models\Payout
 */
class Core extends Base\Core
{
    use TrimSpace;

    const MUTEX_RESOURCE                    = 'PAYOUT_PROCESSING_%s_%s';

    const FREE_PAYOUT_MIGRATE_RESOURCE      = 'FREE_PAYOUT_MIGRATE_%s_%s_%s';

    const CUSTOMER_WALLET_MUTEX_RESOURCE    = 'CUSTOMER_WALLET_PAYOUT_%s_%s_%s';

    const MAX_PAYOUT_AMOUNT                 = 800000000; // 80 Lakhs

    const MUTEX_LOCK_TIMEOUT                = 300;

    const FREE_PAYOUT_MUTEX_LOCK_TIMEOUT    = 180;

    const PAYOUT_MUTEX_LOCK_TIMEOUT         = 180;

    const PAYOUT_FAILURE_MUTEX_LOCK_TIMEOUT = 600;

    const PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT = 3600;

    const PS_CA_PAYOUT_MUTEX_LOCK_TIMEOUT = 30;

    const FUND_MANAGEMENT_PAYOUTS_RETRIEVAL_THRESHOLD = 21600; // In Seconds

    const FUND_MANAGEMENT_PAYOUTS_GATEWAY_BALANCE_THRESHOLD = 1000000; // In Paisa

    const FAILURE_STATUSES_FOR_PAYOUT_TO_AMEX = [Attempt\Status::FAILED, Attempt\Status::REVERSED];

    const STATUSES_FOR_CARD_VAULT_TOKEN_DELETION = [Attempt\Status::PROCESSED, Attempt\Status::REVERSED, Attempt\Status::FAILED];

    const DEFAULT_SLA_FOR_ON_HOLD_PAYOUTS_IN_MINS = 15;

    const DEFAULT_SLA_FOR_PARTNER_BANK_ON_HOLD_PAYOUTS_IN_MINS = 60;

    const DEFAULT_FMP_THRESHOLD = 50000000; // In Paisa

    const DEFAULT_BENE_BANK_STATUS = 'resolved';

    const BENE_BANK_DOWNTIME_STARTED = 'started';

    const BENEFICIARY = 'BENEFICIARY';

    const TOKEN_NOT_FOUND = 'TOKEN_NOT_FOUND';

    const INVALID_TOKEN = 'INVALID_TOKEN';

    const EMAIL_COUNT_FOR_PENDING_PAYOUT_APPROVAL = 5;

    const MAX_RETRIES_FOR_VAULT_TOKEN_DELETION = 2;

    const NON_RETRYABLE_VAULT_TOKEN_DELETION_ERRORS = [
        self::TOKEN_NOT_FOUND,
        self::INVALID_TOKEN
    ];

    const BAS_LINKING_ASYNC_RETRY_DEFAULT_DELAY = 60;

    //constants for fund loading downtime detection test payouts
    const BANK                                       = 'bank';
    const STATUS                                     = 'status';
    const MODE                                       = 'mode';
    const MESSAGE                                    = 'message';
    const IS_DOWNTIME_DETECTED                       = 'is_downtime_detected';
    const SUCCESSFUL_YESB_TEST_PAYOUTS               = 'successful_YESB_test_payouts';
    const DELAYED_YESB_TEST_PAYOUTS                  = 'delayed_YESB_test_payouts';
    const UNSUCCESSFUL_YESB_TEST_PAYOUTS             = 'unsuccessful_YESB_test_payouts';
    const SUCCESSFUL_ICICI_TEST_PAYOUTS              = 'successful_ICICI_test_payouts';
    const DELAYED_ICICI_TEST_PAYOUTS                 = 'delayed_ICICI_test_payouts';
    const UNSUCCESSFUL_ICICI_TEST_PAYOUTS            = 'unsuccessful_ICICI_test_payouts';
    const NARRATION_ICICI                            = 'ICICI Test Payout';
    const NARRATION_YESB                             = 'YESB Test Payout';
    const UTR_FOR_DELAYED_AND_UNSUCCESSFUl_CASES     = 'UTR_for_delayed_and_unsuccessful_cases';
    const PAYEE_ACCOUNT_NUMBER                       = 3434957265741928;

    const USER_COMMENT_KEY_IN_SETTINGS_FOR_ICICI_2FA = 'user_comment_icici_2fa';

    const REDIS_KEY_PREFIX                     = 'ps_data_migration_';
    const MAX_ATTEMPTS_FOR_DATA_MIGRATION      = 10;
    const BUFFER_FOR_DATA_MIGRATION            = 10;
    const PS_DATA_MIGRATION_LIMIT              = 10;
    const MUTEX_LOCK_TIMEOUT_PS_DATA_MIGRATION = 180;

    const DEFAULT_PAYOUT_STUCK_DURATION  = 900;
    const DEFAULT_PAYOUT_STUCK_THRESHOLD = 1800;
    const DEFAULT_PAYOUT_AUTO_EXPIRE_THRESHOLD = 0;

    const PARTNER_BANK_HEALTH_REDIS_KEY = "partner_bank_health";

    const PAYOUT_SERVICE_TEMPORARY_METADATA_TABLE = 'payout_meta_temporary';

    const CA_FUND_MANAGEMENT_PAYOUT_BALANCE_CONFIG_REDIS_KEY = 'ca_fund_management_payout_balance_config';

    const ACCOUNT_TYPE_DIRECT = 'direct';

    const IS_DUPLICATE = "is_duplicate";

    const SCHEDULE_PAYOUT_POST_APPROVAL_VALUE_IN_MINUTES = 15;

    const MODE_WISE_ACTIVE_CHANNELS = "mode_wise_active_channels";

    const MERCHANT_CUSTOMIZED_PRIORITY_RULES = "merchant_customized_priority_rules";

    const MERCHANT_ROUTING_PRIORITIES = 'priorities';

    const DUAL_WRITE_META_NAME = 'dual_write';

    /**
     * @var Mutex
     */
    protected $mutex;

    /**
     * @var PayoutService\Status
     */
    protected $payoutStatusServiceClient;

    /**
     * @var PayoutService\Details
     */
    protected $payoutDetailsServiceClient;

    /**
     * @var PayoutService\CreditTransferPayoutUpdate
     */
    protected $creditTransferPayoutServiceUpdateClient;

    /**
     * @var PayoutService\Cancel
     */
    protected $payoutCancelServiceClient;

    /**
     * @var PayoutService\Schedule
     */
    protected $payoutScheduledServiceClient;

    /**
     * @var PayoutService\DashboardScheduleTimeSlots
     */
    protected $payoutGetTimeSlotsForDashboardServiceClient;

    /**
     * @var PayoutService\OnHoldBeneEvent
     */
    protected $payoutServiceBeneEventUpdateClient;

    /**
     * @var PayoutService\Retry
     */
    protected $payoutRetryServiceClient;

    /**
     * @var PayoutService\Redis
     */
    protected $payoutServiceRedisClient;

    /**
     * @var PayoutService\Get
     */
    protected $payoutGetApiServiceClient;

    /**
     * @var PayoutService\Fetch
     */
    protected $payoutServiceFetchClient;

    /**
     * @var PayoutService\QueuedInitiate
     */
    protected $payoutServiceQueuedInitiateClient;

    /** @var Workflow\Service\Client  */
    protected $workflowService;

    /** @var PayoutService\Workflow*/
    protected $payoutWorkflowServiceClient;

    /** @var PayoutService\DataConsistencyChecker */
    protected $payoutServiceDataConsistencyCheckerClient;

    /** @var PayoutService\UpdateAttachments */
    protected $payoutServiceUpdateAttachmentsClient;

    /** @var PayoutService\ProcessStuckPayouts */
    protected $payoutServiceProcessStuckPayouts;

    /** @var PayoutService\BankingAccountStatement */
    protected $payoutServiceBankingAccountStatementClient;

    /** @var TdsProcessor\Processor*/
    protected $tdsProcessor;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->payoutStatusServiceClient = $this->app[PayoutService\Status::PAYOUT_SERVICE_STATUS];

        $this->payoutWorkflowServiceClient = $this->app[PayoutService\Workflow::PAYOUT_SERVICE_WORKFLOW];

        $this->payoutDetailsServiceClient = $this->app[PayoutService\Details::PAYOUT_SERVICE_DETAIL];

        $this->creditTransferPayoutServiceUpdateClient = $this->app[PayoutService\CreditTransferPayoutUpdate::CREDIT_TRANSFER_PAYOUT_SERVICE_UPDATE];

        $this->payoutCancelServiceClient = $this->app[PayoutService\Cancel::PAYOUT_SERVICE_CANCEL];

        $this->payoutScheduledServiceClient = $this->app[PayoutService\Schedule::PAYOUT_SERVICE_SCHEDULE];

        $this->payoutGetTimeSlotsForDashboardServiceClient = $this->app[PayoutService\DashboardScheduleTimeSlots::PAYOUT_SERVICE_DASHBOARD_TIME_SLOTS];

        $this->payoutServiceDataConsistencyCheckerClient = $this->app[PayoutService\DataConsistencyChecker::PAYOUT_SERVICE_DATA_CONSISTENCY_CHECKER];

        $this->payoutServiceBeneEventUpdateClient = $this->app[PayoutService\OnHoldBeneEvent::PAYOUT_SERVICE_BENE_EVENT_UPDATE];

        $this->payoutRetryServiceClient = $this->app[PayoutService\Retry::PAYOUT_SERVICE_RETRY];

        $this->payoutServiceRedisClient = $this->app[PayoutService\Redis::PAYOUT_SERVICE_REDIS];

        $this->payoutGetApiServiceClient = $this->app[PayoutService\Get::PAYOUT_SERVICE_GET];

        $this->payoutServiceQueuedInitiateClient =
            $this->app[PayoutService\QueuedInitiate::PAYOUT_SERVICE_QUEUED_INITIATE];

        $this->payoutServiceFetchClient = $this->app[PayoutService\Fetch::PAYOUT_SERVICE_FETCH];

        $this->payoutServiceUpdateAttachmentsClient = $this->app[PayoutService\UpdateAttachments::PAYOUT_SERVICE_UPDATE_ATTACHMENTS];

        $this->payoutServiceProcessStuckPayouts =
            $this->app[PayoutService\ProcessStuckPayouts::PAYOUT_SERVICE_PROCESS_STUCK_PAYOUTS];

        $this->payoutServiceBankingAccountStatementClient = $this->app[PayoutService\BankingAccountStatement::PAYOUT_SERVICE_BANKING_ACCOUNT_STATEMENT];

        $this->workflowService = new Workflow\Service\Client;

        $this->tdsProcessor = new TdsProcessor\Processor;
    }

    /**
     * Here, onDemand is used to do payout calculation for
     * merchant with es_on_demand feature enabled
     *
     * SOURCE: Merchant PG balance
     * TO: Merchant linked bank account (destination_id)
     *
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return mixed|null
     * @throws Exception\BadRequestException
     */
    public function createPayoutToMerchant(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_INTERNAL_MERCHANT_CREATE_REQUEST,
            [
                'input' => $input,
            ]);

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $merchant->getId(), $this->mode);

        $payout = $this->mutex->acquireAndRelease(
            $mutexResource,
            function () use ($input, $merchant)
            {
                $amount = $this->getMerchantPayoutAmount($input, $merchant);

                $currency = $this->getCurrency($input);

                $onDemand = $this->getOnDemandStatus($input);

                $payoutInput = [
                    Entity::PURPOSE   => Purpose::PAYOUT,
                    Entity::AMOUNT    => $amount,
                    Entity::CURRENCY  => $currency,
                    Entity::TYPE      => $onDemand,
                ];

                return $this->getProcessor('merchant_payout')
                            ->setMerchant($merchant)
                            ->createPayout($payoutInput);
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);

        if ((isset($input[Entity::TYPE])) and
            ($input[Entity::TYPE] === Entity::ON_DEMAND))
        {
            $this->dispatchFtaInitiate($payout);
        }

        return $payout;
    }

    public function calculateEsOnDemandFees(array $input, Merchant\Entity $merchant): array
    {
        (new Validator)->validateInput(Validator::CALCULATE_ES_ON_DEMAND_FEES, $input);

        $payoutInput = [
            Entity::PURPOSE   => Purpose::PAYOUT, // this purpose should be mapped to FundTransfer\Attempt\Purpose::SETTLEMENT
            Entity::AMOUNT    => $input[Entity::AMOUNT],
            Entity::CURRENCY  => $input[Entity::CURRENCY],
            Entity::TYPE      => Entity::ON_DEMAND,
        ];

        return $this->getProcessor('merchant_payout')
                    ->setMerchant($merchant)
                    ->calculateFees($payoutInput);
    }

    /**
     * Payouts to a fund account
     *
     * SOURCE: Merchant Balance (PG/Banking)
     * TO: Fund Account (BankAccount/VPA/Card etc) (fund_account_id)
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param string|null     $batchId
     * @param bool            $isInternal
     *
     * @return Entity
     * @throws BadRequestException
     */
    public function createPayoutToFundAccount(array $input,
                                              Merchant\Entity $merchant,
                                              string $batchId = null,
                                              bool $isInternal = false,
                                              Balance\Entity $balance = null): Entity
    {


        $amountInfo = $this->getAmountInfoFromInput($input);

        $this->trace->info(
            TraceCode::PAYOUT_TO_FUND_ACCOUNT_CREATE_REQUEST,
            [
                'input'       => $input,
                'merchant_id' => $merchant->getId(),
                'amount_info' => $amountInfo
            ]);

        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($merchant)
                       ->setBatch($batchId)
                       ->setInternal($isInternal)
                       ->setPayoutBalance($input, $balance)
                       ->createPayout($input);

        $this->trace->info(
            TraceCode::PAYOUT_TO_FUND_ACCOUNT_CREATE_RESPONSE,
            [
                'payout_id' => $payout->getId(),
                'merchant_id' => $merchant->getId(),
                'amount_info' => $amountInfo
            ]);

        if ($payout->getIsPayoutService() === false)
        {
            $this->postCreationForPayouts($payout);
        }

        return $payout;
    }

    /**
     * Creates a payout entity and triggers an ICICI OTP creation request via FTS
     *
     * @param array $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     * @throws BadRequestException
     */
    public function createPayoutAndTriggerIciciOtp(array $input,
                                                   Merchant\Entity $merchant,
                                                   Balance\Entity $balance = null): Entity
    {
        $amountInfo = $this->getAmountInfoFromInput($input);

        $this->trace->info(
            TraceCode::PAYOUT_2FA_CREATE_REQUEST,
            [
                'input'       => $input,
                'merchant_id' => $merchant->getId(),
                'amount_info' => $amountInfo
            ]);

        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($merchant)
                       ->setPayoutBalance($input, $balance)
                       ->createPayoutEntityWithoutDownstreamProcessing($input);

        try
        {
            $this->triggerIciciOtpForPayoutViaFts($payout);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::FTS_OTP_CREATION_FAILED,
                [
                    'payout_id'   => $payout->getId(),
                    'merchant_id' => $merchant->getId(),
                ]);

            $this->trace->count(Metric::FTS_OTP_CREATION_FAILURES_COUNT);

            // Suppress the exception as we don't want to return an error if OTP creation fails.
            // The user will retry OTP creation using the send_otp route.
        }

        return $payout;
    }

    public function triggerIciciOtpForPayoutViaFts(Entity $payout): array
    {
        $ftsFundAccountId = (int) $payout->getSourceFtsFundAccountId();

        /** @var \RZP\Services\FTS\FundTransfer $transferService */
        $transferService = App::getFacadeRoot()['fts_fund_transfer'];

        $input = [
            Attempt\Entity::SOURCE_ID         => $payout->getId(),
            Attempt\Entity::SOURCE_TYPE       => Entity::PAYOUT,
            Attempt\Entity::SOURCE_ACCOUNT_ID => $ftsFundAccountId,
            Entity::MODE                      => $payout->getMode(),
            Entity::AMOUNT                    => $payout->getAmount()
        ];

        $this->trace->info(TraceCode::FTS_OTP_CREATION_REQUEST_PAYLOAD, $input);

        $transferService->setRequestTimeout(1000);

        $ftsResponse = $transferService->requestOtpCreate($input);

        return $ftsResponse;
    }

    public function getOnboardingTimeFromBasIfApplicable(Entity $payout)
    {
        try{

            $bankingAccount = $payout->bankingAccount;
            $onboardingTime = optional($bankingAccount)->getCreatedAt();

            // In case of Merchants Onboarded on BAS flow, bankingAcc needs to be retrived from BAS
            if ($bankingAccount === null)
            {
                $basResponse = $this->app['banking_account_service']->fetchBankingAccountByAccountNumberAndChannel(
                    $payout->balance->getMerchantId(),
                    $payout->balance->getAccountNumber(),
                    $payout->balance->getChannel());

                if ($basResponse !== null)
                {
                    $onboardingTime = intdiv($basResponse['created_at'], 1000); // CreatedAt is in Milliseconds in BAS
                }
            }

            return $onboardingTime;
        }
        catch (\Throwable $ex)
        {
            return null;
        }
    }

    protected function getAmountInfoFromInput(array $input)
    {
        $amountInfo = [];

        if (isset($input[Entity::AMOUNT]) === true)
        {
            $amount = $input[Entity::AMOUNT];

            $amountInfo[Entity::AMOUNT] = $amount;

            $amountInfo['type'] = gettype($amount);

            $amountInfo['float_round_off'] = round($amount, 14);

            $amountInfo['is_int'] = is_int($amount);
        }

        return $amountInfo;
    }

    /**
     * THIS FUNCTION IS MEANT ONLY FOR HIGH TPS EXTERNAL MERCHANTS.
     * NOT SUPPORTED: Workflow, Scheduled Payouts, Partner payouts, Payouts via Apps, Batch Payouts, Payout Microservice
     *
     * DO NOT!!!! I REPEAT, DO NOT ONBOARD ANY INTERNAL APPS ON THIS CODE.
     *
     * @param array              $input
     * @param Merchant\Entity    $merchant
     * @param FundAccount\Entity $fundAccount
     *
     * @param Balance\Entity     $balance
     * @param bool               $compositePayoutSaveOrFail
     *
     * @param array              $payoutMetadata
     *
     * @return Entity
     * @throws BadRequestException
     */
    public function createPayoutToFundAccountForCompositePayout(array $input,
                                                                Merchant\Entity $merchant,
                                                                FundAccount\Entity $fundAccount,
                                                                Merchant\Balance\Entity $balance,
                                                                bool $compositePayoutSaveOrFail = true,
                                                                array $payoutMetadata = []): Entity
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_COMPOSITE_PAYOUT_CREATE_REQUEST,
            [
                'input'             => $input,
                'save_or_fail_flag' => $compositePayoutSaveOrFail,
                'metadata'          => $payoutMetadata
            ]);

        if ($compositePayoutSaveOrFail === true)
        {
            // TODO: See if we can get balance from somewhere before and reuse here.
            $payout = $this->getProcessor('fund_account_payout')
                           ->setMerchant($merchant)
                           ->setFundAccount($fundAccount)
                // NOT SUPPORTED: Workflow, Scheduled Payouts, Partner payouts,
                // Payouts via Apps, Batch Payouts, Payout Microservice
                           ->createPayoutForCompositePayoutFlow($input, $balance, $payoutMetadata);
        }
        else
        {
            $payout = $this->getProcessor('fund_account_payout')
                           ->setMerchant($merchant)
                           ->setFundAccount($fundAccount)
                           ->createPayoutWithoutSaveForHighTpsCompositePayouts($input, $balance);
        }

        return $payout;
    }

    /**
     * IMPS payout from a customer wallet to a func account
     *
     * SOURCE: Customer Wallet Balance
     * TO: Fund Account (BankAccount/VPA/Card etc) (fund_account_id)
     *
     * @param Customer\Entity $customer
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function createPayoutFromCustomerWallet(
        array $input,
        Customer\Entity $customer,
        Merchant\Entity $merchant): Entity
    {
        $customerId = $customer->getId();

        $this->trace->info(
            TraceCode::PAYOUT_FROM_CUSTOMER_WALLET_CREATE_REQUEST,
            [
                'input'       => $input,
                'customer_id' => $customerId
            ]);

        $mutexResource = sprintf(
            self::CUSTOMER_WALLET_MUTEX_RESOURCE,
            $merchant->getId(),
            $customerId,
            $this->mode);

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $customer, $merchant)
            {
                return $this->getProcessor('customer_wallet_payout')
                            ->setSourceCustomer($customer)
                            ->setMerchant($merchant)
                            ->createPayout($input);
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);
    }

    /**
     * Makes a payout from merchant primary balance, but link the Payout to a payment ID
     *
     * SOURCE: Merchant Balance (PG/Banking) - Payment ID
     * TO: Fund Account (BankAccount/VPA/Card etc)
     *
     * @param Payment\Entity  $payment
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function createPayoutFromPayment(Payment\Entity $payment, array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_FOR_PAYMENT_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        // The mutex for this is handled in `createPayoutToFundAccount()`.
        (new Validator)->validatePaymentForPayout($input, $payment);

        $payout = $this->createPayoutToFundAccount($input, $merchant);

        $payout->payment()->associate($payment);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    public function retryReversedPayout(Entity $payout): Entity
    {
        // We can't just retry the existing payout (create another FTA and process it via that) since the
        // payout will be marked as reversed. A reverse transaction would also get created for the same.
        // Hence, we create a new payout and a new transaction and so on.

        $this->trace->info(
            TraceCode::PAYOUT_RETRY_REQUEST,
            [
                'payout' => $payout->toArray(),
            ]);

        $payout->getValidator()->validateRetryPayout();

        if ($payout->hasFundAccount() === true)
        {
            if ($payout->hasCustomer() === true)
            {
                $payoutInput = $this->getRetryPayoutInputForCustomerWallet($payout);

                return $this->createPayoutFromCustomerWallet($payoutInput, $payout->customer, $payout->merchant);
            }
            else
            {
                $payoutInput = $this->getRetryPayoutInputForFundAccount($payout);

                // Note that if the payout was created by batch,
                // the information is not percolated to the new payout.
                // This is because, this new payout was not created by the batch.
                return $this->createPayoutToFundAccount($payoutInput, $payout->merchant);
            }
        }
        else
        {
            $payoutInput = $this->getRetryPayoutInputForMerchant($payout);

            return $this->createPayoutToMerchant($payoutInput, $payout->merchant);
        }
    }

    public function updateTestPayoutStatus(Entity $payout, array $input)
    {
        if ($this->isTestMode() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_STATUS_UPDATE_ALLOWED_ONLY_IN_TEST_MODE,
                null,
                [
                    'payout_id'     => $payout->getId(),
                ]);
        }

        if ($payout->getStatus() === Status::CREATED)
        {
            // Move payout to initiated state
            // This has been done so that state machine is respected
            // Payouts can move to final status (i.e.. processed/reversed) from initiated state only
            $this->updateStatusAfterFtaInitiated($payout, new Attempt\Entity);
        }

        // Validate status
        Status::validateStatusUpdate($input[Entity::STATUS], $payout->getStatus());

        $input += [
            Attempt\Entity::UTR                => $payout->getId(),
            Attempt\Entity::SOURCE_ID          => $payout->getId(),
            Attempt\Entity::SOURCE_TYPE        => Constants\Entity::PAYOUT,
            // This is required because FTA has a required|int validator for fund_transfer_id
            Attempt\Entity::FUND_TRANSFER_ID   => -1,
        ];

        (new Attempt\Core())->updateFundTransfer($input);

        // Reloading the model here so that the payout has the updated status which was done via FTA
        // FTA fetches the payout from the db, therefore the instance of payout doesn't have updated status by default
        return $payout->refresh();
    }

    public function updateStatusAfterFtaRecon(Entity $payout, array $ftaData)
    {
        $ftaStatus = $ftaData[Attempt\Constants::FTA_STATUS];

        $status = Status::getPayoutStatusFromFtaStatus($payout, $ftaStatus);

        $this->updateStatusFromFailedToReversedIfDebitAndCreditFoundForCA($status, $payout);

        if ($this->repeatedFtsStatusUpdateForTerminalStatePayout($payout, $status) === true)
        {
            return;
        }

        $ftaFailureReason = $ftaData[Attempt\Constants::FAILURE_REASON] ?? null;
        $ftaBankStatusCode = $ftaData[Attempt\Entity::BANK_STATUS_CODE] ?? null;

        $ftsSourceAccountInformation = [
            Transaction\Processor\Ledger\Base::FTS_FUND_ACCOUNT_ID => $ftaData[Attempt\Entity::SOURCE_ACCOUNT_ID] ?? null,
            Transaction\Processor\Ledger\Base::FTS_ACCOUNT_TYPE    => $ftaData[Attempt\Entity::BANK_ACCOUNT_TYPE] ?? null
        ];

        $isIcici2FaFeatureEnabled = $payout->merchant->isFeatureEnabled(FeatureConstants::ICICI_2FA);

        if ($isIcici2FaFeatureEnabled === true)
        {
            $this->handleIciciCurrentAccount2FAPayoutStatusUpdate($payout, $status, $ftaBankStatusCode, $ftaData);
        }

        switch ($status)
        {
            case Status::PROCESSED:
                $oldStatus = $payout->getStatus();

                $this->handlePayoutProcessed($payout, null, $ftaStatus, $ftsSourceAccountInformation, true);

                if ($this->isHighTpsMerchantWithSubBalance($payout) === false)
                {
                    $this->processTdsForPayout($payout, $oldStatus);
                }

                break;

            case Status::REVERSED:
                $oldStatus = $payout->getStatus();
                if ($this->isHighTpsMerchantWithSubBalance($payout) === true)
                {
                    // this is only on shared account
                    $this->handlePayoutReversedForHighTpsMerchants($payout,
                                                                   $ftaFailureReason,
                                                                   $ftaBankStatusCode,
                                                                   null,
                                                                   $ftsSourceAccountInformation);

                    break;
                }

                $this->handlePayoutReversed($payout, $ftaFailureReason, $ftaBankStatusCode, null, $ftsSourceAccountInformation, $ftaStatus);
                $this->processTdsForPayout($payout, $oldStatus);
                break;

            case Status::FAILED:
                // Not handling this in ledger reverse shadow, as VA payouts don't get marked as FAILED.
                $this->handlePayoutFailed($payout,
                                          $ftaFailureReason,
                                          $ftaBankStatusCode,
                                          $ftaStatus,
                                          $ftsSourceAccountInformation);
                break;

            case Status::CREATED:
            case Status::INITIATED:
                break;

            default:
                $this->trace->warning(
                    TraceCode::UNKNOWN_FTA_STATUS_SENT_TO_PAYOUT,
                    $ftaData);
        }

        if ($payout->getIsPayoutService() === false)
        {
            $payout->reload();
        }

        if ((in_array($status, self::FAILURE_STATUSES_FOR_PAYOUT_TO_AMEX, true) === true) and
            ($payout->fundAccount->getAccountType() === FundAccount\Type::CARD) and
            ($payout->fundAccount->account->isAmex() === true))
        {

            /** @var Card\IIN\Entity $iin */
            $iin = $payout->fundAccount->account->iinRelation;

            $issuer = (empty($iin) === true) ? $payout->fundAccount->account->getIssuer() : $iin->getIssuer();

            $this->trace->info(
                TraceCode::PAYOUT_TO_AMEX_FAILURE,
                [
                    Entity::PAYOUT . '_' . Entity::ID => $payout->getId(),
                    Entity::STATUS                    => $payout->getStatus(),
                    Entity::FAILURE_REASON            => $payout->getFailureReason(),
                    Card\Entity::ISSUER               => ($issuer === null) ?
                                                          Attempt\Constants::DEFAULT_ISSUER : $issuer,
                ]
            );
        }

        if ($payout->getIsPayoutService() === false)
        {
            $this->deleteCardMetaDataAndVaultTokenForTerminalStatePayout($status, $payout);
        }
    }

    public function deleteCardMetaDataAndVaultTokenForTerminalStatePayout(string $status, $payout)
    {
        if ((in_array($status, self::STATUSES_FOR_CARD_VAULT_TOKEN_DELETION, true) === true) and
            ($payout->fundAccount->getAccountType() === FundAccount\Type::CARD))
        {
            $card = $payout->fundAccount->account;

            $isTokenised = ($card->isTokenPan() === true) ? true : $card->isNetworkTokenisedCard();

            // Check this only for non_saved_card_flow
            if ($isTokenised === false)
            {
                // Since vault token and card meta data are stored in vault service,
                // we make a call here to delete both of them as per RBI guidelines.
                $this->deleteCardMetaDataAndVaultToken($card->getVaultToken(), $payout, $card, $isTokenised);
            }
        }
    }

    public function deleteCardMetaDataAndVaultToken($vaultToken, $payout, $card, bool $isTokenised)
    {
        $traceData = [
            Entity::PAYOUT . '_' . Entity::ID => $payout->getId(),
            Entity::CARD . '_' . Entity::ID   => $card->getId(),
            Entity::STATUS                    => $payout->getStatus(),
            Card\Entity::ISSUER               => $card->getIssuer(),
            Card\Entity::VAULT_TOKEN          => $card->getVaultToken(),
            'is_tokenised'                    => $isTokenised,
        ];

        $attempts = 0;

        $maxRetries = self::MAX_RETRIES_FOR_VAULT_TOKEN_DELETION;

        while($attempts < $maxRetries)
        {
            try
            {
                $this->app['card.cardVault']->deleteToken($vaultToken);

                $this->trace->info(TraceCode::PAYOUT_TO_CARDS_VAULT_TOKEN_SUCCESSFULLY_DELETED, $traceData);

                break;
            }
            catch (\Exception $exception)
            {
                $response = [];

                if (method_exists($exception, 'getData') === true)
                {
                    $response = $exception->getData();
                }

                if ((isset($response['error']) === true) and
                    (in_array($response['error'], self::NON_RETRYABLE_VAULT_TOKEN_DELETION_ERRORS, true) === true))
                {
                    $this->trace->info(
                        TraceCode::PAYOUT_TO_CARDS_VAULT_TOKEN_DELETION_FAILED,
                        [
                            'retryable'        => false,
                            'card_vault_error' => $response['error'],
                        ] + $traceData
                    );

                    break;
                }
                else
                {
                    $this->trace->info(
                        TraceCode::PAYOUT_TO_CARDS_VAULT_TOKEN_DELETION_FAILED,
                        [
                            'retryable'           => true,
                            'attempts'            => $attempts,
                            'card_vault_response' => $response,
                        ] + $traceData
                    );

                    $attempts++;

                    if ($attempts >= $maxRetries)
                    {
                        $this->trace->info(
                            TraceCode::PAYOUT_TO_CARDS_VAULT_TOKEN_DELETION_FAILED_WITH_RETRIES_EXHAUSTED,
                            [
                                'attempts'            => $attempts,
                                'card_vault_response' => $response,
                            ] + $traceData
                        );

                        $this->trace->count(Metric::PAYOUT_TO_CARDS_VAULT_TOKEN_DELETION_RETRIES_EXHAUSTED);

                        Tracer::startSpanWithAttributes(HyperTrace::PAYOUT_TO_CARDS_VAULT_TOKEN_DELETION_RETRIES_EXHAUSTED);

                        break;
                    }

                    continue;
                }
            }
        }
    }

    public function handleIciciCurrentAccount2FAPayoutStatusUpdate(Entity & $payout, string $status, string $ftaBankStatusCode = null, array $ftaData)
    {
        $this->trace->info(
            TraceCode::HANDLE_ICICI_2FA_PAYOUT_WEBHOOK,
            [
                'payout_id'            => $payout->getId(),
                'payout_status'        => $payout->getStatus(),
                'status'               => $status,
                'fta_bank_status_code' => $ftaBankStatusCode
            ]);

        $processType =  (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::RX_ICICI_2FA_WEBHOOK_PROCESS_TYPE]);

        if ($processType === 1)
        {
            $this->repo->saveOrFail($payout);

            // Fetch payout from master as we want the payout state transition to be valid for above status webhooks
            $payout = $this->repo->payout->findOrFailOnMaster($payout->getId());

            $this->trace->info(
                TraceCode::PAYOUT_STATUS_AFTER_MASTER_FETCH,
                [
                    'payout_id'            => $payout->getId(),
                    'payout_status'        => $payout->getStatus(),
                ]);
        }
        else if ($processType === 2)
        {
            $payout = $this->repo->payout->findOrFailOnMaster($payout->getId());

            $this->trace->info(
                TraceCode::PAYOUT_STATUS_AFTER_MASTER_FETCH,
                [
                    'payout_id'            => $payout->getId(),
                    'payout_status'        => $payout->getStatus(),
                ]);
        }

        if (($payout->getStatus()===Status::PENDING_ON_OTP) and
            ($payout->balance->isAccountTypeDirect() === true) and
            ($payout->getChannel() === Settlement\Channel::ICICI))
        {
            switch ($status) {
                case Status::PROCESSED:
                case Status::REVERSED:
                case Status::FAILED:
                    $this->handleStatusChangeForIcici2FACurrentAccountPayout($payout, $ftaBankStatusCode);
                    break;

                case Status::CREATED:
                case Status::INITIATED:
                    $input = $this->app['request']->input();

                    if (empty($input[Attempt\Entity::BANK_STATUS_CODE]) === false)
                    {
                        // Handle initiated webhook only if bank status code is present
                        $this->handleInitiatedWebhookForIcici2FACurrentAccount($payout, $ftaBankStatusCode);
                    }
                    break;

                default:
                    $this->trace->warning(
                        TraceCode::UNKNOWN_FTA_STATUS_SENT_TO_PAYOUT,
                        $ftaData);
            }
        }
    }

    public function handleInitiatedWebhookForIcici2FACurrentAccount(Entity $payout, string $ftaBankStatusCode = null)
    {
        if (($ftaBankStatusCode === "INVALID_OTP") or
            ($ftaBankStatusCode === "EXPIRED_OTP"))
        {
            $payout->setStatus(Status::PENDING);

            $payout->setStatusCode($ftaBankStatusCode);

            $this->repo->saveOrFail($payout);

            return;
        }

        if (empty($ftaBankStatusCode) === false)
        {
            $this->handleStatusChangeForIcici2FACurrentAccountPayout($payout, $ftaBankStatusCode);
        }
    }

    public function handleStatusChangeForIcici2FACurrentAccountPayout(Entity & $payout, string $ftaBankStatusCode = null)
    {
        $balance = $payout->balance;

        $feeType = $this->updateFreePayoutsConsumedAndGetFeeType($balance);

        $payout->setExpectedFeeType($feeType);

        (new FundAccountPayout\Direct\Icici)->handleFeeAndTaxForIcici2FACurrentAccountPayout($payout);

        $payout->setStatus(Status::CREATED);

        $fta = $this->repo->fund_transfer_attempt
                          ->getFTSAttemptBySourceId($payout->getId(), $payout->getEntityName(), true);

        $this->updateStatusAfterFtaInitiated($payout, $fta);

        $payout->setStatusCode($ftaBankStatusCode);

        $this->repo->saveOrFail($payout);

        try
        {
            $this->processApproveActionOnPayoutViaWorkflowServiceForICICICAPayout($payout->getId(), $payout->merchant->getId());
        }
        catch (\Throwable $throwable)
        {
            $this->trace->traceException(
                $throwable,
                null,
                TraceCode::PAYOUT_2FA_ICICI_PROCESS_WORKFLOW_EXCEPTION,
                [
                    'payout_id'   => $payout->getId(),
                    'merchant_id' => $payout->merchant->getId()
                ]
            );
        }

        $this->deleteUserCommentInSettingsEntity($payout);
    }

    public function repeatedFtsStatusUpdateForTerminalStatePayout(Entity $payout, string $status)
    {
        if (($payout->getStatus() === $status) and
            (in_array($status, Status::$finalStates)))
            {
                return true;
            }

        return false;
    }

    public function updateStatusAfterFtaInitiated(Entity $payout, Attempt\Entity $fta)
    {
        if ($payout->getStatus() === Status::PENDING_ON_OTP)
        {
            // For ICICI CA 2FA flows, after we send a sync call to FTS, the payout moves to pending_on_otp
            // state. We should not update payout status to initiated for this case.

            $this->trace->info(
                TraceCode::PAYOUT_UPDATE_STATUS_AFTER_FTA_INITIATED_SKIPPED,
                [
                    'payout_id' => $payout->getId(),
                    'merchant_id' => $payout->getMerchantId()
                ]);

            return;
        }

        Status::validateStatusUpdate(Status::INITIATED, $payout->getStatus());

        if ($payout->getIsPayoutService() === true)
        {
            $this->updateStatusAfterFtaInitiatedForPayoutService($payout);
        }
        else
        {
            $payout->batchFundTransfer()->associate($fta->batchFundTransfer);

            $payout->setStatus(Status::INITIATED);

            $this->repo->saveOrFail($payout);
        }
    }

    public function updateWithDetailsBeforeFtaRecon(Entity $payout, array $ftaData = [])
    {
        $this->trace->info(
            TraceCode::PAYOUT_UPDATE_BEFORE_FTA_RECON,
            [
                'payout_id' => $payout->getId(),
            ]);

        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                      $payout->getMerchantId()) === true)
        {
            $this->mutex->acquireAndRelease(
                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                function() use ($payout, $ftaData) {
                    $payout->reload();

                    $this->updateWithDetailsBeforeFtaReconBase($payout, $ftaData);
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
        }
        else
        {
            $this->updateWithDetailsBeforeFtaReconBase($payout, $ftaData);
        }

        $this->trace->info(
            TraceCode::PAYOUT_UPDATED_AFTER_FTA_RECON,
            [
                'payout_id' => $payout->getId(),
            ]);
    }

    /**
     * @param Entity $payout
     * @param array  $ftaData
     *
     * @return void
     * @throws \Throwable
     */
    function updateWithDetailsBeforeFtaReconBase(Entity $payout, array $ftaData): void
    {
        $isPayoutService = $payout->getIsPayoutService();

        if ($isPayoutService === true)
        {
            $this->updateWithDetailsBeforeFtaReconForPayoutService($payout, $ftaData);

            $this->trace->info(
                TraceCode::PAYOUT_UPDATED_AFTER_FTA_RECON,
                [
                    'payout_id' => $payout->getId(),
                ]);

            return;
        }

        $initialUtr = $payout->getUtr();

        $this->repo->transaction(
            function() use ($payout, $ftaData, $initialUtr) {
                // For non-Yesbank, we will not get public_failure_reason
                $ftaFailureReason = $ftaData[Attempt\Constants::FAILURE_REASON] ?? null;

                $ftaBankStatusCode = $ftaData[Attempt\Entity::BANK_STATUS_CODE] ?? null;

                $initialChannel = $payout->getChannel();

                $updatedChannel = $ftaData[Attempt\Constants::CHANNEL] ?? null;

                $payout->setUtr($ftaData[Attempt\Constants::UTR]);

                $payout->setRemarks($ftaData[Attempt\Constants::REMARKS]);

                $registeredName = $ftaData[Attempt\Constants::BENEFICIARY_NAME] ?? null;

                $payout->setRegisteredName($registeredName);

                //
                // For VPA type, we always set it to UPI only
                // at build and we don't take the mode from FTA.
                //
                // Also, we don't want to override the payout's mode if it's already set.
                //
                if ((empty($ftaData[Attempt\Constants::VPA_ID]) === true) and
                    ($payout->getMode() === null))
                {
                    $payout->setMode($ftaData[Attempt\Constants::MODE]);
                }

                if (($updatedChannel !== null) and
                    ($initialChannel !== $updatedChannel))
                {
                    $this->checkOrUpdateChannelToPayoutAndTransaction($payout, $initialChannel, $updatedChannel, true);
                }

                //
                // We do not want to override the failure reason if it's already set.
                // It could have been set in the `afterRecon` flow. In some cases, it's
                // possible that `beforeRecon` gets called and then `afterRecon` gets
                // called and then again `beforeRecon`. In `afterRecon`, if the failure
                // reason gets set, we don't want to reset it to null in `beforeRecon` if
                // the failure reason is empty in the 2nd `beforeRecon` call.
                //
                if (empty($ftaFailureReason) === false)
                {
                    $payout->setFailureReason($ftaFailureReason);
                }

                //
                // same reason as of failure reason
                // Only set if status is Failed or reversed
                //
                if (empty($ftaBankStatusCode) === false)
                {
                    $ftaStatus = $ftaData[Attempt\Constants::FTA_STATUS] ?? null;

                    if ((is_null($ftaStatus) === false) and
                        (array_search($ftaStatus, [Status::FAILED, Status::REVERSED]) !== false))
                    {
                        $payout->setStatusCode($ftaBankStatusCode);
                    }
                }

                // we want to override return UTR only if there is no value for UTR before
                // since return_utr column has a unique constraint, so checking for empty
                // value.
                if (empty($payout->getReturnUtr()) === true)
                {
                    if (empty($ftaData[Entity::RETURN_UTR]) === false)
                    {
                        $returnUtr = $ftaData[Entity::RETURN_UTR];

                        $payout->setReturnUtr($returnUtr);
                    }
                }

                $this->repo->saveOrFail($payout);
            });

        $firePayoutUpdatedWebhook = false;
        $ftaStatus                = $ftaData[Attempt\Constants::FTA_STATUS] ?? null;

        // stores status details in case if unique status details update has come
        if (($ftaStatus === "initiated") and
            ($isPayoutService === false))
        {
            $statusDetails = $ftaData[Attempt\Entity::STATUS_DETAILS] ?? null;
            $lastId        = $payout->getStatusDetailsId();

            if (($statusDetails !== null) and
                (Status::isFinalState($payout->getStatus()) === false))
            {
                if ($lastId !== null)
                {
                    $lastStatusDetails = $this->repo->payouts_status_details->fetchStatusReasonFromStatusDetailsId($lastId);

                    // stores status details in case unique status details has come
                    if (($statusDetails[Attempt\Entity::REASON]) !== $lastStatusDetails['reason'])
                    {
                        (new PayoutsStatusDetailsCore())->createStatusDetailsProcessingState($payout, $ftaData);
                        $firePayoutUpdatedWebhook = true;
                    }
                }

                //stores status details in case last status details id is null
                else
                {
                    (new PayoutsStatusDetailsCore())->createStatusDetailsProcessingState($payout, $ftaData);
                    $firePayoutUpdatedWebhook = true;
                }
            }
        }
        elseif (($ftaStatus === "initiated") and
                ($isPayoutService === true))
        {
            $statusDetails = $ftaData[Attempt\Entity::STATUS_DETAILS] ?? null;
            $lastId        = $payout->getStatusDetailsId();

            if (($statusDetails !== null) and
                (Status::isFinalState($payout->getStatus()) === false))
            {
                if ($lastId !== null)
                {
                    $lastStatusDetails = $this->repo->payouts_status_details->fetchStatusReasonFromStatusDetailsId($lastId);

                    // stores status details in case unique status details has come
                    if (($statusDetails[Attempt\Entity::REASON]) !== $lastStatusDetails['reason'])
                    {
                        (new PayoutsStatusDetailsCore())->createStatusDetailsProcessingState($payout, $ftaData);
                    }
                }

                //stores status details in case last status details id is null
                else
                {
                    (new PayoutsStatusDetailsCore())->createStatusDetailsProcessingState($payout, $ftaData);
                }
            }
        }

        if (($initialUtr === null) and
            ($payout->getUtr() !== null))
        {
            $firePayoutUpdatedWebhook = true;
            if ($isPayoutService === true)
                $firePayoutUpdatedWebhook = false;
        }

        if ($firePayoutUpdatedWebhook === true)
        {
            $this->app->events->fire('api.payout.updated', [$payout]);
        }
    }

    public function fetchAndUpdateGatewayBalanceIfStale(Merchant\Balance\Entity $balanceEntity, bool $isPayoutCreateFlow = false)
    {
        /** @var BankingAccountStatement\Details\Entity $basDetail */
        $basDetails = $balanceEntity->bankingAccountStatementDetails;

        $balanceLastFetchedAt = $basDetails->getBalanceLastFetchedAt();

        $nowTime = Carbon::now(Timezone::IST);

        $diffTime = $nowTime->diffInMinutes(Carbon::createFromTimestamp($balanceLastFetchedAt, Timezone::IST));

        $lastFetchedAtRateLimit =  (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::GATEWAY_BALANCE_LAST_FETCHED_AT_RATE_LIMITING]);

        if (empty($lastFetchedAtRateLimit) === true)
        {
            $lastFetchedAtRateLimit = FundAccountPayout\Direct\Base::DEFAULT_GATEWAY_BALANCE_LAST_FETCHED_AT_RATE_LIMITING;
        }

        if ($diffTime > $lastFetchedAtRateLimit)
        {
            if ($isPayoutCreateFlow === true)
            {
                //since balance is stale for payout create we are dispatching the balance fetch to queue and going forward.
                (new BankingAccount\Core)->dispatchGatewayBalanceUpdateJob($basDetails->getChannel(), $basDetails->getMerchantId());

                return [$basDetails, true];
            }

            $input = [
                Merchant\Balance\Entity::CHANNEL        => $balanceEntity->getChannel(),
                Merchant\Balance\Entity::MERCHANT_ID    => $balanceEntity->getMerchantId(),
                Merchant\Balance\Entity::ACCOUNT_NUMBER => $balanceEntity->getAccountNumber()
            ];

            $basDetails = (new BankingAccount\Core)->fetchAndUpdateGatewayBalanceWrapper($input);
        }

        return [$basDetails, false];
    }

    public function dispatchStuckPayouts(array $input): array
    {
        $this->trace->info(
            TraceCode::DISPATCH_STUCK_PAYOUTS,
            $input);

        if ((array_key_exists('payout_service', $input) === true))
        {
            $value = array_pull($input, 'payout_service');

            if ($value == true)
            {
                return $this->payoutServiceProcessStuckPayouts->dispatchStuckPayouts($input);
            }
        }

        $statuses = $input['statuses'] ?? [];

        if (empty($statuses) === true)
        {
            return [];
        }

        $merchantIdsWhitelist = $input['merchant_ids'] ?? [];
        $merchantIdsBlacklist = $input['merchant_ids_not'] ?? [];

        $payoutStuckDuration  = $input['payout_stuck_duration'] ?? self::DEFAULT_PAYOUT_STUCK_DURATION;
        $payoutStuckThreshold = $input['payout_stuck_threshold'] ?? self::DEFAULT_PAYOUT_STUCK_THRESHOLD;
        $payoutAutoExpireThreshold =
            $input['payout_auto_expire_threshold'] ?? self::DEFAULT_PAYOUT_AUTO_EXPIRE_THRESHOLD;

        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();
        $stuckBeforeTimestamp = $currentTimeStamp - $payoutStuckDuration;
        $payoutStuckThresholdTimestamp = $stuckBeforeTimestamp - $payoutStuckThreshold;
        $payoutAutoExpireTimestamp = $payoutStuckThresholdTimestamp - $payoutAutoExpireThreshold;

        $payouts = $this->repo->payout->fetchPayoutsWithStatus(
            $statuses,
            $stuckBeforeTimestamp,
            $payoutAutoExpireTimestamp,
            $merchantIdsWhitelist,
            $merchantIdsBlacklist);

        $dispatchedPayoutCount = 0;
        $autoExpirePayoutsCount = 0;

        /** @var Entity $payout */
        foreach ($payouts as $payout)
        {
            $status = $payout->getStatus();

            switch ($status) {

                case Status::INITIATED:
                case Status::CREATED:
                    if ($payout->getCreatedAt() > $payoutStuckThresholdTimestamp) {
                        $this->pushPayoutPostCreate($payout);

                        $dispatchedPayoutCount++;
                    }

                    break;

                case Status::CREATE_REQUEST_SUBMITTED:
                    if ($payout->getCreatedAt() > $payoutStuckThresholdTimestamp) {
                        $this->pushPayoutPostCreate($payout);

                        $dispatchedPayoutCount++;
                    }
                    else
                    {
                        PayoutsAutoExpire::dispatch($this->mode, $payout->getId());

                        $autoExpirePayoutsCount++;
                    }

                    break;
            }
        }

        $counts = [
            'dispatched_payouts_count' => $dispatchedPayoutCount,
            'auto_expire_payouts_count' => $autoExpirePayoutsCount
        ];

        $this->trace->info(
            TraceCode::STUCK_PAYOUTS_DISPATCHED,
            $counts + $input);

        return $counts;
    }

    public function pushPayoutPostCreate(Entity $payout)
    {
        $payoutQueueFlagDetails = $payout->payoutsDetails;

        if ($payoutQueueFlagDetails != null and
            $payoutQueueFlagDetails->getQueueIfLowBalanceFlag() === true)
        {
            $payout->setQueueFlag(true);
        }

        PayoutPostCreateProcessLowPriority::dispatch($this->mode, $payout->getId(), $payout->toBeQueued());
    }

    /**
     * TODO : Remove this code. Has been kept here for backward compatibility
     *
     * @param Base\PublicCollection $queuedPayouts
     *
     * @return array
     */
    public function processDispatchForQueuedPayouts(Base\PublicCollection $queuedPayouts)
    {
        $grouped = $queuedPayouts->groupBy(Entity::BALANCE_ID);

        $traceData = [];

        foreach ($grouped as $balanceId => $payouts)
        {
            // We get balance via payout since we would have already fetched balance entity
            // when fetching the payouts list. Avoiding an extra DB query here by doing this.

            /** @var Merchant\Balance\Entity $balanceEntity */
            $balanceEntity = $payouts->first()->balance;

            // In case of current accounts(direct), balance in balance entity is stale since in our system we create
            // transactions only when we fetch account statement from bank.So for current account we can't use balance
            // from balance table.
            // So before making payout we need to get balance amount in account from gateway.
            // We check if account type is direct or not. If direct then fetch balance from gateway if balance last
            // fetched at was a while ago(using threshold to decide that).Use this balance amount to dispatch payout.
            // If account type shared then use balance amount from balance entity.

            $balanceAmount = $balanceEntity->getBalanceWithLockedBalance();

            if ($balanceEntity->isAccountTypeDirect() === true)
            {
                $balanceResponse = $this->getLatestBalanceForDirectAccount($balanceEntity);

                $balanceAmount = $balanceResponse[Constants\Entity::BALANCE];
            }

            $dispatchedData = $this->dispatchApplicablePayouts($balanceAmount, $payouts, $balanceEntity);

            $traceData[$balanceId] = [
                'original_balance'          => $balanceAmount,
                'balance_remaining'         => $dispatchedData['balance_remaining'],
                'total_payout_count'        => count($payouts),
                'dispatched_payout_count'   => $dispatchedData['dispatched_payout_count'],
                'dispatched_payout_amount'  => ($balanceAmount - $dispatchedData['balance_remaining']),
            ];
        }

        $this->trace->info(
            TraceCode::PAYOUT_DISPATCH_SUMMARY,
            $traceData
        );

        return $traceData;
    }

    public function getLatestBalanceForDirectAccount(Merchant\Balance\Entity $balanceEntity, bool $isPayoutCreateFlow = false)
    {
        /** @var BankingAccountStatement\Details\Entity $basDetailsUpdated */
        list($basDetailsUpdated, $isStaleAndDispatched) = $this->fetchAndUpdateGatewayBalanceIfStale($balanceEntity, $isPayoutCreateFlow);

        $balanceAmount = $basDetailsUpdated->getGatewayBalance() ?? 0;

        $balanceAmount = $this->negateODIfApplicable($balanceEntity->merchant, $balanceAmount);

        $balanceAmount = $this->negateMinimumBalance($balanceEntity->merchant, $balanceAmount, $balanceEntity->getId());

        return [
            Constants\Entity::BALANCE  => $balanceAmount,
            'isStaleAndDispatched'     => $isStaleAndDispatched
        ];
    }

    public function getLatestGatewayBalanceForFundManagementPayout($fundManagementPayouts, $basDetails)
    {
        $basDetails->reload();

        $recentChangeTimestamp = 0;

        /* @var Entity $fundManagementPayout */
        foreach ($fundManagementPayouts as $fundManagementPayout)
        {
            switch ($fundManagementPayout->getStatus())
            {
                case Status::REVERSED:
                    $recentChangeTimestamp = max($recentChangeTimestamp, $fundManagementPayout->getReversedAt());

                    break;

                case Status::PROCESSED:
                    $recentChangeTimestamp = max($recentChangeTimestamp, $fundManagementPayout->getProcessedAt());

                    break;
            }
        }

        if (($recentChangeTimestamp != 0) and
            ($recentChangeTimestamp > $basDetails->getGatewayBalanceChangeAt()))
        {
            $input = [
                Balance\Entity::CHANNEL        => $basDetails->getChannel(),
                Balance\Entity::MERCHANT_ID    => $basDetails->getMerchantId(),
                Balance\Entity::ACCOUNT_NUMBER => $basDetails->getAccountNumber()
            ];

            $updatedBasDetails = (new BankingAccount\Core)->fetchAndUpdateGatewayBalanceWrapper($input);

            $balanceAmount = $this->negateODIfApplicable($updatedBasDetails->merchant, $updatedBasDetails->getGatewayBalance());

            $balanceAmount = $this->negateMinimumBalance($updatedBasDetails->merchant, $balanceAmount, $updatedBasDetails->getBalanceId());

            return $balanceAmount;
        }
        else
        {
            $balanceResponse = $this->getLatestBalanceForDirectAccount($basDetails->balance);

            return $balanceResponse[Constants\Entity::BALANCE];
        }
    }

    /**
     * Function dispatches all applicable payouts(where balance is enough to process the payout) for a given Balance Id
     *
     * @param string $balanceId
     *
     * @return array
     * @throws BadRequestException
     */
    public function processDispatchForQueuedPayoutsForBalance(string $balanceId)
    {
        return $this->mutex->acquireAndRelease(
            'process_queued_payouts_' . $balanceId,
            function() use ($balanceId)
            {
                $queuedPayoutsPaginationData = $this->getQueuedPayoutsPaginationData();

                $offset = $queuedPayoutsPaginationData[$balanceId] ?? 0;

                $feeRecoveryQueuedPayouts = $this->repo->payout->fetchQueuedPayoutsForBalanceId($balanceId, 0, Purpose::RZP_FEES);

                $feeRecoveryQueuedPayoutIds = [];
                $queuedPayoutsToProcess = new PublicCollection();

                foreach ($feeRecoveryQueuedPayouts as $feeRecoveryQueuedPayout)
                {
                    $queuedPayoutsToProcess->add($feeRecoveryQueuedPayout);
                    $feeRecoveryQueuedPayoutIds[] = $feeRecoveryQueuedPayout->getId();
                }

                $queuedPayouts = $this->repo->payout->fetchQueuedPayoutsForBalanceId($balanceId, $offset);

                foreach ($queuedPayouts as $queuedPayout)
                {

                    if (count($queuedPayoutsToProcess) === Repository::QUEUED_PAYOUTS_FETCH_LIMIT)
                    {
                        break;
                    }

                    if (in_array($queuedPayout->getID(), $feeRecoveryQueuedPayoutIds) === false)
                    {
                        $queuedPayoutsToProcess->add($queuedPayout);
                    }
                }

                $summary = [];

                /** @var Merchant\Balance\Entity $balanceEntity */
                $balanceEntity = $this->repo->balance->findOrFailById($balanceId);

                // In case of current accounts(direct), balance in balance entity is stale since in our system we create
                // transactions only when we fetch account statement from bank.So for current account we can't use balance
                // from balance table.
                // So before making payout we need to get balance amount in account from gateway.
                // We check if account type is direct or not. If direct then fetch balance from gateway if balance last
                // fetched at was a while ago(using threshold to decide that).Use this balance amount to dispatch payout.
                // If account type shared then use balance amount from balance entity.

                $balanceAmount = $balanceEntity->getBalanceWithLockedBalanceFromLedger();

                if ($balanceEntity->isAccountTypeDirect() === true)
                {
                    $balanceResponse = $this->getLatestBalanceForDirectAccount($balanceEntity);

                    $balanceAmount = $balanceResponse[Constants\Entity::BALANCE];
                }

                $totalQueuedPayouts = $this->repo->payout->fetchCountOfQueuedPayoutsForBalance($balanceId);

                $dispatchedData = $this->dispatchApplicablePayouts($balanceAmount, $queuedPayoutsToProcess , $balanceEntity);

                $dispatchedPayoutCount = $dispatchedData['dispatched_payout_count'];

                $this->updateOffsetForBalance($balanceId,
                                              $offset,
                                              $queuedPayoutsPaginationData,
                                              $dispatchedPayoutCount,
                                              $totalQueuedPayouts);

                $summary[$balanceId] = [
                    'original_balance'         => $balanceAmount,
                    'balance_remaining'        => $dispatchedData['balance_remaining'],
                    'total_payout_count'       => count($queuedPayoutsToProcess),
                    'dispatched_payout_count'  => $dispatchedPayoutCount,
                    'dispatched_payout_amount' => ($balanceAmount - $dispatchedData['balance_remaining']),
                ];

                $this->trace->info(
                    TraceCode::PAYOUT_DISPATCH_SUMMARY,
                    $summary
                );

                return $summary;
            },
            300,
            ErrorCode::BAD_REQUEST_QUEUED_PAYOUT_INITIATE_ANOTHER_OPERATION_IN_PROGRESS);
    }

    public function processQueuedPayout(string $payoutId): Entity
    {
        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                $payout->getValidator()->validateProcessingQueuedPayout();

                //
                // Currently, we support queued concept only for Fund Account type.
                // If we are supporting for others, the processor call needs to be fixed here.
                // Also, need to fix transaction.created event in the processor since
                // we do that only for fund_account and not for others.
                //
                // Apart from this, we also have to handle dispatching FTA for queued payouts.
                //
                // We also have to handle the fund transfer destination while processing the queued payout.
                //
                if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                              $payout->getMerchantId()) === true)
                {
                    return $this->mutex->acquireAndRelease(
                        PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                        function() use ($payout) {
                            $payout->reload();

                            if ($payout->getIsPayoutService() === true)
                            {
                                return null;
                            }

                            return $this->processQueuedPayoutBase($payout);
                        },
                        self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                        ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                        PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
                }
                else
                {
                    return $this->processQueuedPayoutBase($payout);
                }

            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    /**
     * @param Entity $payout
     *
     * @return Entity
     * @throws \Throwable
     */
    function processQueuedPayoutBase(Entity $payout): Entity
    {
        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($payout->merchant)
                       ->processQueuedPayout($payout);

        if ($payout->getStatus() === Status::CREATED)
        {
            $this->processLedgerPayout($payout);
        }

        //
        // There might be some type of payouts where we don't want to dispatch FTA.
        // Should handle that before adding any other type of payouts as queued.
        //
        $this->postCreationForPayouts($payout);

        return $payout;
    }

    public function initiateProcessingOfBatchSubmittedPayouts(string $merchantId)
    {
        $limit = $this->getBatchPayoutsFetchLimit();

        $payoutIds = $this->repo->payout->getBatchSubmittedPayoutIds($merchantId, $limit);

        foreach ($payoutIds as $payoutId)
        {
            $traceData = [
                'payout_id' => $payoutId
            ];

            try
            {
                $this->trace->info(
                    TraceCode::PAYOUT_BATCH_PROCESS_STARTED,
                    $traceData
                );

                $this->processBatchSubmittedPayouts($payoutId);

                $this->trace->info(
                    TraceCode::PAYOUT_BATCH_PROCESS_COMPLETED,
                    $traceData
                );
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::PAYOUT_BATCH_PROCESS_FAILED,
                    $traceData);
            }
        }
    }

    public function processBatchSubmittedPayouts(string $payoutId)
    {
        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                              $payout->getMerchantId()) === true)
                {
                    return $this->mutex->acquireAndRelease(
                        PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                        function() use ($payout) {
                            $payout->reload();

                            if ($payout->getIsPayoutService() === true)
                            {
                                return null;
                            }

                            return $this->processBatchSubmittedPayoutsBase($payout);
                        },
                        self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                        ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                        PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
                }
                else
                {
                    return $this->processBatchSubmittedPayoutsBase($payout);
                }
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    /**
     * @param Entity $payout
     *
     * @return Entity
     * @throws \Throwable
     */
    function processBatchSubmittedPayoutsBase(Entity $payout): Entity
    {
        $payout->getValidator()->validateProcessingBatchProcessingPayout();

        //
        // Currently, we support batch_submitted concept only for Fund Account type.
        // If we are supporting for others, the processor call needs to be fixed here.
        // Also, need to fix transaction.created event in the processor since
        // we do that only for fund_account and not for others.
        //
        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($payout->merchant)
                       ->processBatchSubmittedPayout($payout);

        if ($payout->getStatus() === Status::CREATED)
        {
            $this->processLedgerPayout($payout);
        }

        //
        // There might be some type of payouts where we don't want to dispatch FTA.
        // Should handle that before adding any other type of payouts as batch_submitted.
        //
        $this->postCreationForPayouts($payout);

        return $payout;
    }

    public function processPayoutPostCreate(string $payoutId, bool $queueFlag)
    {
        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId, $queueFlag)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                              $payout->getMerchantId()) === true)
                {
                    return $this->mutex->acquireAndRelease(
                        PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                        function() use ($payout, $queueFlag) {
                            $payout->reload();

                            if ($payout->getIsPayoutService() === true)
                            {
                                return null;
                            }

                            return $this->processPayoutPostCreateBase($payout, $queueFlag);
                        },
                        self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                        ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                        PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
                }
                else
                {
                    return $this->processPayoutPostCreateBase($payout, $queueFlag);
                }
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    /**
     * @param Entity $payout
     * @param bool   $queueFlag
     *
     * @return Entity
     * @throws \Throwable
     */
    function processPayoutPostCreateBase(Entity $payout, bool $queueFlag): Entity
    {
        $payout->getValidator()->validatePostCreateProcessPayout();

        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($payout->merchant)
                       ->processPayoutPostCreate($payout, $queueFlag);

        return $payout;
    }

    public function processPayoutPostCreateLowPriority(string $payoutId, bool $queueFlag)
    {
        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId, $queueFlag)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                              $payout->getMerchantId()) === true)
                {
                    return $this->mutex->acquireAndRelease(
                        PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                        function() use ($payout, $queueFlag) {
                            $payout->reload();

                            if ($payout->getIsPayoutService() === true)
                            {
                                return null;
                            }

                            return $this->processPayoutPostCreateLowPriorityBase($payout, $queueFlag);
                        },
                        self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                        ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                        PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
                }
                else
                {
                    return $this->processPayoutPostCreateLowPriorityBase($payout, $queueFlag);
                }
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    /**
     * @param Entity $payout
     * @param bool   $queueFlag
     *
     * @return Entity
     * @throws \Throwable
     */
    function processPayoutPostCreateLowPriorityBase(Entity $payout, bool $queueFlag): Entity
    {
        // associate sub balance if merchant is not on ledger reverse shadow
        if (($payout->merchant->isFeatureEnabled(FeatureConstants::LEDGER_REVERSE_SHADOW) === false) and
            ($payout->isStatusCreateRequestSubmitted() === true))
        {
            $payout = $this->setSubBalance($payout);
        }

        // If failure happens after payout transaction was created, next retry attempt will fail in validation as status is different.
        $payout->getValidator()->validatePostCreateProcessPayout();

        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($payout->merchant)
                       ->processPayoutPostCreate($payout, $queueFlag);

        return $payout;
    }

    public function fireWebhookForPayoutCreationFailure($metadata, $input, $merchantId)
    {
        $traceData = [
            'metadata'    => $metadata,
            'input'       => $input,
            'merchant_id' => $merchantId
        ];

        try
        {
            $this->trace->info(TraceCode::PAYOUT_FAILURE_WEBHOOK_DISPATCH_INITIATED, $traceData);

            $this->merchant = $this->repo->merchant->findOrFail($merchantId);

            if ((array_key_exists(Entity::NARRATION, $input) === false) or
                (empty($input[Entity::NARRATION]) === true))
            {
                $input[Entity::NARRATION] = $this->merchant->getBillingLabel();
            }

            /** @var Balance\Entity $balance */
            $balance = (new Balance\Repository)->getBalanceByAccountNumberOrFail($input[Entity::ACCOUNT_NUMBER], $merchantId);

            $payout = new Entity;

            $fundAccountInput = array_pull($input, Entity::FUND_ACCOUNT);
            unset($input[Entity::ACCOUNT_NUMBER]);

            $input[Entity::FUND_ACCOUNT_ID] = 'fa_' . $metadata[Entity::FUND_ACCOUNT][Entity::ID];

            $input[Entity::BALANCE_ID] = $balance->getId();

            $payout->build($input);

            $payout->setAttribute(Entity::FUND_ACCOUNT_ID, $metadata[Entity::FUND_ACCOUNT][Entity::ID]);

            if (array_key_exists(Entity::REFERENCE_ID, $input) === true)
            {
                $payout->setAttribute(Entity::REFERENCE_ID, $input[Entity::REFERENCE_ID]);
            }

            $payout->setId($metadata[Entity::PAYOUT][Entity::ID]);

            $payout->setCreatedAt($metadata[Entity::PAYOUT][Entity::CREATED_AT]);

            $payout->setFailureReason('Payout failed due to technical failure. Please retry after 30 min');

            $payout->setStatusCode("FTS_ATTEMPT_CREATE_FAILED");

            $payout->setStatus(Status::FAILED);

            $payout->merchant()->associate($this->merchant);

            $payout->balance()->associate($balance);

            $payout->setUpdatedAt(Carbon::now()->getTimestamp());

            (new PayoutsStatusDetailsCore())->create($payout);

            $this->app->events->dispatch('api.payout.failed', [$payout]);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_FAILURE_WEBHOOK_FAILED_TO_DISPATCH,
                $traceData);
        }
    }

    // This is a feature for high tps merchants. A single merchant can have multiple sub balance (entity of type balance)
    // apart from main balance entity. We will pick one of the sub balance from sub balance map and associate it to the payout.
    // Transaction will also have sub balance id as balance id.
    public function setSubBalance(Entity $payout)
    {
        $subBalances = (new Balance\SubBalanceMap\Core)->getSubBalancesForParentBalance($payout->getBalanceId());

        if (count($subBalances) === 0)
        {
            return $payout;
        }

        /** @var Merchant\Balance\SubBalanceMap\Entity $subBalanceToFix */
        $subBalanceToFix = $this->pickSubBalanceFromMap($payout, $subBalances);

        $payout->setAttribute(Entity::BALANCE_ID, $subBalanceToFix);

        return $payout;
    }

    public function pickSubBalanceFromMap(Entity $payout, $subBalances)
    {
        $redis = $this->app['redis']->connection();

        $redisKey = "round_robin_" . $payout->getBalanceId();

        try
        {
            // Picking a sub balance from available sub balances in round robin fashion.
            $value = $redis->incr($redisKey);

            $balanceNumber = $value % count($subBalances);

            if ($value == 4000000)
            {
                $redis->decrby($redisKey, 4000000);
            }
        }
        catch (\Throwable $exception)
        {
            // If redis in round robin fails then randomly a sub balance is picked.
            try
            {
                $balanceNumber = random_int(0, count($subBalances) - 1);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::SUB_BALANCE_MAP_RANDOM_GENERATE_FAILURE,
                    []);
                $balanceNumber = 0;
            }

            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::SUB_BALANCE_MAP_ROUND_ROBIN_FAILURE,
                []);
        }

        $this->trace->info(TraceCode::SUB_BALANCE_MAP_PAYOUT_BALANCE_ID, [
            'payout_id'         => $payout->getId(),
            Entity::MERCHANT_ID => $payout->getMerchantId(),
            Entity::BALANCE_ID  => $payout->getBalanceId(),
            'child_balance_id'  => $subBalances[$balanceNumber],
            'balance_number'    => $balanceNumber
        ]);

        return $subBalances[$balanceNumber];
    }

    public function processScheduledPayout(string $payoutId): Entity
    {
        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                $payout->getValidator()->validateProcessingScheduledPayout();

                if ($payout->isStatusPending() === true)
                {
                    $this->forceRejectPayout($payout);

                    $this->trace->info(
                        TraceCode::SCHEDULED_PAYOUT_AUTO_REJECTED,
                        [
                            'payout_id' => $payout->getId(),
                        ]);

                    // We have only implemented the email function. No SMS will be sent.
                    (new Notifications\Factory)->getNotifier(Notifications\Type::PAYOUT_AUTO_REJECTED, $payout)
                                               ->notify();

                    return $payout;
                }

                //
                // If scheduled payouts were created via batch uploads, then it may lead to increased processing times
                // for certain merchants, hence we send these to batch_submitted state so that when dispatching,
                // we don't starve any single merchant
                //
                if (empty($payout->getBatchId()) === false)
                {
                    $payout->setStatus(Status::BATCH_SUBMITTED);

                    $this->repo->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::SCHEDULED_PAYOUT_TO_BATCH_SUBMITTED,
                        [
                            'payout_id'     => $payout->getId(),
                            'merchant_id'   => $payout->getMerchantId(),
                            'batch_id'      => $payout->getBatchId(),
                        ]);

                    return $payout;
                }

                if ($this->isPayoutApplicableForP2PDelay($payout) === true)
                {
                    $payoutsDetails = $payout->payoutsDetails;

                    if ($payoutsDetails != null and $payoutsDetails->getQueueIfLowBalanceFlag() === true)
                    {
                        $payout->setQueueFlag(true);
                    }
                }

                if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                              $payout->getMerchantId()) === true)
                {
                    return $this->mutex->acquireAndRelease(
                        PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                        function() use ($payout) {
                            $payout->reload();

                            if ($payout->getIsPayoutService() === true)
                            {
                                return null;
                            }

                            return $this->processScheduledPayoutBase($payout);
                        },
                        self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                        ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
                }
                else
                {
                    return $this->processScheduledPayoutBase($payout);
                }
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED,
            PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
    }


    /**
     * @param Entity $payout
     *
     * @return Entity
     * @throws \Throwable
     */
    function processScheduledPayoutBase(Entity $payout): Entity
    {
//
        // Currently, we support scheduled payouts concept only for Fund Account type.
        // If we are supporting for others, the processor call needs to be fixed here.
        // Also, need to fix transaction.created event in the processor since
        // we do that only for fund_account and not for others.
        //
        // Apart from this, we also have to handle dispatching FTA for scheduled payouts.
        //
        // We also have to handle the fund transfer destination while processing the scheduled payout.
        //
        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($payout->merchant)
                       ->processScheduledPayout($payout);

        if ($payout->getStatus() === Status::CREATED)
        {
            $this->processLedgerPayout($payout);
        }

        $this->postCreationForPayouts($payout);

        return $payout;
    }

    public function processDispatchForScheduledPayouts($scheduledPayouts)
    {
        $grouped = $scheduledPayouts->groupBy(Entity::BALANCE_ID);

        $traceData = [];

        foreach ($grouped as $balanceId => $payouts)
        {
            $dispatchedData = $this->dispatchAllScheduledPayouts($payouts);

            $traceData[$balanceId] = [
                'total_payout_count'                    => count($payouts),
                'dispatched_payout_count'               => $dispatchedData['dispatched_payout_count'],
                'dispatched_payout_amount'              => ($dispatchedData['dispatched_payout_amount']),
                'no_dispatch_for_payout_service_count'  => $dispatchedData['no_dispatch_for_payout_service_count']
            ];
        }

        $this->trace->info(
            TraceCode::PAYOUT_SCHEDULED_DISPATCH_SUMMARY,
            $traceData
        );

        return $traceData;
    }

    public function cancelPayout(Entity $payout,
                                 string $remarks = null): Entity
    {
        // it can cause deadlock if any payout getting processed from webhook or api call
        // at the same time of cancel payout.
        //
        // this is the only case where we Migration redis lock before payout lock .
        // its a rare scenario bcz cancel payout is manual
        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                      $payout->getMerchantId()) === true)
        {
            return $this->mutex->acquireAndRelease(
                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                function() use ($payout, $remarks) {
                    $payout->reload();

                    return $this->cancelPayoutBase($payout, $remarks);
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED,
                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
        }
        else
        {
            return $this->cancelPayoutBase($payout, $remarks);
        }
    }

    /**
     * @param Entity      $payout
     * @param string|null $remarks
     *
     * @return mixed|Entity|null
     * @throws BadRequestException
     * @throws Exception\InvalidArgumentException
     */
    function cancelPayoutBase(Entity $payout, string $remarks = null): mixed
    {
        if ($payout->getIsPayoutService() === true)
        {
            $this->payoutCancelServiceClient->cancelPayoutViaMicroservice($payout->getId(), $remarks);

            return $payout->reload();
        }

        // If Payout has purpose 'rzp_fees' we won't allow merchant to cancel that
        if ((Purpose::isInInternal($payout->getPurpose()) === true)
            and
            ($payout->getPurpose() !== Purpose::RZP_TAX_PAYMENT))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_PAYOUT_CANCEL_NOT_PERMITTED,
                null,
                [
                    'payout_id' => $payout->getId(),
                ]);
        }

        return $this->mutex->acquireAndRelease(
            $payout->getId(),
            function() use ($payout, $remarks) {
                $payout->getValidator()->validateCancel();

                $payout->setStatus(Status::CANCELLED);

                $payout->setRemarks($remarks);

                $cancellationUser = app('basicauth')->getUser();

                $cancellationUserId = (empty($cancellationUser) === false) ? $cancellationUser->getId() : null;

                $payout->setCancellationUserId($cancellationUserId);

                $this->repo->saveOrFail($payout);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    public function approvePayout(Entity $payout, array $input): Entity
    {
        $payout = $this->processWorkflowActionOnPayout($payout, true, $input);

        return $payout;
    }

    public function approveIciciCAPayout(Entity $payout, array $input): Entity
    {
        $payoutId = $payout->getId();

        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId, $input)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                /** @var Validator $payoutValidator */
                $payoutValidator = $payout->getValidator();

                $payoutValidator->validateProcessingPendingPayout();

                $payout = $this->getProcessor('fund_account_payout')
                               ->setMerchant($payout->merchant)
                               ->processIciciCAPendingPayout($payout,$input);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    /**
     * @param Entity $payout
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function forceRejectPayout(Entity $payout): Entity
    {
        $payout->getValidator()->validatePayoutStatusForApproveOrReject();

        // If payout workflow is on workflow service, process via workflow service
        // else process via api workflow system
        if ($this->shouldCallWorkflowService($payout) === true)
        {
            if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                             $payout->getMerchantId()) === true)
            {
                $this->mutex->acquireAndRelease(
                    PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                    function() use ($payout) {
                        $payout->reload();

                        $this->rejectWorkflowViaWorkflowService($payout, [Entity::FORCE_REJECT => true]);
                    },
                    self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                    ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                    PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
            }
            else
            {
                $this->rejectWorkflowViaWorkflowService($payout, [Entity::FORCE_REJECT => true]);
            }

            return $payout->reload();
        }

        /** @var Workflow\Action\Entity|null $workflowAction */
        $workflowAction = $this->getOpenWorkflowActionForPayout($payout);

        if ($workflowAction === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'No further actions can be performed on this payout',
                null,
                ['payout_id' => $payout->getId()]);
        }

        $payout = $this->repo->transaction(
            function() use ($payout, $workflowAction)
            {
                $state = State\Name::REJECTED;

                (new Action\Core)->updateState($workflowAction, $state);

                $payout = $this->rejectPendingPayout($payout);

                return $payout;
            });

        return $payout;
    }

    public function rejectPayout(Entity $payout, array $input): Entity
    {
        $payout = $this->processWorkflowActionOnPayout($payout, false, $input);

        return $payout;
    }

    protected function processWorkflowActionOnPayout(Entity $payout, bool $approve, array $input): Entity
    {
        // If payout workflow is on workflow service, process via workflow service
        // else process via api workflow system
        if (($payout->getIsPayoutService() === true)
            || ($this->shouldCallWorkflowService($payout) === true))
        {
            return $this->processActionOnPayoutViaWorkflowService($payout, $approve, $input);
        }

        $payoutId = $payout->getId();

        // Adding this mutex here to handle concurrent requests.
        $payout = $this->mutex->acquireAndRelease(
            'process_workflow_action_on_' . $payoutId,
            function() use ($payout, $approve, $input)
            {
                // Reload $payout here if needed.
                if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                              $payout->getMerchantId()) === true)
                {
                    return $this->mutex->acquireAndRelease(
                        PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                        function() use ($payout, $approve, $input) {
                            $payout->reload();

                            return $this->processWorkflowActionOnPayoutBase($payout, $approve, $input);
                        },
                        self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                        ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED,
                        PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
                }
                else
                {
                    return $this->processWorkflowActionOnPayoutBase($payout, $approve, $input);
                }
            });

        return $this->repo->payout->findOrFail($payout->getId());
    }


    /**
     * @param Entity $payout
     * @param bool   $approve
     * @param array  $input
     *
     * @return mixed
     * @throws BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    function processWorkflowActionOnPayoutBase(Entity $payout, bool $approve, array $input): mixed
    {
        /** @var Workflow\Action\Entity|null $workflowAction */
        $workflowAction = $this->getOpenWorkflowActionForPayout($payout);

        $action = ($approve === true) ? 'approve' : 'reject';

        if ($workflowAction === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'No further actions can be performed on this payout',
                null,
                ['action' => $action, 'payout_id' => $payout->getId()]);
        }

        $payout = $this->repo->transaction(
            function() use ($payout, $workflowAction, $approve, $action, $input) {
                $userComment = $input[Workflow\Action\Checker\Entity::USER_COMMENT] ?? null;

                $actionCheckerCreateParams = [
                    Workflow\Action\Checker\Entity::ACTION_ID => $workflowAction->getId(),
                    Workflow\Action\Checker\Entity::APPROVED  => ($approve === true) ? 1 : 0, // 1 = true
                ];

                if (empty($userComment) === false)
                {
                    $actionCheckerCreateParams[Workflow\Action\Checker\Entity::USER_COMMENT] = $userComment;
                }

                $actionChecker = (new Workflow\Action\Checker\Core)->create($actionCheckerCreateParams);

                $e = null;

                if ((empty($actionChecker) === true))
                {
                    $e = new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_ACTION_FAILED,
                        null,
                        [
                            'create_params'       => $actionCheckerCreateParams,
                            'payout_id'           => $payout->getId(),
                            'workflows_action_id' => $workflowAction->getId(),
                            'action'              => $action,
                        ]);
                }

                //tracking slack app related events
                $this->trackPayoutEvent(EventCode::PENDING_PAYOUT_APPROVE_REJECT_ACTION,
                                        $payout,
                                        $e);

                if ((empty($actionChecker) === true) and
                    ($this->app['basicauth']->getAdmin()->isSuperAdmin() === false))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_ACTION_FAILED,
                        null,
                        [
                            'create_params'       => $actionCheckerCreateParams,
                            'payout_id'           => $payout->getId(),
                            'workflows_action_id' => $workflowAction->getId(),
                            'action'              => $action,
                        ]);
                }

                //
                // Reload the workflow_action entity. Changes from the previous function calls
                // may not have been sync'd
                //
                $workflowAction->reload();

                $this->trace->info(
                    TraceCode::PAYOUT_WORKFLOW_ACTION_INFO,
                    [
                        'workflow_action' => $workflowAction,
                        'action'          => $action,
                        'payout_id'       => $payout->getId(),
                    ]);

                // This check is similar in processApprovePayout function
                // If Logic is changed then it needs to be changed at both the places
                $queueFlag = isset($input[Entity::QUEUE_IF_LOW_BALANCE]) ? boolval($input[Entity::QUEUE_IF_LOW_BALANCE]) : true;

                if (($approve === true) and
                    ($workflowAction->getApproved() === true))
                {
                    if ($payout->getIsPayoutService() == true)
                    {
                        $this->payoutWorkflowServiceClient->approvePayoutViaMicroservice(
                            $payout->getId(),
                            $queueFlag
                        );
                    }
                    else
                    {
                        $payout = $this->processApprovePayout($payout, $input);
                    }
                }
                else
                {
                    if (($approve === false) and
                        ($workflowAction->isRejected() === true))
                    {
                        if ($payout->getIsPayoutService() == true)
                        {
                            $this->payoutWorkflowServiceClient->rejectPayoutViaMicroservice(
                                $payout->getId()
                            );
                        }
                        else
                        {
                            $payout = $this->processRejectPayout($payout);
                        }
                    }
                }

                return $payout;
            });

        return $payout;
    }


    // Fetch payout by id from payouts service
    public function fetchByIdFromPayoutsService(string $id, array $input): array
    {
        $this->trace->info(
            TraceCode::PAYOUT_FETCH_BY_ID_VIA_MICROSERVICE_REQUEST,
            [
                'id'    => $id,
                'input' => $input,
            ]);

        $response = $this->payoutServiceFetchClient->fetch(EntityConstant::PAYOUT, $id, $input);

        $this->trace->info(
            TraceCode::PAYOUT_FETCH_BY_ID_VIA_MICROSERVICE_RESPONSE,
            [
                'response' => $response
            ]);

        return $response;
    }

    // Fetch payout multiple from payouts service
    public function fetchMultipleFromPayoutsService(array $input): array
    {
        $this->trace->info(
            TraceCode::PAYOUT_FETCH_MULTIPLE_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input
            ]);

        $response = $this->payoutServiceFetchClient->fetchMultiple(EntityConstant::PAYOUT, $input);

        $this->trace->info(
            TraceCode::PAYOUT_FETCH_MULTIPLE_VIA_MICROSERVICE_RESPONSE,
            [
                'response' => $response
            ]);

        return $response;
    }

    /**
     * @param array $input
     * @return bool
     */
    public function shouldFetchPayoutByIdViaMicroservice(array $input): bool
    {
        // Doing it this way so that we can avoid as many db calls as possible for fetching features.
        if ($this->mode !== Constants\Mode::LIVE)
        {
            return false;
        }

        /** @var $auth BasicAuth */
        $auth = $this->app['basicauth'];

        // Currently on private, proxy and privilege auth requests and response for get payouts is supported on payouts
        // service hence we are not allowing requests via payouts service if neither of these auth are used.
        if (($auth->isPrivateAuth() !== true) and
            ($auth->isProxyAuth() !== true) and
            ($auth->isPrivilegeAuth() !== true))
        {
            return false;
        }

        $partnerMerchantId = $auth->getPartnerMerchantId();

        $applicationId = $auth->getOAuthApplicationId();

        // Doing this separately because for auth apps, $auth->isPrivateAuth() is also true so requests can still come
        // to ps.
        if ((empty($partnerMerchantId) === false) or
            (empty($applicationId) === false))
        {
            return false;
        }

        $isRoutingViaMicroserviceFeasible = (new Fetch)->canFetchRequestBeRoutedToMicroservice($input);

        if ($isRoutingViaMicroserviceFeasible === false)
        {
            return false;
        }

        $isFetchEnabledViaPs = $this->merchant->isFeatureEnabled(FeatureConstants::FETCH_VA_PAYOUTS_VIA_PS);

        return $isFetchEnabledViaPs;
    }

    /**
     * @param array $input
     * @return bool
     */
    public function shouldFetchPayoutsViaMicroserviceAndUpdateInputAccordingly(array & $input): bool
    {
        // Doing it this way so that we can avoid as many db calls as possible.
        if ($this->mode !== Constants\Mode::LIVE)
        {
            return false;
        }

        /** @var $auth BasicAuth */
        $auth = $this->app['basicauth'];

        // Currently on private and proxy auth requests and response for get payouts is supported on payouts service
        // hence we are not allowing requests via payouts service if neither of these auth are used.
        if (($auth->isPrivateAuth() !== true) and
            ($auth->isProxyAuth() !== true))
        {
            return false;
        }

        $isRoutingViaMicroserviceFeasible = (new Fetch)->canFetchRequestBeRoutedToMicroservice($input);

        if ($isRoutingViaMicroserviceFeasible === false)
        {
            return false;
        }

        $accountType = null;

        if (isset($input[Entity::BALANCE_ID]) === true)
        {
            /** @var Balance\Entity $balance */
            $balance = $this->repo->balance->findOrFailById($input[Entity::BALANCE_ID]);

            $accountType = $balance->getAccountType();
        }

        else
        {
            // If we don't get account number in request, we check how many balances exist for merchant with type
            // banking. If they are !== 1, we return false else we check the account type of the balance and based on
            // that decide to let it go via payouts service or not.
            $balances = $this->repo->balance->getMerchantBalancesByType($this->merchant->getId(),
                                                                        Balance\Type::BANKING);

            $countOfBalances = count($balances);

            if ($countOfBalances !== 1)
            {
                return false;
            }

            $accountType = $balances[0]->getAccountType();
        }


        if ($accountType !== Balance\AccountType::SHARED)
        {
            return false;
        }

        $isFetchEnabledViaPs = $this->merchant->isFeatureEnabled(FeatureConstants::FETCH_VA_PAYOUTS_VIA_PS);

        if ($isFetchEnabledViaPs === true)
        {
            if (isset($input[Entity::BALANCE_ID]) === true)
            {
                $input[Entity::ACCOUNT_NUMBER] = $balance->getAccountNumber();

                unset($input[Entity::BALANCE_ID]);
            }

            if (isset($input[Entity::PAYOUT_MODE]) === true)
            {
                $input[Entity::MODE] = $input[Entity::PAYOUT_MODE];

                unset($input[Entity::PAYOUT_MODE]);
            }
        }

        return $isFetchEnabledViaPs;
    }


    /**
     * @param Entity $payout
     * @return bool
     */
    public function shouldCallWorkflowService(Entity $payout): bool
    {
        // If a workflow is present for the payout in the entity map repo
        // If present, we should call the workflow service
        // else process via API workflow system
        $workflowViaWorkflowService = (new EntityMap\Repository)->isPresent(Entity::PAYOUT, $payout->getId());

        $this->trace->info(
            TraceCode::PROCESS_REQUEST_VIA_WORKFLOW_SERVICE,
            [
                'id'    => $payout->getId(),
                'workflowViaWorkflowService' => $workflowViaWorkflowService
            ]);

        return $workflowViaWorkflowService === true;
    }

    /**
     * @param Entity $payout
     * @param bool $approved
     * @param array $input
     * @return Entity
     * @throws Exception\RuntimeException
     * @throws Exception\ServerErrorException
     */
    protected function processActionOnPayoutViaWorkflowService(Entity $payout, bool $approved, array $input): Entity
    {
        $workflowAction = null;

        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                      $payout->getMerchantId()) === true)
        {
            $this->mutex->acquireAndRelease(
                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                function() use ($payout, $approved, $input) {
                    $payout->reload();

                    $this->processActionOnPayoutViaWorkflowServiceBase($approved, $payout, $input);
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED,
                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
        }
        else
        {
            $this->processActionOnPayoutViaWorkflowServiceBase($approved, $payout, $input);
        }

        return $payout->reload();
    }

    /**
     * @param bool   $approved
     * @param Entity $payout
     * @param array  $input
     *
     * @return void
     * @throws BadRequestValidationFailureException
     * @throws \Throwable
     */
    function processActionOnPayoutViaWorkflowServiceBase(bool $approved, Entity $payout, array $input): void
    {
        if ($approved === true)
        {
            $this->approveWorkflowViaWorkflowService($payout, $input);
        }
        else
        {
            $this->rejectWorkflowViaWorkflowService($payout, $input);
        }
    }

    /**
     * @param Entity $payout
     * @param array $input
     * @return Entity
     * @throws Exception\RuntimeException
     * @throws Exception\ServerErrorException
     */
    public function retryPayoutWorkflow(Entity $payout, array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_WORKFLOW_SERVICE_WORKFLOW_CREATE_RETRY,
            [
                'id'    => $payout->getId(),
                'input' => $input
            ]);

        $response = null;

        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                      $payout->getMerchantId()) === true)
        {
            $response = $this->mutex->acquireAndRelease(
                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                function() use ($payout, $input) {
                    $payout->reload();

                    // error handling is done in the payout service layer
                    return $this->workflowService->createWorkflow($payout, $input);
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED,
                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
        }
        else
        {
            $response = $this->workflowService->createWorkflow($payout, $input);
        }

        if (empty($response) === false)
        {
            (new EntityMap\Core)->create($response, $payout);
        }

        return $payout;
    }

    protected function getOpenWorkflowActionForPayout(Entity $payout)
    {
        $workflowActions = (new Workflow\Action\Core)->fetchOpenActionOnEntityOperation(
            $payout->getId(),
            $payout->getEntity(),
            Permission\Name::CREATE_PAYOUT,
            Org\Entity::RAZORPAY_ORG_ID);

        //
        // There can only be 0 or 1 open workflow actions on a payout
        // If there are more, it could be due to a bug, and we'd need to debug this
        // This check can be removed once the code is stable
        //
        if ($workflowActions->count() > 1)
        {
            throw new Exception\LogicException(
                'More than 1 open workflow actions found for payout',
                null,
                ['payout_id' => $payout->getId(), 'workflow_actions' => $workflowActions->toArray()]);
        }

        // Returns the single workflow action for the payout, else null if none exist
        return $workflowActions->first();
    }

    protected function dispatchApplicablePayouts(int $totalBalance,
                                                 Base\PublicCollection $payouts,
                                                 Balance\Entity $balance)
    {
        $dispatchedCount = 0;

        // This is only false when there is a queued fee_recovery payout and the merchant doesn't
        // have enough balance for that payout
        $rzpFeesRecoverySucceeded = true;

        $experimentEnabled = $this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                   $balance->getMerchantId());

        foreach ($payouts as $key => $payout)
        {
            if($experimentEnabled === true)
            {
                $this->mutex->acquireAndRelease(
                    PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                    function() use ($payouts, $payout, $key) {
                        $payout->reload();

                        // We don't want to process queued payouts that have payout service enabled as those will be processed by
                        // payout service.
                        if ($payout->getIsPayoutService() === true)
                        {
                            unset($payouts[$key]);
                        }
                    },
                    self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                    ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED,
                    PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
            }
            else
            {
                if ($payout->getIsPayoutService() === true)
                {
                    unset($payouts[$key]);
                }
            }

            $purpose = $payout->getPurpose();

            if (($purpose === Purpose::RZP_FEES) or
                ($purpose === Purpose::RZP_CHARGE_COLLECTIONS))
            {
                $totalPayoutAmount = $payout->getAmount();

                if ($totalBalance < $totalPayoutAmount)
                {
                    $rzpFeesRecoverySucceeded = false;

                    continue;
                }

                $totalBalance -= $totalPayoutAmount;

                $this->dispatchQueuedPayout($payout, $totalBalance);

                $dispatchedCount += 1;

                unset($payouts[$key]);
            }
        }

        // If fee_recovery payout does not get processed, we will not process any other queued payout either
        if ($rzpFeesRecoverySucceeded === false)
        {
            return [
                'balance_remaining'        => $totalBalance,
                'dispatched_payout_count'  => $dispatchedCount,
            ];
        }

        (new Processor\Base)->unsetQueuedFeeRecoveryPayoutsFlag($balance->getId(), $balance->getMerchantId());

        // We are going to get the count of Free Payouts here but we shall not be incrementing or decrementing the
        // count at this point. Increments/Decrements should ideally reside in the same flow.
        // This will also help avoid issues with counter getting stuck or any other race conditions.
        $freePayoutsConsumed = (new CounterHelper)->getCounterForBalance($balance)->getFreePayoutsConsumed();

        foreach ($payouts as $payout)
        {
            $payoutAmount = $payout->getAmount();

            if ($payout->balance->isAccountTypeDirect() === true)
            {
                $totalPayoutAmount = $payoutAmount;
            }
            else
            {
                // We have to explicitly calculate fees here since if it's queued, transaction wouldn't
                // have been created and hence the fees also wouldn't have been calculated.
                list($payoutFees, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

                $freePayoutsAllowed = (new Balance\FreePayout)->getFreePayoutsCount($balance);

                if ($freePayoutsConsumed < $freePayoutsAllowed)
                {
                    // If we have enough free payouts, we don't consider the fees while dispatching.
                    $totalPayoutAmount = $payoutAmount;

                    // We are not updating the counter in the database because, at this point, we are only dispatching
                    // the payout. Dispatch does not guarantee processing and if we increment the counter, we would
                    // be blocking the free payout to be used by other flows such as normal payouts or scheduled payouts
                    $freePayoutsConsumed++;
                }
                else
                {
                    $totalPayoutAmount = $payoutAmount + $payoutFees;
                }
            }

            if ($totalBalance < $totalPayoutAmount)
            {
                if ($payout->getQueuedReason() === QueuedReasons::SYNCING_BALANCE)
                {
                    $payout->setQueuedReason(QueuedReasons::LOW_BALANCE);

                    $payout->setConnection($this->mode);

                    $this->repo->payout->saveOrFail($payout);
                }
                continue;
            }

            $totalBalance -= $totalPayoutAmount;

            $this->dispatchQueuedPayout($payout, $totalBalance);

            $dispatchedCount += 1;
        }

        return [
            'balance_remaining'        => $totalBalance,
            'dispatched_payout_count'  => $dispatchedCount,
        ];
    }

    protected function dispatchAllScheduledPayouts(Base\PublicCollection $scheduledPayouts)
    {
        $dispatchedCount = 0;
        $dispatchedAmount = 0;
        $noDispatchPayoutServiceCount = 0;

        foreach ($scheduledPayouts as $scheduledPayout)
        {
            if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                          $scheduledPayout->getMerchantId()) === true)
            {
                list($count, $amount, $noCount) = $this->mutex->acquireAndRelease(
                    PayoutConstants::MIGRATION_REDIS_SUFFIX . $scheduledPayout->getId(),
                    function() use ($scheduledPayout) {
                        $dispatchedCount              = 0;
                        $dispatchedAmount             = 0;
                        $noDispatchPayoutServiceCount = 0;

                        $scheduledPayout->reload();

                        if ($scheduledPayout->getIsPayoutService() === false)
                        {
                            $this->dispatchScheduledPayout($scheduledPayout);

                            $dispatchedCount  = 1;
                            $dispatchedAmount = $scheduledPayout->getAmount();
                        }
                        else
                        {
                            $noDispatchPayoutServiceCount = 1;
                        }

                        return [$dispatchedCount, $dispatchedAmount, $noDispatchPayoutServiceCount];
                    },
                    self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                    ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED,
                    PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);

                $dispatchedCount              += $count;
                $dispatchedAmount             += $amount;
                $noDispatchPayoutServiceCount += $noCount;
            }
            else
            {
                if ($scheduledPayout->getIsPayoutService() === false)
                {
                    $this->dispatchScheduledPayout($scheduledPayout);
                    $dispatchedCount += 1;
                    $dispatchedAmount += $scheduledPayout->getAmount();
                }
                else
                {
                    $noDispatchPayoutServiceCount += 1;
                }
            }
        }

        return [
            'dispatched_payout_amount'              => $dispatchedAmount,
            'dispatched_payout_count'               => $dispatchedCount,
            'no_dispatch_for_payout_service_count'  => $noDispatchPayoutServiceCount
        ];
    }

    protected function dispatchScheduledPayout(Entity $payout)
    {
        $payoutId = $payout->getId();

        $traceInfo = [
            'payout_id' => $payoutId,
            'amount'    => $payout->getAmount(),
            'status'    => $payout->getStatus(),
        ];

        try
        {
            $this->trace->info(TraceCode::PAYOUT_SCHEDULED_DISPATCH_INIT, $traceInfo);

            ScheduledPayoutsProcess::dispatch($this->mode, $payoutId);

            $this->trace->info(TraceCode::PAYOUT_SCHEDULED_DISPATCH_COMPLETE, $traceInfo);
        }
        catch (\Throwable $e)
        {
            // If the dispatch fails due to any reason, cron will
            // pick up these payouts again and attempt to dispatch.

            $data = $traceInfo + [ 'message' => $e->getMessage() ];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_SCHEDULED_DISPATCH_FAILED,
                $data);
        }
    }

    protected function dispatchQueuedPayout(Entity $payout, int $currentBalance)
    {
        $payoutId = $payout->getId();

        $traceInfo = [
            'payout_id'         => $payoutId,
            'amount'            => $payout->getAmount(),
            'current_balance'   => $currentBalance,
        ];

        try
        {
            $this->trace->info(TraceCode::PAYOUT_QUEUE_DISPATCH_INIT, $traceInfo);

            QueuedPayouts::dispatch($this->mode, $payoutId);

            $this->trace->info(TraceCode::PAYOUT_QUEUE_DISPATCH_COMPLETE, $traceInfo);
        }
        catch (\Throwable $e)
        {
            // If the dispatch fails due to any reason, cron will
            // pick up these payouts again and attempt to dispatch.

            $data = $traceInfo + [ 'message' => $e->getMessage() ];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_QUEUE_DISPATCH_FAILED,
                $data);
        }
    }

    public function dispatchBalanceIdsForQueuedPayouts(array $balanceIdList)
    {
        foreach ($balanceIdList as $balanceId)
        {
            $traceInfo = [
                'balance_id' => $balanceId
            ];

            try
            {
                $this->trace->info(TraceCode::PAYOUT_QUEUED_INITIATE_DISPATCH_JOB, $traceInfo);

                QueuedPayoutsInitiate::dispatch($this->mode, $balanceId);

                $this->trace->info(TraceCode::PAYOUT_QUEUED_INITIATE_DISPATCH_COMPLETE, $traceInfo);
            }
            catch (\Throwable $e)
            {
                // If the dispatch fails due to any reason, cron will
                // pick up these payouts again and attempt to dispatch.

                $data = $traceInfo + [ 'message' => $e->getMessage() ];

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_QUEUED_INITIATE_DISPATCH_FAILED,
                    $data);
            }
        }
    }

    public function dispatchBalanceIdsForQueuedPayoutsToPayoutsService(array $balanceIdList = [])
    {
        if ($this->isLiveMode() === true)
        {
            try
            {
                $payoutServiceInput = [
                    Entity::BALANCE_IDS => array_values($balanceIdList)
                ];

                $this->trace->info(
                    TraceCode::PAYOUT_QUEUED_INITIATE_DISPATCH_TO_PAYOUT_SERVICE,
                    $payoutServiceInput);

                $this->payoutServiceQueuedInitiateClient
                    ->dispatchQueuedPayoutBalanceIdToMicroservice($payoutServiceInput);

                $this->trace->info(
                    TraceCode::PAYOUT_QUEUED_INITIATE_DISPATCH_TO_PAYOUT_SERVICE_COMPLETE,
                    $payoutServiceInput);
            }
            catch (\Throwable $e)
            {
                // If the dispatch fails due to any reason, cron will
                // pick up these payouts again and attempt to dispatch.
                $data = $payoutServiceInput + [ 'message' => $e->getMessage() ];

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_QUEUED_DISPATCH_TO_PAYOUT_SERVICE_FAILED,
                    $data);
            }
        }
    }

    //returns the sla for on hold payouts if present specific for a merchant else default sla
    public function getMerchantSlaForOnHoldPayouts(string $merchantId, string $queuedReason = null)
    {
        $merchantSlaConfigList = (new Admin\Service)->getConfigKey([
            'key' => Admin\ConfigKey::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA
        ]);

        if (in_array($merchantId, array_keys($merchantSlaConfigList), true) === true)
        {
            $slaValue = $merchantSlaConfigList[$merchantId];
        }
        else
        {
            $slaValue = (new Admin\Service)->getConfigKey([
                'key' => Admin\ConfigKey::RX_ON_HOLD_PAYOUTS_DEFAULT_SLA
            ]);

            if (empty($slaValue) === true)
            {
                switch ($queuedReason) {
                    case QueuedReasons::GATEWAY_DEGRADED:
                        // partner bank downtime sla
                        $slaValue = self::DEFAULT_SLA_FOR_PARTNER_BANK_ON_HOLD_PAYOUTS_IN_MINS;
                        break;
                    default:
                        // keeping this as default
                        // bene bank downtime taken as default configuration
                        $slaValue = self::DEFAULT_SLA_FOR_ON_HOLD_PAYOUTS_IN_MINS;
                }
            }
        }
        return $slaValue;
    }

    public function processOnHoldPayouts(string $payoutId)
    {
        try
        {
            return $this->mutex->acquireAndRelease(
                $payoutId,
                function () use ($payoutId)
                {
                    /** @var Entity $payout */
                    $payout = $this->repo->payout->findOrFail($payoutId);

                    $payout->getValidator()->validateOnHoldPayoutProcessing();

                    $isBeneDown = $this->checkIfBeneBankIsDown($payout);

                    if ($isBeneDown === false)
                    {
                        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                                      $payout->getMerchantId()) === true)
                        {
                            return $this->mutex->acquireAndRelease(
                                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                                function() use ($payout) {
                                    $payout->reload();

                                    if ($payout->getIsPayoutService() === true)
                                    {
                                        return null;
                                    }

                                    return $this->processOnHoldPayoutsBase($payout);
                                },
                                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                                ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED,
                                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
                        }
                        else
                        {
                            return $this->processOnHoldPayoutsBase($payout);
                        }
                    }
                    else
                    {
                        $isSlaBreached = $this->checkIfMerchantSlaBreachedForOnHoldPayout($payout);

                        if ($isSlaBreached === true)
                        {
                            //Failure reason is marked as BENE_BANK_DOWN since the sla is breached and the bank is still down.
                            $payout->setFailureReason(QueuedReasons::BENE_BANK_DOWN);

                            $payout->setStatusCode("BBANK_OFFLINE");

                            $payout->setStatus(Status::FAILED);

                            $this->repo->payout->saveOrFail($payout);

                            // Since laravel would have already loaded the relation for fund account and bank account
                            // Refetching the relation shouldn't need to do a DB call.
                            // Adding a default value for cases a bene bank code doesn't exist (for instance, non fund account payouts)
                            $beneBankCode = substr($payout->fundAccount->account->getIfscCode(), 0, 4)
                                ?? Constants\Metric::LABEL_NONE_VALUE;

                            $this->trace->count(
                                Metric::ON_HOLD_PAYOUT_FAILED_TOTAL,
                                [Constants\Metric::LABEL_BANK_CODE => $beneBankCode]);

                            $this->trace->info(
                                TraceCode::ON_HOLD_PAYOUT_FAILED,
                                [
                                    'payout_id'      => $payout->getId(),
                                    'payout_status'  => $payout->getStatus(),
                                    'failure_reason' => $payout->getFailureReason(),
                                ]);

                            (new PayoutsStatusDetailsCore())->create($payout);

                            $this->app->events->dispatch('api.payout.failed', [$payout]);
                        }
                    }
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ON_HOLD_PAYOUT_PROCESSING_JOB_FAILED,
                [
                    'payout_id' => $payoutId,
                ]);
        }
    }

    /**
     * @param Entity $payout
     *
     * @return Entity
     * @throws \Throwable
     */
    function processOnHoldPayoutsBase(Entity $payout): Entity
    {
        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($payout->merchant)
                       ->processOnHoldPayout($payout);

        if ($payout->getStatus() === Status::CREATED)
        {
            $this->processLedgerPayout($payout);
        }

        return $payout;
    }

    protected function checkIfMerchantSlaBreachedForOnHoldPayout(Entity $payout, string $queuedReason = null)
    {
        $slaValue = $this->getMerchantSlaForOnHoldPayouts($payout->getMerchantId(), $queuedReason);

        $this->trace->info(
            TraceCode::ON_HOLD_PAYOUT_MERCHANT_SLA_CHECKED,
            [
                'sla'         => $slaValue,
                'payout_id'   => $payout->getId(),
                'merchant_id' => $payout->getMerchantId()
            ]);

        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        if($payout->getOnHoldAt() <= (strtotime(('-' . ($slaValue * 60) . ' seconds'), $currentTimeStamp)))
        {
             return true;
        }
        return false;
    }

    public function processPartnerBankDowntimeHoldPayouts(string $payoutId)
    {
        try
        {
            return $this->mutex->acquireAndRelease(
                $payoutId,
                function () use ($payoutId)
                {
                    /** @var Entity $payout */
                    $payout = $this->repo->payout->findOrFail($payoutId);

                    $payout->getValidator()->validatePartnerBankDowntimeHoldPayoutProcessing();

                    $isPartnerBankDown = $this->checkIfPartnerBankIsDown($payout);

                    if ($isPartnerBankDown === false)
                    {
                        if ($this->holdIfBeneBankDown($payout) === true) {
                            return null;
                        }

                        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                                      $payout->getMerchantId()) === true)
                        {
                            return $this->mutex->acquireAndRelease(
                                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                                function() use ($payout) {
                                    $payout->reload();

                                    if ($payout->getIsPayoutService() === true)
                                    {
                                        return null;
                                    }

                                    return $this->processPartnerBankDowntimeHoldPayoutsBase($payout);
                                },
                                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
                        }
                        else
                        {
                            return $this->processPartnerBankDowntimeHoldPayoutsBase($payout);
                        }
                    }
                    else
                    {

                        $isSlaBreached = $this->checkIfMerchantSlaBreachedForOnHoldPayout($payout,
                            QueuedReasons::GATEWAY_DEGRADED);

                        if (!$isSlaBreached) {
                            return null;
                        }

                        //Failure reason is marked as PARTNER_BANK_DEGRADED since the sla is breached and the bank is still down.
                        $payout->setFailureReason(QueuedReasons::GATEWAY_DEGRADED);

                        $payout->setStatusCode("PARTNER_BANK_OFFLINE");

                        $payout->setStatus(Status::FAILED);

                        $this->repo->payout->saveOrFail($payout);

                        $this->trace->count(Metric::PARTNER_BANK_ON_HOLD_FAILED,
                               ['mode'         => $payout->getMode(),
                                'channel'      => $payout->getChannel(),
                                'account_type' => $payout->balance->getAccountType()]);

                        $this->trace->info(
                            TraceCode::PARTNER_BANK_ON_HOLD_FAILED,
                            [
                                'payout_id'      => $payout->getId(),
                                'payout_status'  => $payout->getStatus(),
                                'failure_reason' => $payout->getFailureReason(),
                            ]);

                        (new PayoutsStatusDetailsCore())->create($payout);

                        $this->app->events->dispatch('api.payout.failed', [$payout]);
                    }
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PARTNER_BANK_ON_HOLD_PROCESSING_FAILED,
                [
                    'payout_id' => $payoutId,
                ]);
        }
    }

    /**
     * @param Entity $payout
     *
     * @return Entity
     * @throws \Throwable
     */
    function processPartnerBankDowntimeHoldPayoutsBase(Entity $payout): Entity
    {
        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($payout->merchant)
                       ->processOnHoldPayout($payout);

        if ($payout->getStatus() === Status::CREATED)
        {
            $this->processLedgerPayout($payout);
        }

        return $payout;
    }

    public function dispatchOnHoldPayouts(array $payoutIdList)
    {
        try
        {
            foreach ($payoutIdList as $payoutId)
            {
                $traceInfo = [
                    'payout_id' => $payoutId,
                ];

                $this->trace->info(TraceCode::ON_HOLD_PAYOUT_PROCESSING_JOB, $traceInfo);

                OnHoldPayoutsProcess::dispatch($this->mode, $payoutId);

                $this->trace->info(TraceCode::ON_HOLD_PAYOUT_PROCESSING_DISPATCH_COMPLETE, $traceInfo);
            }
        }
        catch (\Throwable $e)
        {
            // If the dispatch fails due to any reason, cron will
            // pick up these again and attempt to dispatch.
            $data = $traceInfo + ['message' => $e->getMessage()];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ON_HOLD_PAYOUT_PROCESSING_DISPATCH_FAILED,
                $data);
        }
    }

    public function dispatchPartnerBankOnHoldPayouts(array $payoutIdList)
    {
        try
        {
            foreach ($payoutIdList as $payoutId)
            {
                $traceInfo = [
                    'payout_id' => $payoutId,
                ];

                $this->trace->info(TraceCode::PARTNER_BANK_ON_HOLD_PROCESSING_JOB, $traceInfo);

                PartnerBankDowntimeHoldPayouts::dispatch($this->mode, $payoutId);

                $this->trace->info(TraceCode::PARTNER_BANK_ON_HOLD_DISPATCH_COMPLETE, $traceInfo);
            }
        }
        catch (\Throwable $e)
        {
            // If the dispatch fails due to any reason, cron will
            // pick up these again and attempt to dispatch.
            $data = $traceInfo + ['message' => $e->getMessage()];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PARTNER_BANK_ON_HOLD_DISPATCH_FAILED,
                $data);
        }
    }

    public function processAutoExpiryOfPayouts(string $payoutId)
    {
        try
        {
            return $this->mutex->acquireAndRelease(
                $payoutId,
                function () use ($payoutId)
                {
                    $payout = $this->repo->payout->findOrFail($payoutId);

                    $status = $payout[Entity::STATUS];

                    switch ($status)
                    {
                        case Status::PENDING:
                            $this->forceRejectPayout($payout);
                            break;

                        case Status::QUEUED:
                        case Status::CREATE_REQUEST_SUBMITTED:
                            $this->handlePayoutFailed($payout);
                            break;

                        default:
                            $this->trace->warning(
                                TraceCode::UNKNOWN_STATUS_SENT_TO_PAYOUT,
                                $payoutId
                            );
                    }
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_AUTO_EXPIRY_JOB_FAILED,
                [
                    'payout_id' => $payoutId,
                ]);
        }
    }

    public function dispatchPayoutsForAutoExpiry(array $payoutIdList)
    {
        try
        {
            foreach ($payoutIdList as $payoutId)
            {
                $traceInfo = [
                    'payout_id' => $payoutId,
                ];

                $this->trace->info(TraceCode::PAYOUT_AUTO_EXPIRY_JOB, $traceInfo);

                PayoutsAutoExpire::dispatch($this->mode, $payoutId);

                $this->trace->info(TraceCode::PAYOUT_AUTO_EXPIRY_DISPATCH_COMPLETE, $traceInfo);
            }
        }
        catch (\Throwable $e)
        {
            // If the dispatch fails due to any reason, cron will
            // pick up these again and attempt to dispatch.
            $data = $traceInfo + ['message' => $e->getMessage()];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_AUTO_EXPIRY_DISPATCH_FAILED,
                $data);
        }
    }

    protected function getRetryPayoutInputForMerchant(Entity $payout): array
    {
        $type = $payout->getPayoutType();

        $amount = $payout->getAmount();

        $payoutInput = [
            Entity::TYPE        => $type,
            Entity::AMOUNT      => $amount,
            Entity::CURRENCY    => $payout->getCurrency(),
        ];

        //
        // If the merchant makes an on_demand payout request with 100rs,
        // we actually create the payout entity with amount 98rs. We do
        // this only for on_demand payouts. Hence, when retrying, we
        // add the fees and amount to create a payout request of the
        // original amount which the merchant would have sent initially.
        //
        if ($type === Entity::ON_DEMAND)
        {
            $fees = $payout->getFees();

            $payoutInput[Entity::AMOUNT] = $amount + $fees;
        }

        return $payoutInput;
    }

    protected function getRetryPayoutInputForFundAccount(Entity $payout): array
    {
        $payoutInput = [
            Entity::FUND_ACCOUNT_ID => FundAccount\Entity::getSignedId($payout->getFundAccountId()),
            Entity::AMOUNT          => $payout->getAmount(),
            Entity::CURRENCY        => $payout->getCurrency(),
            Entity::PURPOSE         => $payout->getPurpose(),
            Entity::BALANCE_ID      => $payout->getBalanceId(),
            Entity::MODE            => $payout->getMode(),
        ];

        // TODO: Support Notes copy also.

        return $payoutInput;
    }

    protected function getRetryPayoutInputForCustomerWallet(Entity $payout): array
    {
        $payoutInput = [
            Entity::FUND_ACCOUNT_ID => FundAccount\Entity::getSignedId($payout->getFundAccountId()),
            Entity::AMOUNT          => $payout->getAmount(),
            Entity::CURRENCY        => $payout->getCurrency(),
            Entity::PURPOSE         => $payout->getPurpose(),
        ];

        // TODO: Support Notes copy also.

        return $payoutInput;
    }

    public function handlePayoutProcessed(
        Entity $payout,
        $debit_bas = null,
        string $ftaStatus = null,
        array $ftsSourceAccountInformation = [],
        $webhookFlow = false)
    {
        if ($payout->isStatusReversed() === true)
        {
            throw new Exception\LogicException(
                'Attempted to process a reversed payout',
                null,
                [
                    'payout_id' => $payout->getId(),
                ]);
        }

        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                      $payout->getMerchantId()) === true)
        {
            $this->mutex->acquireAndRelease(
                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                function() use ($payout, $ftaStatus, $ftsSourceAccountInformation, $debit_bas, $webhookFlow) {
                    $payout->reload();

                    $this->handlePayoutProcessedBase($payout, $ftaStatus, $ftsSourceAccountInformation, $debit_bas, $webhookFlow);
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
        }
        else
        {
            $this->handlePayoutProcessedBase($payout, $ftaStatus, $ftsSourceAccountInformation, $debit_bas, $webhookFlow);
        }
    }


    /**
     * @param Entity      $payout
     * @param string|null $ftaStatus
     * @param array       $ftsSourceAccountInformation
     * @param mixed       $debit_bas
     *
     * @return void
     * @throws BadRequestException
     */
    function handlePayoutProcessedBase(Entity $payout,
                                       string $ftaStatus = null,
                                       array $ftsSourceAccountInformation = [],
                                       $debit_bas = null,
                                       $webhookFlow = false): void
    {
        if ($payout->getIsPayoutService() === true)
        {
            $this->handlePayoutProcessedForPayoutService($payout, $ftaStatus, $ftsSourceAccountInformation);
        }
        else
        {
            $bankAccStmt = $this->repo->transaction(
                function() use ($payout, $debit_bas, $webhookFlow) {
                    $payout->setStatus(Status::PROCESSED);

                    (new PayoutsStatusDetailsCore())->create($payout);

                    $this->repo->saveOrFail($payout);

                    $bankAccStmt = null;
                    if ($payout->isBalanceAccountTypeDirect() === true)
                    {
                        $bankAccStmt = $this->handlePayoutTransactionForDirectBanking($payout, $debit_bas, $webhookFlow);

                        (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout);
                    }

                    return $bankAccStmt;
                });

            // send event to ledger in shadow mode for direct acc
            // if $bankAccStmt was found for the payout, it indicates that we mapped an existing external transaction to razorpay payout
            // so corresponding event needs to be sent to ledger
            $this->sendExtToPayoutEventToLedger($payout, $bankAccStmt);

            $this->app->events->dispatch('api.payout.processed', [$payout]);

            (new Notifications\Factory)->getNotifier(Notifications\Type::PAYOUT_PROCESSED_CONTACT_COMMUNICATION,
                                                     $payout)->notify();

            $merchant = $payout->merchant;

            if (empty($merchant) === false)
            {
                if ($payout->isBalanceAccountTypeDirect() === true)
                {
                    $this->app['x-segment']->sendEventToSegment(SegmentEvent::CA_PAYOUT_PROCESSED, $merchant);
                }
            }
        }

        $isondemandXVaPayout = self::isOndemandXVaPayout($payout);

        if (($payout->isInterAccountPayout() === true) or
            ($isondemandXVaPayout === true))
        {
            $internal = null;

            try
            {
                $internalEntityService = new \RZP\Models\Internal\PayoutService();
                $internal              = $internalEntityService->createOnPayout($payout);
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException(
                    $ex,
                    Trace::ALERT,
                    TraceCode::INTERNAL_ENTITY_CREATION_FAILED);
            }

            // if its an es payout done to internal account, we need to create
            // a receivable entry for the internal entity. This is because Finops
            // will not be manually verifying and creating receivables for these
            // payouts unlike inter account payouts.
            // If the payout gets reversed we will reverse the recievable entries
            if (($internal !== null) and
                ($isondemandXVaPayout === true))
            {
                try
                {
                    $internalEntityService = new \RZP\Models\Internal\Service();
                    $internal              = $internalEntityService->receive($internal[Entity::ID],
                                                                             [PayoutsLedgerProcessor::TRANSACTOR_EVENT => PayoutsLedgerProcessor::NODAL_FUND_LOADING,
                                                                              PayoutsLedgerProcessor::FTS_INFO         => $ftsSourceAccountInformation]);

                    $this->trace->info(TraceCode::INTERNAL_ENTITY_RECEIVED, $internal);
                }
                catch (\Throwable $ex)
                {
                    $this->app['trace']->traceException(
                        $ex,
                        Trace::ALERT,
                        TraceCode::INTERNAL_ENTITY_RECEIVABLE_FAILED);
                }
            }
        }

        // This will do nothing for reverse shadow
        $this->processLedgerPayout($payout, null, $ftsSourceAccountInformation);

        if (($payout->getIsPayoutService() === false) and
            (self::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true))
        {
            try
            {
                $response = (new PayoutsLedgerProcessor($payout))
                    ->processPayoutAndCreateJournalEntry($payout, null, $ftsSourceAccountInformation);
            }
            catch (\Throwable $e)
            {
                //There's no change to merchant balance here
                //Thus, we wont disrupt the flow by throwing an exception here.
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                    [
                        'payout_id' => $payout->getId()
                    ]
                );
            }
        }

        $this->pushAccountStatementsSourceEvent($payout);
    }

    /**
     * @param Entity               $payout
     * @param Reversal\Entity|null $reversal
     * @param array|null           $ftsSourceAccountInformation
     * Push to ledger sns when a payout status is changed. This will create this payout in ledger DB.
     * Since ledger keeps different records for all payout states, these events are triggered.
     */
    public function processLedgerPayout(Entity $payout,
                                           Reversal\Entity $reversal = null,
                                           array $ftsSourceAccountInformation = [])
    {
        // Currently only shared fundAccount payout is pushed to ledger. So in case of direct, return.
        // In case env variable ledger.enabled is false, return.
        if (($this->app['config']->get('applications.ledger.enabled') === false) or
            ($payout->getBalanceAccountType() === AccountType::DIRECT) or
            ($payout->isBalanceTypePrimary() === true))
        {
            return;
        }

        // If merchant is enabled on payout service then we return.
        if ($payout->getIsPayoutService() === true) {
            return;
        }

        // return if reverse shadow is enabled
        if (self::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true)
        {
            return;
        }

        // If the mode is live but the merchant does not have the ledger journal write feature, we return.
        if (($this->isLiveMode()) and
            ($payout->merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES) === false))
        {
            return;
        }

        $event = Status::getLedgerEventForPayout($payout);

        if (($event === PayoutsLedgerProcessor::PAYOUT_FAILED or
             $event === PayoutsLedgerProcessor::PAYOUT_REVERSED or
             $event === PayoutsLedgerProcessor::INTER_ACCOUNT_PAYOUT_FAILED or
             $event === PayoutsLedgerProcessor::INTER_ACCOUNT_PAYOUT_REVERSED or
             $event === PayoutsLedgerProcessor::VA_TO_VA_PAYOUT_FAILED) and
             $reversal === null)
        {
            // We don't want to call payout_failed event without a reversal
            // We will return here
            $this->trace->info(
                TraceCode::PAYOUT_FAILED_OR_REVERSED_EVENT_BEING_SENT_TO_LEDGER_WITHOUT_REVERSAL,
                [
                    'payout_id' => $payout->getPublicId(),
                ]
            );

            return;
        }

        (new Transaction\Processor\Ledger\Payout)
         ->pushTransactionToLedger($payout, $event, $reversal, $ftsSourceAccountInformation);
    }

    /**
     * @param Entity               $payout
     * @param string               $event
     * @param Reversal\Entity|null $reversal
     * @param External\Entity|null $external
     * @param BASEntity|null       $bas
     * Push event to ledger sns when an external record is identified as payout or reversal. This will create this required journal in ledger DB.
     * Since ledger keeps different records for all payout states, these events are triggered.
     */
    public function processLedgerPayoutForDirect(Entity $payout,
                                                string $event,
                                                Reversal\Entity $reversal = null,
                                                External\Entity $external = null,
                                                BASEntity $bas = null)
    {
        // Here only direct fundAccount payout is pushed to ledger. So in case of shared, return.
        // In case env variable ledger.enabled is false, return.
        if (($this->app['config']->get('applications.ledger.enabled') === false) or
            ($payout->getBalanceAccountType() === AccountType::SHARED) or
            ($payout->isBalanceTypePrimary() === true))
        {
            return;
        }

        // Skip ledger shadow mode for high TPS merchant
        if ($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT) === true)
        {
            return;
        }

        // If the merchant does not have any of the DA's ledger shadow or reverse shadow feature, we return.
        if ($payout->merchant->isFeatureEnabled(Feature\Constants::DA_LEDGER_JOURNAL_WRITES) === false)
        {
            return;
        }

        (new Transaction\Processor\Ledger\Payout)
            ->pushTransactionToLedgerForDirect($event, $payout, $reversal, $external, $bas);
    }

    public static function shouldPayoutGoThroughLedgerReverseShadowFlow($payout)
    {
        // Currently ledger cannot support TPS > 100, and hence we don't call ledger for balance
        // deduction and transaction creation if the below features are enabled
        if (self::isHighTpsMerchantWithSubBalance($payout) === true)
        {
            return false;
        }

        if (($payout->merchant->isFeatureEnabled(FeatureConstants::LEDGER_REVERSE_SHADOW) === true) and
            ($payout->getBalanceType() === Merchant\Balance\Type::BANKING) and
            ($payout->getBalanceAccountType() === Merchant\Balance\AccountType::SHARED) and
            ($payout->getIsPayoutService() === false))
        {
            return true;
        }

        return false;
    }

    /**
     * TODO: The logic here could change for different banks. The structure needs to be accommodated for that.
     * JIRA: https://razorpay.atlassian.net/browse/RX-698
     *
     * @param Entity $payout
     * @param null $debit_bas
     * debit_bas is bas entity with which we want the payout to be linked. This is added for manual
     * linking via admin action
     *
     * webhookFlow variable is true if this function is getting called from handlePayoutProcessed else false
     * we are using this variable to check if there exists corresponding debit bas and if not exists
     * we push for async retry of source linking
     *
     * @throws Exception\LogicException
     */
    public function handlePayoutTransactionForDirectBanking(Entity $payout, $debit_bas = null, $webhookFlow = false)
    {
        if ($payout->hasTransaction() === true)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_ALREADY_LINKED_WITH_PAYOUT,
                [
                    'payout_id'      => $payout->getId(),
                    'transaction_id' => $payout->getTransactionId(),
                    'debit_bas'      => optional($debit_bas)->getId()
                ]);

            return null;
        }

        $bas = $debit_bas;

        if ($bas === null)
        {
            try
            {
                // Fetch BAS for this payout
                if (empty($payout->getUtr()) === false)
                {
                    $bas = $this->repo->banking_account_statement->fetchByUtrForPayout($payout);
                }
                if ($bas === null)
                {
                    $bas = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout) ??
                           $this->repo->banking_account_statement->fetchByGatewayRefNumForPayout($payout);
                }
            }
            catch (\Throwable $e)
            {
                // This happens when a payout is be mapped to multiple bas entities
                // we do not want to throw an exception here, as this operation occurs in a db txn
                $this->trace->traceException($e);
            }

            // This happens when account statement has not been fetched yet, or we were unable to map the BAS to a payout
            if (empty($bas) === true)
            {
                $this->trace->info(
                    TraceCode::BAS_NOT_FOUND_FOR_DEBIT_MAPPING,
                    [
                        'payout_id' => $payout->getId(),
                    ]);

                if ($webhookFlow === true)
                {
                    $this->pushForAsyncSourceLinkingRetry($payout->getId());
                }

                return null;
            }
        }

        if ($bas->getTransactionId() == $bas->getId())
        {
            $this->trace->info(
                TraceCode::PS_BAS_FOUND_FOR_STATEMENT_LINKING,
                [
                    'payout_id' => $payout->getId(),
                    'bas_id' => $bas->getId(),
                ]);

            return null;
        }

        $transaction = $bas->transaction;

        if ($transaction === null)
        {
            throw new Exception\LogicException(
                'bas row selected is not linked to any transaction!',
                ErrorCode::SERVER_ERROR_TRANSACTION_WRONG_SOURCE,
                [
                    'bas_id'            => $bas->getId(),
                    'payout_id'         => $payout->getId(),
                ]);
        }

        $source = $transaction->source;

        if ($source->getEntity() !== Constants\Entity::EXTERNAL)
        {
            throw new Exception\LogicException(
                'payout transaction created for some other source other than external!',
                ErrorCode::SERVER_ERROR_TRANSACTION_WRONG_SOURCE,
                [
                    'transaction_id'    => $transaction->getId(),
                    'bas_id'            => $bas->getId(),
                    'payout_id'         => $payout->getId(),
                ]);
        }

        $this->updateTransactionAndSourceToPayout($payout, $transaction);

        return $transaction->bankingAccountStatement;
    }

    public function pushForAsyncSourceLinkingRetry($payoutId)
    {
        $params = [
            PayoutConstants::PAYOUT_ID    => $payoutId,
        ];

        $this->trace->info(TraceCode::BAS_SOURCE_LINKING_ASYNC_RETRY_DISPATCH_INITIATE, $params);

        try
        {
            BankingAccountStatementSourceLinking::dispatch($this->mode, $params)->delay(self::BAS_LINKING_ASYNC_RETRY_DEFAULT_DELAY);

            $this->trace->info(TraceCode::BAS_SOURCE_LINKING_ASYNC_RETRY_DISPATCH_SUCCESS, $params);
        }
        catch(\Throwable $throwable)
        {
            $this->trace->traceException(
                $throwable,
                Trace::ERROR,
                TraceCode::BAS_SOURCE_LINKING_ASYNC_RETRY_DISPATCH_FAILURE,
                $params);
        }
    }

    /**
     * @param Reversal\Entity $reversal
     * @param null            $credit_bas
     *
     * credit_bas is bas entity with which we want the reversal to be linked. This is added for manual
     * linking via admin action
     *
     * @throws Exception\LogicException
     */
    public function handleReversalTransactionForDirectBanking(Reversal\Entity $reversal, $credit_bas = null)
    {
        list($payoutTransaction, $bankAccStmtForPayout) = $this->handleProcessedPayoutViaReversedPayout($reversal);

        //
        // If we were not able to find payout's transaction, we won't be able to find
        // reversal's transaction also. Hence, no point of doing all the below stuff.
        //
        if (empty($payoutTransaction) === true)
        {
            return [null, null];
        }

        if ($reversal->hasTransaction() === true)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_ALREADY_LINKED_WITH_REVERSAL,
                [
                    'reversal_id'    => $reversal->getId(),
                    'transaction_id' => $reversal->getTransactionId(),
                    'credit_bas'     => $credit_bas->getId()
                ]);

            return [null, null];
        }

        /** @var BASEntity $bas */
        $bas = $credit_bas;

        if ($bas === null)
        {
            $bas = $this->repo->banking_account_statement->fetchByUtrForReversal($reversal)->first() ??
                   $this->repo->banking_account_statement->fetchByCmsRefNumForReversal($reversal)->first() ??
                   $this->repo->banking_account_statement->fetchByGatewayRefNumForReversal($reversal);

            // This happens when account statement has not been fetched yet, or we were unable to map the BAS to a reversal
            if (empty($bas) === true)
            {
                $this->trace->info(
                    TraceCode::BAS_NOT_FOUND_FOR_CREDIT_MAPPING,
                    [
                        'reversal_id' => $reversal->getId(),
                    ]);

                return [null, $bankAccStmtForPayout];
            }
        }

        if ($bas->getTransactionId() == $bas->getId())
        {
            $this->trace->info(
                TraceCode::PS_BAS_FOUND_FOR_CREDIT_MAPPING,
                [
                    'reversal_id' => $reversal->getId(),
                    'bas_id' => $bas->getId(),
                ]);

            return [null, null];
        }

        $transaction = $bas->transaction;

        if ($transaction === null)
        {
            throw new Exception\LogicException(
                'bas row selected is not linked to any transaction!',
                ErrorCode::SERVER_ERROR_TRANSACTION_WRONG_SOURCE,
                [
                    'bas_id'            => $bas->getId(),
                    'reversal_id'       => $reversal->getId(),
                ]);
        }

        $source = $transaction->source;

        if ($source->getEntity() !== Constants\Entity::EXTERNAL)
        {
            throw new Exception\LogicException(
                'reversal transaction created for some other source other than external',
                ErrorCode::SERVER_ERROR_TRANSACTION_WRONG_SOURCE,
                [
                    'transaction_id'    => $transaction->getId(),
                    'bas_id'            => $bas->getId(),
                    'payout_id'         => $reversal->entity->getId(),
                    'reversal_id'       => $reversal->getId(),
                ]);
        }

        $this->updateTransactionAndSourceToReversal($reversal, $transaction);

        return [$transaction->bankingAccountStatement, $bankAccStmtForPayout];
    }

    protected static function isOndemandXVaPayout(Entity $payout)
    {
        $ondemandXVaPayoutMerchants = (new AdminService)->getConfigKey(
            ['key' => ConfigKey::ONDEMAND_SETTLEMENT_INTERNAL_MERCHANTS]);

        for ($i = 0; $i < count($ondemandXVaPayoutMerchants); $i++) {

            if ((isset($ondemandXVaPayoutMerchants[$i][Entity::MERCHANT_ID]) === true) and
                ($ondemandXVaPayoutMerchants[$i][Entity::MERCHANT_ID] === $payout->getMerchantId()) and
                (isset($ondemandXVaPayoutMerchants[$i][Entity::FUND_ACCOUNT_ID]) === true) and
                ($ondemandXVaPayoutMerchants[$i][Entity::FUND_ACCOUNT_ID]) === $payout->getFundAccountId())
            {
                return true;
            }
        }

        return false;
    }
    protected function isInterAccountPayout(Entity $payout)
    {
        return $payout->getPurpose() === Purpose::INTER_ACCOUNT_PAYOUT;
    }

    /**
     * @throws BadRequestException
     */
    public function getBatchType(array $entries): ?string
    {
        $headers = array_keys(current($entries));

        $headers = $this->cleanHeaders($headers);

        $batchType = null;

        if (count($headers) > 11 || count($headers) === 1)
        {
            $batchType = BatchPayoutConstants::PAYOUTS;
        }
        else if (in_array(BatchPayoutConstants::PAYOUT_MODE_FILE_HEADER, $headers, false) === true)
        {
            if (in_array(BatchPayoutConstants::BENE_FA_ID_FILE_HEADER, $headers, false) === true)
            {
                $this->validateMandatoryFileHeaders($headers, BatchPayoutConstants::BANK_TRANSFER_WITH_BENE_ID_BATCH_TYPE);

                $batchType = BatchPayoutConstants::BANK_TRANSFER_WITH_BENE_ID_BATCH_TYPE;
            }
            else
            {
                $this->validateMandatoryFileHeaders($headers, BatchPayoutConstants::BANK_TRANSFER_WITH_BENE_DETAILS_BATCH_TYPE);

                $batchType = BatchPayoutConstants::BANK_TRANSFER_WITH_BENE_DETAILS_BATCH_TYPE;
            }
        }
        else if (in_array(BatchPayoutConstants::BENE_UPI_ID_FILE_HEADER, $headers, false) === true)
        {
            $this->validateMandatoryFileHeaders($headers, BatchPayoutConstants::UPI_WITH_BENE_DETAILS_BATCH_TYPE);

            $batchType = BatchPayoutConstants::UPI_WITH_BENE_DETAILS_BATCH_TYPE;
        }
        else if (in_array(BatchPayoutConstants::BENE_FA_ID_FILE_HEADER, $headers, false) === true)
        {
            $this->validateMandatoryFileHeaders($headers, BatchPayoutConstants::UPI_WITH_BENE_ID_BATCH_TYPE);

            $batchType = BatchPayoutConstants::UPI_WITH_BENE_ID_BATCH_TYPE;
        }
        else if (in_array(BatchPayoutConstants::BENE_FA_ID_WALLET_FILE_HEADER, $headers, false) === true)
        {
            $this->validateMandatoryFileHeaders($headers, BatchPayoutConstants::AMAZONPAY_WITH_BENE_ID_BATCH_TYPE);

            $batchType = BatchPayoutConstants::AMAZONPAY_WITH_BENE_ID_BATCH_TYPE;
        }
        else if (in_array(BatchPayoutConstants::BENE_PHONE_NUMBER_AMAZONPAY_FILE_HEADER, $headers, false) === true)
        {
            $this->validateMandatoryFileHeaders($headers, BatchPayoutConstants::AMAZONPAY_WITH_BENE_DETAILS_BATCH_TYPE);

            $batchType = BatchPayoutConstants::AMAZONPAY_WITH_BENE_DETAILS_BATCH_TYPE;
        }

        return $batchType;
    }

    /**
     * @throws BadRequestException
     */
    private function validateMandatoryFileHeaders(array $headers, string $batchType)
    {
        $mandatoryFileHeaders = BatchPayoutConstants::MANDATORY_FILE_HEADERS_FOR_BATCH_TYPE[$batchType];

        foreach ($mandatoryFileHeaders as $mandatoryFileHeader)
        {
            if (in_array($mandatoryFileHeader, $headers, false) === false)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                    null,
                    null,
                    "File upload failed, file format doesn't follow any of the templates"
                );
            }
        }
    }

    private function cleanHeaders(array $headers): array
    {
        $cleanHeaders = array();

        foreach ($headers as $header)
        {
            $pos = strpos($header, "(");

            if ($pos !== false)
            {
                $cleanHeaders[] = trim(substr($header, 0, strpos($header, "(")));
            }
        }

        return $cleanHeaders;
    }

    protected function isInterAccountTestPayout(Entity $payout)
    {
        return $payout->merchant->isFeatureEnabled(FeatureConstants::INTER_ACCOUNT_TEST_PAYOUT) === true;
    }

    public static function getInterAccountPayoutType(Entity $payout)
    {
        if ($payout->getPurpose() === Purpose::INTER_ACCOUNT_PAYOUT)
        {
            return \RZP\Models\Internal\Service::INTER_ACCOUNT_PAYOUT;
        }
        else if ($payout->merchant->isFeatureEnabled(FeatureConstants::INTER_ACCOUNT_TEST_PAYOUT) === true)
        {
            return \RZP\Models\Internal\Service::TEST_INTER_ACCOUNT_PAYOUT;
        }
        else if (self::isOndemandXVaPayout($payout) === true)
        {
            return \RZP\Models\Internal\Service::ONDEMAND_SETTLEMENT_XVA_PAYOUT;
        }

        return null;
    }

    protected function handleProcessedPayoutViaReversedPayout(Reversal\Entity $reversal)
    {
        /** @var Entity $payout */
        $payout = $reversal->entity;

        if ($payout->hasTransaction() === true)
        {
            return [$payout->transaction, null];
        }

        $this->trace->info(
            TraceCode::PAYOUT_PROCESS_VIA_PAYOUT_REVERSE,
            [
                'reversal_id'   => $reversal->getId(),
                'payout_id'     => $payout->getId()
            ]);

        $bankAccStmt = $this->handlePayoutTransactionForDirectBanking($payout);

        return [$payout->transaction, $bankAccStmt];
    }

    public function updateTransactionAndSourceToPayout(Entity $payout, Transaction\Entity $transaction)
    {
        /** @var External\Entity $source */
        $source = $transaction->source;

        $this->trace->warning(
            TraceCode::TRANSACTION_FOUND_DURING_PAYOUT_PROCESSED,
            [
                'payout_id'         => $payout->getId(),
                'transaction_id'    => $transaction->getId(),
                'source_id'         => $source->getPublicId(),
            ]);

        Tracer::startSpanWithAttributes(Constants\HyperTrace::TRANSACTION_FOUND_DURING_PAYOUT_PROCESSED,
                                        [
                                            'payout_id'         => $payout->getId(),
                                            'transaction_id'    => $transaction->getId(),
                                            'source_id'         => $source->getPublicId(),
                                        ]);

        list($dummyTransaction, $dummyFeesBreakup) = $this->getDummyTransactionAndFeesBreakupForPayout($payout);

        $this->repo->transaction(
            function() use($payout, $transaction, $dummyTransaction, $dummyFeesBreakup)
            {
                //
                // This must be called before updating the transaction in the next statement since
                // we would be updating the source to payout there and we won't be able to get external.
                //
                $this->deleteTransactionExternal($transaction);

                $this->updateTransactionWithDummyPayoutTransactionDetails($payout, $transaction, $dummyTransaction);

                $this->updateFeesBreakupWithDummyFeesBreakupDetails($transaction, $dummyFeesBreakup);

                $this->updateBankingAccountStatementLinkedEntity($transaction->bankingAccountStatement, $payout);
            });

        (new Transaction\Core)->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification($payout->transaction);
    }

    public function updateTransactionAndSourceToReversal(Reversal\Entity $reversal, Transaction\Entity $transaction)
    {
        /** @var External\Entity $source */
        $source = $transaction->source;

        $this->trace->warning(
            TraceCode::TRANSACTION_FOUND_DURING_PAYOUT_REVERSED,
            [
                'reversal_id'       => $reversal->getId(),
                'payout_id'         => $reversal->entity->getId(),
                'transaction_id'    => $transaction->getId(),
                'source_id'         => $source->getId(),
            ]);

        //
        // We don't need fees breakup related stuff here since no fees for RBL.
        // We don't need any dummy transaction also since there's no difference
        // between an external transaction and a reversal transaction.
        // This is mostly because, no fees and tax for reversal.
        //

        $this->repo->transaction(
            function() use($reversal, $transaction)
            {
                //
                // This must be called before updating the transaction in the next statement since
                // we would be updating the source to reversal there and we won't be able to get external.
                //
                $this->deleteTransactionExternal($transaction);

                $this->updateTransactionWithDummyReversalTransactionDetails($reversal, $transaction);

                $this->updateBankingAccountStatementLinkedEntity($transaction->bankingAccountStatement, $reversal);
            });

        (new Transaction\Core)->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification($reversal->transaction);
    }

    protected function getDummyTransactionAndFeesBreakupForPayout(Entity $payout)
    {
        //
        // We do this so that in case any of the payout's attributes/objects are changed in this block, they
        // don't affect the actual payout entity. Especially, since this is being done for dummy purpose.
        // We don't use `clone` since it does only a shallow copy. If objects of the payout entity are changed,
        // the original payout's objects get changed too.
        //
        /** @var Entity $clonedPayout */
        $clonedPayout = (new DeepCopy)->copy($payout);

        return $this->repo->beginTransactionAndRollback(
            function() use ($clonedPayout)
            {
                //
                // We don't want to do any balance related changes since that would have already been taken
                // care of when the "external" transaction was created. Also, everything will be rolled back
                // here anyway. But we still have to set the flag because we do multiple validations when
                // updating balance. These validations could fail. Hence, skipping everything around balance.
                //
                $clonedPayout->setShouldValidateAndUpdateBalancesFlag(false);

                (new DownstreamProcessor('fund_account_payout', $clonedPayout, $this->mode))->processTransaction();

                $dummyTransaction = $clonedPayout->transaction;

                //
                // Here we're not using: $dummyFeesBreakup = $dummyTransaction->feesBreakup;
                // Because:
                // Since the fee_breakup has already been inserted in the db while performing  processTransaction()
                // Though this happens inside a db transaction, which will be rolled back at the end of this function
                // it still sets the $exists flag on the model as true. Due to this any subsequent
                // save on this entity is going to be an UPDATE not an INSERT. See RZP\Base\Repository::saveOrFail()
                //
                // Later when we try to save the fee_breakup in updateFeesBreakupWithDummyFeesBreakupDetails(),
                // it executes as an update instead of an insert.
                // Therefore we're using calculateMerchantFees(), which just build the entity
                // This fee_breakup can be later inserted in the db without any issues
                //
                /** @var Base\PublicCollection $dummyFeesBreakup */

                $fees = $clonedPayout->getFees();

                $tax = $clonedPayout->getTax();

                $pricingRuleId = $clonedPayout->getPricingRuleId();

                $dummyFeesBreakup = (new Transaction\Processor\Payout($clonedPayout))->getFeeSplitForPayouts(
                                    $fees,
                                    $tax,
                                    $pricingRuleId);

                $this->trace->info(
                        TraceCode::DUMMY_TRANSACTION_FEES_BREAKUP_DETAILS,
                        [
                            'transaction_details' => $dummyTransaction->toArrayPublic(),
                            'fees_breakup_details' => $dummyFeesBreakup->toArrayPublic(),
                        ]);

                return [$dummyTransaction, $dummyFeesBreakup];
            });
    }

    protected function deleteTransactionExternal(Transaction\Entity $transaction)
    {
        /** @var External\Entity $external */
        $external = $transaction->source;

        if ($external->getEntity() !== Constants\Entity::EXTERNAL)
        {
            throw new Exception\LogicException(
                'This function should be called to delete only external entity!',
                ErrorCode::SERVER_ERROR_INCORRECT_ENTITY_DELETE,
                [
                    'source_id'     => $transaction->getEntityId(),
                    'source_type'   => $transaction->getType(),
                ]);
        }

        (new External\Core)->delete($external);
    }

    protected function updateTransactionWithDummyPayoutTransactionDetails(Entity $payout,
                                                                          Transaction\Entity $transaction,
                                                                          Transaction\Entity $dummyTransaction)
    {
        $transaction->sourceAssociate($payout);

        $transaction->setFee($dummyTransaction->getFee());
        $transaction->setTax($dummyTransaction->getTax());

        $this->repo->saveOrFail($transaction);

        //
        // This can happen when we are associating payout transaction when the payout
        // is directly marked as `reversed` without first being marked as processed.
        // This happens when the Status API on the payout is called by FTA after a very
        // long time. Due to this, the payout might have gotten processed and later reversed.
        // Since we called the status API directly after it has been reversed on the bank's
        // end, we end up marking the payout as reversed without first marking it as processed.
        //
        // Marking it as processed now will set `processed_at` value. This helps us in easily
        // figuring out which all payouts have been processed successfully (even though they are reversed now).
        //
        if ($payout->hasBeenProcessed() === false)
        {
            $payout->setStatus(Status::PROCESSED);
        }

        $this->repo->saveOrFail($payout);
    }

    protected function updateTransactionWithDummyReversalTransactionDetails(Reversal\Entity $reversal,
                                                                            Transaction\Entity $transaction)
    {
        $transaction->sourceAssociate($reversal);

        $this->repo->saveOrFail($transaction);

        $this->repo->saveOrFail($reversal);
    }

    protected function updateFeesBreakupWithDummyFeesBreakupDetails(Transaction\Entity $transaction,
                                                                    Base\PublicCollection $dummyFeesBreakup)
    {
        // Since external entities do not have any fees_breakup, create them now
        (new Transaction\Core)->saveFeeDetails($transaction, $dummyFeesBreakup);
    }

    /**
     * @param BankingAccountStatement\Entity $bas
     * @param Entity|Reversal\Entity         $entity
     */
    protected function updateBankingAccountStatementLinkedEntity(BankingAccountStatement\Entity $bas,
                                                                 Base\PublicEntity $entity)
    {
        $bas->source()->associate($entity);

        $this->repo->saveOrFail($bas);
    }

    public function handlePayoutReversed(Entity $payout,
                                         string $ftaFailureReason = null,
                                         string $ftaBankStatusCode = null,
                                                $credit_bas = null,
                                         array $ftsSourceAccountInformation = [],
                                         string $ftaStatus = null)
    {
        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                      $payout->getMerchantId()) === true)
        {
            $this->mutex->acquireAndRelease(
                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                function() use (
                    $payout, $ftaFailureReason, $ftaBankStatusCode, $credit_bas,
                    $ftsSourceAccountInformation, $ftaStatus
                )
                {
                    $payout->reload();

                    $this->handlePayoutReversedBase($payout, $ftaStatus, $ftsSourceAccountInformation, $ftaFailureReason, $ftaBankStatusCode, $credit_bas);
                },
                self::PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
        }
        else
        {
            $this->handlePayoutReversedBase($payout, $ftaStatus, $ftsSourceAccountInformation, $ftaFailureReason, $ftaBankStatusCode, $credit_bas);
        }
    }

    /**
     * @param Entity      $payout
     * @param string|null $ftaStatus
     * @param array       $ftsSourceAccountInformation
     * @param string|null $ftaFailureReason
     * @param string|null $ftaBankStatusCode
     * @param mixed       $credit_bas
     *
     * @return mixed
     */
    function handlePayoutReversedBase(Entity $payout,
                                      string $ftaStatus = null,
                                      array $ftsSourceAccountInformation = [],
                                      string $ftaFailureReason = null,
                                      string $ftaBankStatusCode = null,
                                      mixed $credit_bas = null)
    {
        $reversal = null;

        $payout->setReversalStatusUpdateIntentForMetrics(true);

        /*
         * Cloning a payout to be used in case we send some additional events to ledger
         * cloning is done here before payout gets marked as REVERSED because
         */
        $clonedPayout = clone $payout;

        $previousStatus = $payout->getStatus();

        /*
         * If fta status is Status::REVERSED, we have to send reversed event to ledger,
         * so we first send a processed event to make sure correct ledger entries are recorded
         */
        if (($payout->getIsPayoutService() === false) and
            (($ftaStatus === null) || ($ftaStatus === Attempt\Status::REVERSED)))
        {

            // If a payout goes from initiated to directly reversed, we will still wish to move the status
            // from initiated -> processed -> reversed for proper journal writes,
            // hence we are forcing a call to ledger with processed status.
            if (($previousStatus === Status::INITIATED or
                 $previousStatus === Status::CREATED))
            {
                $this->trace->info(
                    TraceCode::PAYOUT_BEING_REVERSED_WITHOUT_PROCESSED_STATE
                );

                $clonedPayout->setStatus(Status::PROCESSED);

                // Sending null for reversal here.
                // We are trying to push an event for the transactor type payout_processed, which doesn't require a
                // reversal object. Anyways, the $reversal variable is currently null anyways.
                // If we try to create a cloned payout after $reversal var is initialised, that is, after the $payout object
                // has its status set to reversed, cloning the payout and then trying to mark it as processed will throw an
                // exception, as the state machine for payout status will prohibit this change.
                $this->processLedgerPayout($clonedPayout, null, $ftsSourceAccountInformation);

                // Since the above call for shadow mode won't work for reverse shadow payouts
                // thus making another call in sync mode
                if (self::shouldPayoutGoThroughLedgerReverseShadowFlow($clonedPayout) === true)
                {
                    try
                    {
                        $response = (new PayoutsLedgerProcessor($clonedPayout))->processPayoutAndCreateJournalEntry(
                            $clonedPayout,
                            null,
                            $ftsSourceAccountInformation
                        );
                    }
                    catch (\Throwable $e)
                    {
                        // trace and ignore exception as it will be retries in async
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                            [
                                'payout_id' => $payout->getId()
                            ]
                        );
                        // We won't throw an exception here, as this is a credit flow. We just try to make sure that the
                        // entry is eventually created
                    }
                }
            }
        }

        // check using service
        if ($payout->getIsPayoutService() === true)
        {
            $this->handlePayoutReversedForPayoutService($payout,
                                                        $ftaFailureReason,
                                                        $ftaBankStatusCode,
                                                        $reversal,
                                                        $ftaStatus,
                                                        $ftsSourceAccountInformation);
        }
        else
        {
            // will be removed after new error object is released.
            $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

            $this->reversePayout($payout, $ftaFailureReason, $ftaBankStatusCode, $credit_bas, $reversal);

            if (empty($reversal) === true)
            {
                return;
            }

            if (self::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true)
            {
                /*
                 * If fta status is Status::REVERSED, we have to send reversed event to ledger,
                 * so we first send a processed event to make sure correct ledger entries are recorded
                 */
                if (($ftaStatus === null) || ($ftaStatus === Attempt\Status::REVERSED))
                {
                    try
                    {
                        $response = (new PayoutsLedgerProcessor($payout))->processPayoutAndCreateJournalEntry(
                            $payout,
                            $reversal,
                            $ftsSourceAccountInformation
                        );
                    }
                    catch (\Throwable $e)
                    {
                        // If an exception is caught here, we ignore it.
                        // TODO: set an alert for exceptions caught in ledger calls
                        // If that exception is found to be a part of this reversal flow, we will make sure that we
                        // create an entry in ledger asynchronously/manually later.
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                            [
                                'payout_id'   => $payout->getId(),
                                'reversal_id' => $reversal->getId()
                            ]
                        );
                    }
                }
                else
                {
                    // failed event for ledger using $clonedPayout
                    //incase of subAccount Payout we want to send the actual payout status to ledger and not clone it to failed.
                    //For subaccount, failed does not need any ledger entry.
                    if ($payout->isSubAccountPayout() === false)
                    {
                        $clonedPayout->setStatus(Status::FAILED);
                    }
                    else
                    {
                        $clonedPayout->setStatus(Status::REVERSED);
                    }

                    try
                    {
                        $response = (new PayoutsLedgerProcessor($clonedPayout))->processPayoutAndCreateJournalEntry(
                            $clonedPayout,
                            $reversal
                        );
                    }
                    catch (\Throwable $e)
                    {
                        // If an exception is caught here, we ignore it.
                        // TODO: set an alert for exceptions caught in ledger calls
                        // If that exception is found to be a part of this reversal flow, we will make sure that we
                        // create an entry in ledger asynchronously/manually later.
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                            [
                                'payout_id'   => $clonedPayout->getId(),
                                'reversal_id' => $reversal->getId()
                            ]
                        );
                    }
                }
            }

            (new PayoutsStatusDetailsCore())->create($payout);

            $this->app->events->dispatch('api.payout.reversed', [$payout]);
        }

        // if a reversal happens on the payout with inter_account_payout then mark the internal entity as failed
        if (($previousStatus === Status::PROCESSED) and
            (($payout->isInterAccountPayout() === true) or
             (self::isOndemandXVaPayout($payout) === true)))
        {
            try
            {
                // get internal entity
                $internalEntityService = new \RZP\Models\Internal\PayoutService();
                $internalEntityService->failOnPayoutReversal($payout,
                                                             [PayoutsLedgerProcessor::TRANSACTOR_EVENT => PayoutsLedgerProcessor::NODAL_FUND_LOADING_REVERSE,
                                                              PayoutsLedgerProcessor::FTS_INFO         => $ftsSourceAccountInformation]);
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException(
                    $ex,
                    Trace::ALERT,
                    TraceCode::INTERNAL_ENTITY_UPDATE_FAILED);
            }
        }

        /*
         * This is for ledger shadow flow
         * If fta status Attempt\Status::REVERSED, it means either
         * no debit has happened OR debit and credit both happened
         * in this cases, we send a failed event to ledger to make
         * sure correct ledger entries are recorded
         */
        if (($ftaStatus === null) || ($ftaStatus === Attempt\Status::REVERSED))
        {
            // reversal event for ledger
            // Note: In case of manual reversal, $ftsSourceAccountInformation is not being passed currently, its being checked
            $this->processLedgerPayout($payout, $reversal, $ftsSourceAccountInformation);
        }
        else
        {
            // failed event for ledger using $clonedPayout
            $clonedPayout->setStatus(Status::FAILED);
            $this->processLedgerPayout($clonedPayout, $reversal);
        }

        $this->pushAccountStatementsSourceEvent($payout);
        return $reversal;
    }


    public function handlePayoutReversedForHighTpsMerchants(Entity $payout,
                                         string $ftaFailureReason = null,
                                         string $ftaBankStatusCode = null,
                                         $credit_bas = null,
                                         array $ftsSourceAccountInformation = [])
    {
        $reversal = null;

        $previousStatus = $payout->getStatus();

        // will be removed after new error object is released.
        $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

        $this->reversePayoutForHighTpsMerchants($payout, $ftaFailureReason, $ftaBankStatusCode, $credit_bas, $reversal);

        (new PayoutsStatusDetailsCore())->create($payout);

        $this->app->events->dispatch('api.payout.reversed', [$payout]);

        $this->processLedgerPayout($payout, $reversal, $ftsSourceAccountInformation);
    }

    protected function handlePayoutFailed(Entity $payout,
                                          string $ftaFailureReason = null,
                                          string $ftaBankStatusCode = null,
                                          string $ftaStatus = null,
                                          array $ftsSourceAccountInformation = [])
    {
        // will be removed after new error object is released.
        $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

        $this->verifyPayoutFailedTransaction($payout);

        $currentStatus = $payout->getStatus();

        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                      $payout->getMerchantId()) === true)
        {
            $this->mutex->acquireAndRelease(
                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                function() use ($payout, $ftaFailureReason, $ftaBankStatusCode, $ftaStatus, $ftsSourceAccountInformation, $currentStatus) {

                    $payout->reload();

                    $this->handlePayoutFailedBase($payout, $ftaFailureReason, $ftaBankStatusCode, $ftaStatus, $ftsSourceAccountInformation, $currentStatus);
                },
                self::PAYOUT_FAILURE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
        }
        else
        {
            $this->handlePayoutFailedBase($payout, $ftaFailureReason, $ftaBankStatusCode, $ftaStatus, $ftsSourceAccountInformation, $currentStatus);
        }

    }

    /**
     * @param Entity      $payout
     * @param string|null $ftaFailureReason
     * @param string|null $ftaBankStatusCode
     * @param string|null $ftaStatus
     * @param array       $ftsSourceAccountInformation
     * @param string|null $currentStatus
     * @return void
     * @throws BadRequestException
     * @throws Exception\InvalidArgumentException
     */
    function handlePayoutFailedBase(Entity $payout,
                                    string $ftaFailureReason = null,
                                    string $ftaBankStatusCode = null,
                                    string $ftaStatus = null,
                                    array $ftsSourceAccountInformation = [],
                                    string $currentStatus = null): void
    {
        // Keeping the mutex TTL high while updating the payout to failed.
        // This is to ensure that the process that is working on the payout
        // resource, releases mutex on the payout only once all entities are
        // saved in the database.
        if ($payout->getIsPayoutService() === false)
        {

            // Payout can go to failed state from initiated or created state only
            Status::validateStatusUpdate(Status::FAILED, $currentStatus);

            $this->mutex->acquireAndRelease(
                'failure_payout_id_' . $payout->getId(),
                function() use ($payout, $ftaFailureReason, $ftaBankStatusCode) {
                    // reloading the payout here to ensure if any other process
                    // gets a mutex on payout resource, it gets a fresh copy
                    // of payout to work.
                    $this->repo->reload($payout);

                    $this->repo->transaction(
                        function() use ($payout, $ftaFailureReason, $ftaBankStatusCode) {

                            $previousStatus = $payout->getStatus();

                            $payout->setFailureReason($ftaFailureReason);

                            $payout->setStatusCode($ftaBankStatusCode);

                            $payout->setStatus(Status::FAILED);

                            if ($this->shouldHandleRewardForFailedPayout($payout) === true)
                            {
                                (new Credits\Transaction\Core)->reverseCreditsForSource(
                                    $payout->getId(),
                                    Constants\Entity::PAYOUT,
                                    $payout);
                            }

                            $balance = $payout->balance;

                            if ($balance->getType() === Merchant\Balance\Type::BANKING)
                            {
                                $this->decreaseFreePayoutsConsumedAndUnsetFeeTypeIfApplicable($payout);

                            }

                            $this->repo->saveOrFail($payout);

                            if ($payout->isBalanceAccountTypeDirect() === true)
                            {
                                (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout, $previousStatus);
                            }
                        });
                },
                self::PAYOUT_FAILURE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );
        }

        if ($payout->getIsPayoutService() === true)
        {
            $ftsInfo = [
                Constants\Entity::FTS_STATUS => $ftaStatus
            ];

            $this->payoutStatusServiceClient->updatePayoutStatusViaFTS(
                $payout->getId(),
                Status::FAILED,
                $ftaFailureReason,
                $ftaBankStatusCode,
                $ftsInfo + $ftsSourceAccountInformation);
        }
        else
        {
            (new PayoutsStatusDetailsCore())->create($payout);

            $this->app->events->dispatch('api.payout.failed', [$payout]);
        }

        $this->pushAccountStatementsSourceEvent($payout);
    }

    private function pushAccountStatementsSourceEvent(Entity $payout): void {
        try {
            $properties = [
                'id'            => $payout->getMerchantId(),
                'experiment_id' => $this->app['config']->get('app.account_statements_source_event_experiment_id'),
                'request_data' => json_encode(['merchant_id' => $payout->getMerchantId()])
            ];

            $isPushToQueueForAccStSourceExperimentEnabled = $this->isSplitzExperimentEnable($properties, 'variables', TraceCode::ACCOUNT_STATEMENTS_SOURCE_EVENT_SPLITZ_ERROR);
            $isCAPayout = $payout->balance->isAccountTypeDirect();

            if ($isPushToQueueForAccStSourceExperimentEnabled && $isCAPayout) {
                $this->pushToAccountServiceQueue($payout);
            }
        } catch (\Exception $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::QUEUE_PUSH_TO_ACCOUNT_STATEMENTS_SOURCE_EVENT_ERROR
            );
        }
    }

    private function pushToAccountServiceQueue(Entity $payout): void {
        $pushData = [
            PayoutConstants::ENTITY_ID      => $payout->getId(),
            PayoutConstants::ENTITY_TYPE    => PayoutConstants::PAYOUTS_ENTITY_TYPE,
            PayoutConstants::UTR            => $payout->getUtr(),
            PayoutConstants::EVENT_CREATED_TIMESTAMP => Carbon::now()->getTimestamp(),
            PayoutConstants::EVENT_ID => UniqueIdEntity::generateUniqueId(),
            PayoutConstants::GATEWAY_REF_NO => "",
            PayoutConstants::CMS_REF_NO => "",
            PayoutConstants::STATUS => $payout->getStatus(),
        ];

        $queueName = $this->app['config']->get('queue.account_statements_source_event.' . $this->mode);

        $this->app['queue']->connection('sqs')->pushRaw(json_encode($pushData), $queueName);
    }

    public function isSplitzExperimentEnable(array $properties, string $checkVariant, string $traceCode = null): bool
    {
        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'experiment_id' => $properties['experiment_id'],
                'splitz_output' => $response,
            ]);

            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === $checkVariant)
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $id = $properties['id'] ?? null;

            $traceCode = $traceCode ?? TraceCode::SPLITZ_ERROR;

            $this->trace->traceException($e, Trace::ERROR, $traceCode, ['id' => $id]);
        }

        return false;
    }

    protected function verifyPayoutFailedTransaction(Entity $payout, string $ftaFailureReason = null)
    {
        if ($payout->hasTransaction() === true)
        {
            throw new Exception\LogicException(
                'A Payout with transaction can not be moved to failed state, it should be reversed',
                null,
                [
                    'payout_id'      => $payout->getId(),
                    'failure_reason' => $ftaFailureReason,
                ]);
        }
        // the below logic tries to find entry in Banking account statement table(BAS) for a payout
        // BAS table has rows only for CA payouts. So skipping below code for VA payouts
        // We are also skipping this for payouts done via PS, since these checks will be migrated there.
        if ($payout->balance->isAccountTypeShared() === true)
        {
            return;
        }

        if (($payout->getIsPayoutService() === true) and
            ($payout->balance->isAccountTypeDirect() === true))
        {
            $variant = $this->app['razorx']->getTreatment($payout->getMerchantId(),
                RazorxTreatment::ENABLE_BAS_CHECK_FOR_PAYOUTS_SERVICE, Constants\Mode::LIVE);

            if ($variant != 'on') {
                return;
            }
        }

        $bas = null;

        if (empty($payout->getUtr()) === false)
        {
            $bas = $this->repo->banking_account_statement->fetchByUtrForPayout($payout);
        }
        if ($bas === null)
        {
            $bas = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout) ??
                   $this->repo->banking_account_statement->fetchByGatewayRefNumForPayout($payout);
        }

        if (empty($bas) === false)
        {
            // raising an alert for RBL payouts for now, to inform the recon team to look into
            // this quickly and reduce the SLA for merchants to see final payout status

            $data = [
                'payout_id'                 => $payout->getId(),
                'account_statement_row'     => $bas->getId(),
            ];

            throw new Exception\LogicException(
                'Failed payout has a corresponding BAS entity. This should be reversed instead, not failed.',
                null,
                [
                    'payout_id'         => $payout->getId(),
                    'failure_reason'    => $ftaFailureReason,
                    'bas_id'            => $bas->getId()
                ]);
        }
    }

    public function reversePayout(Entity $payout,
                                  string $reverseReason = null,
                                  $ftaBankStatusCode = null,
                                  $credit_bas = null,
                                  Reversal\Entity &$reversal = null,
                                  $basReconUpdate = false)
    {
        $payout->setReversalStatusUpdateIntentForMetrics(true);

        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_INITIATED,
            [
                'payout_id' => $payout->getId(),
            ]);

        // Keeping the mutex TTL high while updating the payout to reversed.
        // This is to ensure that the process that is working on the payout
        // resource, releases mutex on the payout only once all entities are
        // saved in the database.
        list($bankAccStmtForReversal, $bankAccStmtForPayout) = $this->mutex->acquireAndRelease(
            'reversal_payout_id_' . $payout->getId(),
            function () use ($payout, $reverseReason, $ftaBankStatusCode, $credit_bas, &$reversal, $basReconUpdate)
            {
                // reloading the payout here to ensure if any other process
                // gets a mutex on payout resource, it gets a fresh copy
                // of payout to work.
                $this->repo->reload($payout);

                if ($payout->isStatusReversed() === true)
                {
                    $this->trace->info(TraceCode::PAYOUT_ALREADY_REVERSED,
                        [
                            'payout_id'      => $payout->getId(),
                            'status'         => $payout->getStatus(),
                            'reverse_reason' => $reverseReason,
                        ]);

                    return [null, null];
                }

                list($reversal, $bankAccStmtForReversal, $bankAccStmtForPayout) = $this->repo->transaction(
                    function() use ($payout, $reverseReason, $ftaBankStatusCode, $credit_bas, $basReconUpdate) {
                        $reversal = (new Reversal\Core)->reverseForPayout($payout);

                        if ($reverseReason !== null)
                        {
                            $payout->setFailureReason($reverseReason);
                        }

                        $payout->setStatusCode($ftaBankStatusCode);

                        $bankAccStmtForReversal = null;
                        $bankAccStmtForPayout = null;
                        if (($payout->isBalanceAccountTypeDirect() === true) and
                            !$basReconUpdate)
                        {
                            list($bankAccStmtForReversal, $bankAccStmtForPayout) = $this->handleReversalTransactionForDirectBanking($reversal, $credit_bas);
                            $this->trace->info(
                                TraceCode::REVERSAL_TRANSACTION_LINKED,
                                [
                                    'payout_id' => $payout->getId(),
                                ]);
                        }

                        $previousStatus = $payout->getStatus();

                        $balance = $payout->balance;

                        if ($balance->getType() === Merchant\Balance\Type::BANKING)
                        {
                            $this->decreaseFreePayoutsConsumedAndUnsetFeeTypeIfApplicable($payout);
                        }

                        // For certain cases like  where a payout is being marked
                        // as reversed  through recon flows(as in RBL), the above
                        // method handleReversalTransactionForDirectBanking updates
                        // the payout status to processed (to indicate the payout
                        // got processed at sometime by setting processed_at,
                        // so when the call returns from above method, we end up
                        // override payout status. In order to ensure status of
                        // payout is reversed in the system, we are setting the
                        // status at the end
                        $payout->setStatus(Status::REVERSED);

                        // Need to keep this here because handlePayoutStatusUpdate needs the correct payout status
                        if ($payout->isBalanceAccountTypeDirect() === true)
                        {
                            (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout, $previousStatus, $reversal);
                        }

                        $this->repo->saveOrFail($payout);

                        return [$reversal, $bankAccStmtForReversal, $bankAccStmtForPayout];
                    });
                return [$bankAccStmtForReversal, $bankAccStmtForPayout];
            },
            self::PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_TRANSACTION_COMPLETED,
            [
                'payout_id' => $payout->getId(),
            ]);

        // send event to ledger in shadow mode for direct acc
        $this->sendExtToPayoutEventToLedger($payout, $bankAccStmtForPayout);

        // send event to ledger in shadow mode for direct acc
        $this->sendExtToReversalEventToLedger($payout, $bankAccStmtForReversal, $reversal);

    }

    public function reversePayoutForHighTpsMerchants(Entity $payout,
                                                     string $reverseReason = null,
                                                     $ftaBankStatusCode = null,
                                                     $credit_bas = null,
                                                     Reversal\Entity &$reversal = null)
    {
        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_INITIATED_HIGH_TPS,
            [
                'payout_id' => $payout->getId(),
            ]);

        // Keeping the mutex TTL high while updating the payout to reversed.
        // This is to ensure that the process that is working on the payout
        // resource, releases mutex on the payout only once all entities are
        // saved in the database.
        $this->mutex->acquireAndRelease(
            'reversal_payout_id_' . $payout->getId(),
            function () use ($payout, $reverseReason, $ftaBankStatusCode, $credit_bas, &$reversal)
            {
                // reloading the payout here to ensure if any other process
                // gets a mutex on payout resource, it gets a fresh copy
                // of payout to work.
                $this->repo->reload($payout);

                if ($payout->isStatusReversed() === true)
                {
                    $this->trace->info(TraceCode::PAYOUT_ALREADY_REVERSED,
                                       [
                                           'payout_id'      => $payout->getId(),
                                           'status'         => $payout->getStatus(),
                                           'reverse_reason' => $reverseReason,
                                       ]);

                    return;
                }

                $reversal = $this->repo->transaction(
                    function() use ($payout, $reverseReason, $ftaBankStatusCode, $credit_bas) {
                        $reversal = (new Reversal\Core)->reverseForPayoutForHighTpsMerchants($payout);

                        $payout->setFailureReason($reverseReason);

                        $payout->setStatusCode($ftaBankStatusCode);

                        $previousStatus = $payout->getStatus();

                        $payout->setStatus(Status::REVERSED);

                        $this->repo->saveOrFail($payout);

                        return $reversal;
                    });
            },
            self::PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    public function processInitiateForBatchSubmittedPayouts(array $merchantIds)
    {
        foreach ($merchantIds as $merchantId)
        {
            $traceInfo = [
                'merchant_id' => $merchantId
            ];

            try
            {
                $this->trace->info(TraceCode::PAYOUT_BATCH_INITIATE_DISPATCH_JOB, $traceInfo);

                BatchPayoutsProcess::dispatch($this->mode, $merchantId);

                $this->trace->info(TraceCode::PAYOUT_BATCH_INITIATE_DISPATCH_COMPLETE, $traceInfo);
            }
            catch (\Throwable $e)
            {
                // If the dispatch fails due to any reason, cron will
                // pick up these payouts again and attempt to dispatch.

                $data = $traceInfo + [ 'message' => $e->getMessage() ];

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_BATCH_INITIATE_DISPATCH_FAILED,
                    $data);
            }
        }
    }

    // will be removed after new error object is released.
    protected function getPublicErrorMessage(
        Entity $payout,
        string $ftaFailureReason = null,
        string $ftaBankStatusCode = null)
    {
        if (empty($ftaBankStatusCode) === true)
        {
            $this->trace->error(
                TraceCode::PAYOUT_ERROR_CODE_MAPPING_BANK_STATUS_REQUIRED,
                [
                    'payout_id'         => $payout->getId(),
                    'failure_reason'    => $ftaFailureReason,
                    'status'            => $payout->getStatus(),
                ]);
        }

        if (empty($ftaFailureReason) === true)
        {
            $ftaFailureReason = ErrorCodeMapping::getErrorMessageFromBankResponseCode($payout, $ftaBankStatusCode);
        }

        return $ftaFailureReason;
    }

    protected function getMerchantPayoutAmount(array $input, Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        if (isset($input[Entity::AMOUNT]) === true)
        {
            $amount = $input[Entity::AMOUNT];
        }
        else
        {
            $merchantBalance = $merchant->primaryBalance->getBalance();

            if ((isset($input[Entity::BUFFER_AMOUNT]) === true) and
                ($merchantBalance < $input[Entity::BUFFER_AMOUNT]))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'merchant balance is less than buffer amount',
                    Entity::BUFFER_AMOUNT,
                    [
                        'merchant_id' => $merchantId,
                        'buffer_amount' => $input[Entity::BUFFER_AMOUNT],
                        'balance'       => $merchantBalance
                    ]);
            }

            $amount = $merchantBalance - ($input[Entity::BUFFER_AMOUNT] ?? 0);

            $amount = ($amount > self::MAX_PAYOUT_AMOUNT) ? self::MAX_PAYOUT_AMOUNT : $amount;
        }

        if ((isset($input[Entity::MIN_AMOUNT]) === true) and
            ($amount < $input[Entity::MIN_AMOUNT]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'amount is less than min amount',
                Entity::MIN_AMOUNT,
                [
                    'merchant_id' => $merchantId,
                    'min_amount'  => $input[Entity::MIN_AMOUNT],
                    'amount'      => $amount
                ]);
        }

        //
        // Modulo will convert the amount into multiples
        // of modulo value
        //
        if (isset($input[Entity::MODULO]) === true)
        {
            $moduloAmount = $amount % $input[Entity::MODULO];

            $amount = $amount - $moduloAmount;
        }

        return $amount;
    }

    protected function getCurrency(array $input): string
    {
        if (isset($input[Entity::CURRENCY]) === true)
        {
            return $input[Entity::CURRENCY];
        }

        return Currency::INR;
    }

    protected function getOnDemandStatus(array $input): string
    {
        return ($input[Entity::TYPE] ?? Entity::DEFAULT);
    }

    public function getProcessor(string $type): Processor\Base
    {
        $processor = __NAMESPACE__ . '\\' . 'Processor';

        $processor .= '\\' . studly_case($type);

        return new $processor();
    }

    protected function postCreationForPayouts(Entity $payout)
    {
        // for va to va transfers, we handle credit internally we won't create FTA
        if ($payout->isVaToVaPayout() === true)
        {
            $this->handleTransferForVaToVaPayouts($payout);
        }
        else
        {
            $this->dispatchFtaInitiate($payout);
        }
    }

    public function handleTransferForVaToVaPayouts(Entity $payout)
    {
        //
        // For payouts with status=(queued, pending, scheduled, rejected, failed, batch_submitted), we don't create any
        // transaction or credit_transfer. We do it later when we actually process that payout.
        //
        if ($payout->isStatusBeforeCreate() === true)
        {
            return;
        }

        $creditTransferRequest = null;

        try
        {
            $creditTransferRequest = $this->buildCreditTransferInputFromPayout($payout);

            $this->trace->info(TraceCode::CREDIT_TRANSFER_FOR_VA_TO_VA_PAYOUT_INITIATE,
                [
                    "credit_transfer_request" => $creditTransferRequest
                ]);

            (new CreditTransfer\Core())->createCreditTransfer($creditTransferRequest);

            // to update payout if the credit transfer got processed synchronously
            $payout->reload();
        }
        catch (\Throwable $throwable)
        {
            $traceInfo = [
                'payout_id'               => $payout->getId(),
                "credit_transfer_request" => $creditTransferRequest,
            ];

            $this->trace->traceException($throwable, null,
                TraceCode::CREDIT_TRANSFER_FOR_VA_TO_VA_PAYOUT_FAILURE,
                $traceInfo
            );

            $this->trace->count(Metric::CREDIT_TRANSFER_FOR_VA_TO_VA_PAYOUT_FAILURE);
        }
    }

    public function updateEntityPostProcessingOfCreditTransfer(CreditTransfer\Entity $creditTransfer)
    {
        $payoutId = $creditTransfer->getSourceEntityId();

        $payout = $this->repo->payout->find($payoutId);

        // Skipped Mutex lock considering its a very rare scenario of credit transfer
        // It happened 400 times in a lifetime
        if ((empty($payout) === false)
            and ($payout->getIsPayoutService() === false))
        {
            if ($creditTransfer->isStatusProcessed() === true)
            {
                $payout->setUtr($creditTransfer->getUtr());

                $payout->setStatus(Status::PROCESSED);

                $this->repo->saveOrFail($payout);

                $this->trace->info(TraceCode::CREDIT_TRANSFER_FOR_VA_TO_VA_PAYOUT_SUCCESS,
                    [
                        'payout_id'          => $payout->getId(),
                        'payout'             => $payout->toArray(),
                        'credit_transfer_id' => $creditTransfer->getId(),
                        'credit_transfer'    => $creditTransfer->toArray(),
                    ]);

                $this->handlePayoutProcessed($payout);
            }

            if ($creditTransfer->isStatusFailed() === true)
            {
                $payout->getValidator()->validateVaToVaPayoutForReversal();

                $this->handlePayoutReversed($payout);
            }
        }
        else
        {
            $this->creditTransferPayoutServiceUpdateClient->UpdateCreditTransferPayoutOnPayoutsService($creditTransfer);
        }
    }

    public function buildCreditTransferInputFromPayout(Entity $payout)
    {
        $creditTransferRequest = [
            CreditTransfer\Entity::AMOUNT                => $payout->getAmount(),
            CreditTransfer\Entity::CURRENCY              => $payout->getCurrency(),
            CreditTransfer\Entity::CHANNEL               => $payout->getChannel(),
            CreditTransfer\Entity::DESCRIPTION           => $payout->getNarration(),
            CreditTransfer\Entity::MODE                  => $payout->getMode(),
            CreditTransfer\Constants::SOURCE_ENTITY_ID   => $payout->getId(),
            CreditTransfer\Constants::SOURCE_ENTITY_TYPE => $payout->getEntityName(),
            CreditTransfer\Entity::PAYER_ACCOUNT         => $payout->bankingAccount->getAccountNumber(),
            CreditTransfer\Entity::PAYER_NAME            => $payout->merchant->getDisplayNameElseName(),
            CreditTransfer\Entity::PAYER_IFSC            => $payout->bankingAccount->getAccountIfsc(),
        ];

        $fundAccount = $payout->fundAccount;

        $fundAccountType = $fundAccount->getAccountType();

        switch ($fundAccountType)
        {
            case FundAccount\Type::BANK_ACCOUNT:
                $creditTransferRequest[CreditTransfer\Constants::PAYEE_DETAILS] = [
                    BankAccount\Entity::ACCOUNT_NUMBER => $fundAccount->account->getAccountNumber(),
                    BankAccount\Entity::IFSC_CODE      => $fundAccount->account->getIfscCode()
                ];
        }

        $creditTransferRequest[CreditTransfer\Entity::PAYEE_ACCOUNT_TYPE] = $fundAccountType;

        return $creditTransferRequest;
    }

    protected function dispatchFtaInitiate(Entity $payout)
    {
        //
        // For payouts with status=(queued, pending, scheduled, rejected, failed, batch_submitted), we don't create any
        // transaction or FTA. We do it later when we actually process that payout.
        //
        if ($payout->isStatusBeforeCreate() === true)
        {
            return;
        }

        // No need to trigger FTA for payout service payouts.
        if ($payout->getIsPayoutService() === true)
        {
            return;
        }

        // After FTA creation the fund account source internally dispatches Fund transfer to FTS
        $isFts = $payout->fundTransferAttempts->first()->getIsFts();

        if ($isFts === true)
        {
            return;
        }

        $ftaId = $payout->fundTransferAttempts->first()->getId();

        $info = [
            'fta_id'    => $ftaId,
            'payout_id' => $payout->getId()
        ];

        try
        {
            $this->trace->info(TraceCode::FTA_DISPATCH_FOR_PAYOUT_INIT, $info);

            FundTransfer::dispatch($this->mode, $ftaId);

            $this->trace->info(TraceCode::FTA_DISPATCH_FOR_PAYOUT_COMPLETE, $info);
        }
        catch (\Throwable $e)
        {
            $data = $info + [ 'message' => $e->getMessage() ];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTA_DISPATCH_FOR_MERCHANT_FAILED,
                $data);

            (new Settlement\SlackNotification)->send(
                'FundTransfer dispatch for merchant failed',
                $data,
                $e,
                1);
        }
    }

    public function updateEntityWithFtsTransferId(Entity $entity, $ftsTransferId)
    {
        if (empty($ftsTransferId) === false)
        {
            $entity->setFTSTransferId($ftsTransferId);

            $this->repo->saveOrFail($entity);
        }
    }

    public function updatePayoutStatusManually(Entity $payout, array $input)
    {
        $status = $input[Entity::STATUS];

        $ftaFailureReason  = $input[Attempt\Constants::FAILURE_REASON] ?? null;

        $ftsFundAccountId = (empty($input[Attempt\Constants::FTS_FUND_ACCOUNT_ID]) === true) ?
            null : trim($input[Attempt\Constants::FTS_FUND_ACCOUNT_ID]) ;

        $ftsAccountType = (empty($input[Attempt\Constants::FTS_ACCOUNT_TYPE]) === true) ?
            null : trim($input[Attempt\Constants::FTS_ACCOUNT_TYPE]) ;

        $payoutValidator = $payout->getValidator();

        $payoutValidator->validatePayoutStatusUpdateManually($payout, $status,$ftsFundAccountId, $ftsAccountType);

        $ftsSourceInformation = [
            Attempt\Constants::FTS_ACCOUNT_TYPE     => $ftsAccountType,
            Attempt\Constants::FTS_FUND_ACCOUNT_ID  => $ftsFundAccountId,
        ];

        $statusCode = null;

        if ((Status::isFailureState($status) === true) and
            (is_null($payout->getStatusCode()) === true) and
            (empty($payout->getFTSTransferId()) === true))
        {
            $statusCode = "FTS_ATTEMPT_CREATE_FAILED";
        }

        switch ($status)
        {
            case Status::PROCESSED:
                $this->handlePayoutProcessed($payout, null, null, $ftsSourceInformation);
                break;

            case Status::REVERSED:
                $ftsStatus = $this->getFTSStatusBasedOnPayoutStatus($payout, $status, $ftsSourceInformation);

                $this->handlePayoutReversed(
                    $payout,
                    $ftaFailureReason,
                    $statusCode,
                    null,
                    $ftsSourceInformation,
                    $ftsStatus);
                break;

            case Status::FAILED:
                $this->handlePayoutFailed($payout, $ftaFailureReason, $statusCode);
                break;

            default:
                $this->trace->warning(
                    TraceCode::UNKNOWN_STATUS_SENT_TO_PAYOUT,
                    $input);
        }

        $payout->reload();

        if ($payout->getIsPayoutService() === false)
        {
            $this->deleteCardMetaDataAndVaultTokenForTerminalStatePayout($status, $payout);
        }

        if ($payout->getIsPayoutService() === true)
        {
            return $this->getAPIModelPayoutFromPayoutService($payout->getId());
        }

        return $payout;
    }

    public function updateFTAOfPayoutManually(Entity $payout, array $input)
    {
        /** @var Attempt\Entity $fta */
        $fta = $payout->fundTransferAttempts()->first();

        if (empty($fta) === true)
        {
            return;
        }

        // if update in payout service side fails the the network call itself will fail and hence won't reach this part of code.
        // This part of code is to update FTA and internal entity which will be deprecated and the route used here is a manual update route.
        // Hence for Payout Service payouts we won't be checking if the below fields were changed in the payout.

        // Only updating fta failure reason if payout failure reason was updated during this request.
        if ((empty($input[Entity::FAILURE_REASON]) === false) and
            (($payout->getFailureReason() !== $fta->getFailureReason()) or
             ($payout->getIsPayoutService() === true)))
        {
            $fta->setFailureReason($payout->getFailureReason());
        }

        // Only updating fta status if payout status was updated during this request.
        if ((empty($input[Entity::STATUS]) === false) and
            (($payout->getStatus() !== $fta->getStatus()) or
             ($payout->getIsPayoutService() === true)))
        {
            $fta->setStatus($input[Entity::STATUS]);
        }

        $this->repo->fund_transfer_attempt->saveOrFail($fta);
    }

    protected function processPendingPayout(Entity $payout, bool $queueFlag): Entity
    {
        $payoutId = $payout->getId();

        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId, $queueFlag)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                /** @var Validator $payoutValidator */
                $payoutValidator = $payout->getValidator();

                $payoutValidator->validateProcessingPendingPayout();

                $payout = $this->getProcessor('fund_account_payout')
                               ->setMerchant($payout->merchant)
                               ->processPendingPayout($payout, $queueFlag);

                if ($payout->getStatus() === Status::CREATED)
                {
                    $this->processLedgerPayout($payout);
                }

                $this->postCreationForPayouts($payout);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    protected function processRejectPayout(Entity $payout): Entity
    {
        $payoutId = $payout->getId();

        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                /** @var Validator $payoutValidator */
                $payoutValidator = $payout->getValidator();

                $payoutValidator->validateRejectPayout();

                $payout = $this->rejectPendingPayout($payout);

                $this->app->events->dispatch('api.payout.rejected', [$payout]);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    protected function rejectPendingPayout(Entity $payout)
    {
        $payout->setStatus(Status::REJECTED);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    protected function getQueuedPayoutsPaginationData()
    {
        $queuedPayoutsPaginationData = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION
            ]);

        return $queuedPayoutsPaginationData;
    }

    protected function updateOffsetForBalance(string $balanceId,
                                              $currentOffset,
                                              $queuedPayoutsPaginationData,
                                              $dispatchedPayoutCount,
                                              $totalQueuedPayouts)
    {
        // If offset is not 0, we shall add it to the redis array for offset values
        if ($currentOffset + Repository::QUEUED_PAYOUTS_FETCH_LIMIT <= $totalQueuedPayouts - $dispatchedPayoutCount)
        {
            $updatedOffset = $currentOffset + Repository::QUEUED_PAYOUTS_FETCH_LIMIT - $dispatchedPayoutCount;

            $queuedPayoutsPaginationData[$balanceId] = $updatedOffset;
        }

        //
        // If offset is 0 (merchant has less than 5000 payouts, then we'll remove the offset value from redis)
        // Very few merchants use queued payouts and even fewer would have more than 5000 queued payouts.
        // It makes no sense in storing the offset 0 for all merchants on every cron run.
        // So, we shall only store it for merchants with more than 0 payouts. Unset it for the rest
        //
        else
        {
            unset($queuedPayoutsPaginationData[$balanceId]);
        }

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION => $queuedPayoutsPaginationData
            ]);
    }

    protected function getFTSStatusBasedOnPayoutStatus(Entity $payout,
                                                       string $status,
                                                       array $ftsSourceInformation = null)
    {
        // Context - For manual routes (for transition from initiated to reversed) we will modify
        // fts status to failed or reversed based on fts info passed
        // and send payout.failed to ledger in such cases
        // This is to avoid sending payout.processed and payout.reversed to ledger
        if ((($payout->getStatus() === Status::INITIATED) or
            ($payout->getStatus() === Status::CREATED)) and
            ($status === Status::REVERSED))
        {
            if (($ftsSourceInformation[Attempt\Constants::FTS_ACCOUNT_TYPE] !== null) and
            ($ftsSourceInformation[Attempt\Constants::FTS_FUND_ACCOUNT_ID] !== null))
            {
                return Status::REVERSED;
            }
            else
            {
                return Status::FAILED;
            }
        }
        return null;
    }

    protected function checkOrUpdateChannelToPayoutAndTransaction(Entity $payout,
                                                                  string $payoutChannel,
                                                                  string $ftsChannel,
                                                                  bool $updateChannel = false)
    {
        $traceInfo = [
            'payout_id'         => $payout->getId(),
            'payoutChannel'     => $payoutChannel,
            'ftsChannel'        => $ftsChannel,
        ];

        $this->trace->info(
            TraceCode::PAYOUT_HAS_DIFFERENT_CHANNEL_AT_FTS,
            $traceInfo);

        if ($payout->isBalanceAccountTypeDirect() === true)
        {
            //
            // For direct payouts, there shouldn't be any mismatch in channel at FTS
            // So this signifies bug in logic, hence raising an alert and failing webhook
            //
            (new Settlement\SlackNotification)->send(
                'FTS sent different channel for ' . $payoutChannel . ' payouts',
                $traceInfo,
                null,
                1,
                'rx_ca_rbl_alerts');

            throw new Exception\LogicException(
                'Different channel passed by FTS for CA payouts',
                null,
                $traceInfo);
        }
        else if ($updateChannel === true)
        {
            $this->trace->info(
                TraceCode::PAYOUT_CHANNEL_CHANGED_USING_FTA_DATA,
                $traceInfo);

            $payout->setChannel($ftsChannel);

            $transaction = $payout->transaction;

            if (empty($transaction) === false)
            {
                $transaction->setChannel($ftsChannel);
                $this->repo->saveOrFail($transaction);
            }
        }
    }

    /**
     * Returns limit from redis key. If empty, falls back to default value of 300.
     *
     * @return int
     */
    protected function getBatchPayoutsFetchLimit()
    {
        $limit = (new AdminService)->getConfigKey(['key' => ConfigKey::BATCH_PAYOUTS_FETCH_LIMIT]);

        if (empty($limit) === true)
        {
            $limit = Repository::BATCH_PAYOUTS_FETCH_LIMIT;
        }

        return $limit;
    }

    protected function shouldHandleRewardForFailedPayout(Entity $payout)
    {
        // this checks if rewards were used for the payout
        if ($payout->getFeeType() !== Transaction\CreditType::REWARD_FEE)
        {
            return false;
        }

        // this check if by any flow other flow credits were reversed, then don't
        // reverse credits again
        $creditTxns = (new Credits\Transaction\Core)->getReverseCreditTransactionsForSource(
            $payout->getId(),
            Constants\Entity::PAYOUT);

        if ($creditTxns->count() > 0)
        {
            return false;
        }

        return true;
    }

    /**
     * @param bool $approved
     * @param Entity $payout
     * @param array $input
     * @return Entity
     */
    public function processActionOnPayout(bool $approved, Entity $payout, array $input): Entity
    {
        $this->trace->info(TraceCode::PAYOUT_WORKFLOW_ACTION_INFO,
            [
                'payout_id' => $payout->getId(),
                'approved'  => $approved,
                'input'     => $input,
            ]);

        if($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                      $payout->getMerchantId()) === true)
        {
            return $this->mutex->acquireAndRelease(
                PayoutConstants::MIGRATION_REDIS_SUFFIX . $payout->getId(),
                function() use ($payout, $approved, $input) {
                    $payout->reload();

                    if ($approved === true)
                    {
                        if ($payout->getIsPayoutService() === true)
                        {
                            $this->payoutWorkflowServiceClient->approvePayoutViaMicroservice(
                                $payout->getId()
                            );
                        }
                        else
                        {
                            return $this->processApprovePayout($payout, $input);
                        }
                    }
                    else
                    {
                        if ($payout->getIsPayoutService() === true)
                        {
                            $this->payoutWorkflowServiceClient->rejectPayoutViaMicroservice(
                                $payout->getId()
                            );
                        }
                        else
                        {
                            return $this->processRejectPayout($payout);
                        }
                    }

                    return $payout->reload();
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
        }
        else
        {
            if ($approved === true)
            {
                return $this->processApprovePayout($payout, $input);
            }
            return $this->processRejectPayout($payout);
        }
    }

    /**
     * @param array $input
     * @param Entity $payout
     * @return Entity
     */
    protected function processApprovePayout(Entity $payout, array $input): Entity
    {
        // setting default queue flag to be true since Queued Payouts is always enabled
        // alongside Payout Workflows till now
        // This check is similar in processWorkflowActionOnPayout function
        // If Logic is changed then it needs to be changed at both the places
        $queueFlag = isset($input[Entity::QUEUE_IF_LOW_BALANCE]) ?
            boolval($input[Entity::QUEUE_IF_LOW_BALANCE]) : true;

        return $this->processPendingPayout($payout, $queueFlag);
    }

    public function updateFreePayoutsConsumedAndGetFeeType(Merchant\Balance\Entity $balance)
    {
        return $this->repo->counter->transaction(
            function() use ($balance)
            {
                return (new CounterHelper)->updateFreePayoutConsumedIfApplicable($balance);
            });
    }

    public function decreaseFreePayoutsConsumedAndUnsetFeeTypeIfApplicable(Entity $payout)
    {
        $shouldUnsetFeeType =
            (new CounterHelper)->decreaseFreePayoutsConsumedIfApplicable($payout, CounterHelper::REVERSAL_OR_FAILURE);

        if ($shouldUnsetFeeType === true)
        {
            $payout->setFeeType(null);
        }
    }

    public function decrementFreePayoutsForPayoutsService(array $input)
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::DECREMENT_FREE_PAYOUT_FOR_PAYOUTS_SERVICE, $input);

        $balance = $this->repo->balance->findOrFailById($input[Entity::BALANCE_ID]);

        try
        {
            if ($balance->getType() === Merchant\Balance\Type::BANKING)
            {
                $resp = (new CounterHelper)->fetchCounterAndDecreaseFreePayoutsConsumed($balance);

                $this->trace->info(TraceCode::DECREMENT_FREE_PAYOUTS_FOR_PAYOUTS_SERVICE_RESPONSE, [
                    'response' => $resp,
                ]);

                return $resp;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::BAD_REQUEST_DECREMENT_FREE_PAYOUTS_FOR_PAYOUTS_SERVICE, [
                Entity::BALANCE_ID => $input[Entity::BALANCE_ID],
            ]);
            throw $e;
        }

        return null;
    }

    public function decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable(string $balanceId, $feeType)
    {
        if ($feeType === Entity::FREE_PAYOUT)
        {
            (new CounterHelper)->decreaseFreePayoutsConsumedInCaseOfTransactionFailure($balanceId);
        }
    }

    public function handleFreePayoutsConsumedInCaseOfFailure(
        $balance, $exception, $feeType)
    {
        $isLedgerReverseShadowEnabled =
            ($balance->merchant->isFeatureEnabled(FeatureConstants::LEDGER_REVERSE_SHADOW) === true);

        $internalErrorCode = (method_exists($exception, 'getError') === true) ?
            optional($exception->getError())->getInternalErrorCode() : null;

        /**
         * Keeping this check here, as for this error Code, decrementing of free payouts is already handled in
         * failPayoutPostLedgerFailure function for merchants enabled on ledger reverse shadow
         */
        if (($isLedgerReverseShadowEnabled === true) and
            ($internalErrorCode === ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING))
        {
            return;
        }

        $this->decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable($balance->getId(), $feeType);
    }

    public function getFreePayoutsAttributes(string $balanceId)
    {
        try
        {
            /** @var Entity $balance */
            $balance = $this->repo->balance->findOrFailById($balanceId);
        }

        catch (\Exception $exception)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INVALID_BALANCE_ID,
                Entity::BALANCE_ID,
                [
                    Entity::BALANCE_ID => $balanceId,
                ]);
        }

        $balanceType = $balance->getType();

        if ($balanceType !== Merchant\Balance\Type::BANKING)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INCORRECT_BALANCE_TYPE,
                Merchant\Balance\Entity::BALANCE_ID,
                [
                    Merchant\Balance\Entity::BALANCE_ID => $balanceId,
                    Merchant\Balance\Entity::TYPE       => $balanceType,
                ]);
        }

        $merchantId = $balance->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($merchant->isFeatureEnabled(FeatureConstants::PAYOUT_SERVICE_ENABLED) &&
            ($balance->isAccountTypeShared() === true))
        {
            return $this->payoutGetApiServiceClient->getFreePayoutAttributesViaMicroservice($balanceId);
        }
        else
        {
            $freePayoutsCount = (new Merchant\Balance\FreePayout)->getFreePayoutsCount($balance);

            $freePayoutsSupportedModes = (new Merchant\Balance\FreePayout)->getFreePayoutsSupportedModes($balance);

            /** @var Counter\Entity $counter */
            $counter = (new Counter\Repository)->getCounterByAccountTypeAndBalanceId(
                $balance->getAccountType(),
                $balanceId);

            $freePayoutsConsumed = ($counter === null) ? 0 : $counter->getFreePayoutsConsumed();

            $response = [
                Merchant\Balance\FreePayout::FREE_PAYOUTS_COUNT           => $freePayoutsCount,
                Counter\Entity::FREE_PAYOUTS_CONSUMED                     => $freePayoutsConsumed,
                Merchant\Balance\FreePayout::FREE_PAYOUTS_SUPPORTED_MODES => $freePayoutsSupportedModes,
            ];

            return $response;
        }
    }

    // Push each request into queue. Worker will pick up
    // and do actual migration. This is done to avoid timeouts
    public function postFreePayoutMigration(array $input)
    {
        $action = $input[EntityConstant::ACTION];

        $totalCount = 0;

        foreach ($input['ids'] as $request)
        {
            $merchantId = $request[Entity::MERCHANT_ID];

            $balanceId = $request[Entity::BALANCE_ID];

            $traceInfo = [
                EntityConstant::ACTION => $action,
                Entity::MERCHANT_ID    => $merchantId,
                Entity::BALANCE_ID     => $balanceId,
            ];

            $this->trace->info(TraceCode::MIGRATE_FREE_PAYOUT_TO_PAYOUTS_SERVICE_DISPATCH_INITIATE, $traceInfo);

            if (($action == "basd_status") or
                ($action == "ps_basd_status") or
                ($action == "ps_basd_create"))
            {
                $request[EntityConstant::ACTION] = $action;

                (new BankingAccountStatement\Details\Core)->handleBasDetailsActions($request);

                $totalCount++;

                continue;
            }

            if ($action == "ps_bas_link")
            {
                $request[EntityConstant::ACTION] = $action;

                (new BankingAccountStatement\Service)->handleBasAdminActions($request);

                $totalCount++;

                continue;
            }

            try
            {
                FreePayoutMigrationForPayoutsService::dispatch($this->mode, $action, $merchantId, $balanceId);

                $totalCount++;
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::MIGRATE_FREE_PAYOUT_TO_PAYOUTS_SERVICE_DISPATCH_FAILED, [
                    Entity::MERCHANT_ID    => $merchantId,
                    EntityConstant::ACTION => $action,
                ]);
            }

            $this->trace->info(TraceCode::MIGRATE_FREE_PAYOUT_TO_PAYOUTS_SERVICE_DISPATCH_COMPLETE, $traceInfo);
        }

        return [
            'total_count' => $totalCount,
        ];
    }

    public function freePayoutMigrationFeatureChecks(string $action, string $merchantId, string $accountType)
    {
        /** @var Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->find($merchantId);

        if (empty($merchant) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_DOES_NOT_EXIST);
        }

        switch ($action)
        {
            case EntityConstant::ENABLE:

                // We can only process free payout migration if ledger_reverse_shadow is enabled.
                if ($accountType ===  Merchant\Balance\AccountType::SHARED && $merchant->isFeatureEnabled(FeatureConstants::LEDGER_REVERSE_SHADOW) === false)
                {
                    $this->trace->error(TraceCode::LEDGER_REVERSE_SHADOW_NOT_ENABLED_FOR_THE_MERCHANT, [
                        Entity::MERCHANT_ID     => $merchant->getId(),
                        EntityConstant::ACTION  => $action,
                    ]);

                    throw new ServerErrorException(
                        'ledger_reverse_shadow feature is not enabled for the merchant',
                        ErrorCode::SERVER_ERROR,
                        [
                            Entity::MERCHANT_ID => $merchant->getId(),
                        ]);
                }

                // If merchant is already migrated to payout service then we don't process it again.
                if ($merchant->isFeatureEnabled(FeatureConstants::PAYOUT_SERVICE_ENABLED) === true)
                {
                    $this->trace->error(TraceCode::PAYOUT_SERVICE_ENABLED_FEATURE_EXISTS, [
                        Entity::MERCHANT_ID     => $merchant->getId(),
                        EntityConstant::ACTION  => $action,
                    ]);

                    throw new ServerErrorException(
                        'payout_service_enabled feature is assigned already to the merchant',
                        ErrorCode::SERVER_ERROR,
                        [
                            Entity::MERCHANT_ID => $merchant->getId(),
                        ]);
                }

                break;

            case EntityConstant::DISABLE:

                if ($accountType ===  Merchant\Balance\AccountType::SHARED && $merchant->isFeatureEnabled(FeatureConstants::LEDGER_REVERSE_SHADOW) === false)
                {
                    $this->trace->error(TraceCode::LEDGER_REVERSE_SHADOW_NOT_ENABLED_FOR_THE_MERCHANT, [
                        Entity::MERCHANT_ID     => $merchant->getId(),
                        EntityConstant::ACTION => $action,
                    ]);

                    throw new ServerErrorException(
                        'ledger_reverse_shadow feature is not assigned to the merchant',
                        ErrorCode::SERVER_ERROR,
                        [
                            Entity::MERCHANT_ID => $merchant->getId(),
                        ]);
                }

                if ($merchant->isFeatureEnabled(FeatureConstants::PAYOUT_SERVICE_ENABLED) === false)
                {
                    $this->trace->error(TraceCode::PAYOUT_SERVICE_NOT_ENABLED_FOR_THE_MERCHANT, [
                        Entity::MERCHANT_ID     => $merchant->getId(),
                        EntityConstant::ACTION => $action,
                    ]);

                    throw new ServerErrorException(
                        'payout_service_enabled feature is not assigned to the merchant',
                        ErrorCode::SERVER_ERROR,
                        [
                            Entity::MERCHANT_ID => $merchant->getId(),
                        ]);
                }

                break;

            default:
                throw new ServerErrorException(
                    "Invalid action for free payout migration",
                    ErrorCode::SERVER_ERROR,
                    [
                        EntityConstant::ACTION => $action,
                    ]
                );
        }
    }

    public function performFreePayoutMigration(string $action,
                                               string $merchantId,
                                               string $balanceId)
    {
        try
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::MERCHANT_FETCH_FAILED, [
                Entity::MERCHANT_ID    => $merchantId,
                EntityConstant::ACTION => $action,
            ]);

            throw $e;
        }

        $mutexResource = sprintf(self::FREE_PAYOUT_MIGRATE_RESOURCE,
                                 $merchantId,
                                 $balanceId,
                                 $this->mode);

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($merchant, $balanceId, $action) {
                return (new Balance\Service)->migrateFreePayout($merchant, $balanceId, $action);
            },
            self::FREE_PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_FREE_PAYOUT_UPDATE_ANOTHER_OPERATION_IN_PROGRESS);
    }

    public function postFreePayoutRollback(array $request)
    {
        $merchantId = $request[Entity::MERCHANT_ID];

        $balanceId = $request[Entity::BALANCE_ID];

        try
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::MERCHANT_FETCH_FAILED, [
                Entity::MERCHANT_ID    => $merchantId,
            ]);

            throw $e;
        }

        /** @var Entity $balance */
        $balance = $this->repo->balance->findOrFailById($balanceId);
        $accountType = $balance->getAccountType();

        $this->freePayoutMigrationFeatureChecks(EntityConstant::DISABLE, $merchant->getId(), $accountType);

        $balance = (new Balance\Service)->getBankingTypeBalanceEntity($balanceId);

        $response = $this->repo->counter->transaction(
            function() use ($balance,
                $merchant,
                $request) {

                $freePayoutsConsumed = $request[Counter\Entity::FREE_PAYOUTS_CONSUMED];

                $freePayoutsConsumedLastResetAt = $request[Counter\Entity::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT];

                // rollback counter
                (new CounterHelper)->rollbackCounter($balance, $freePayoutsConsumed, $freePayoutsConsumedLastResetAt);

                $this->rollbackFreePayoutsCountAndSupportedModes($balance, $request);

                $this->deletePayoutServiceEnabledFeature($merchant->getId());

                return [
                    Entity::BALANCE_ID                => $balance->getId(),
                    EntityConstant::COUNTERS_ROLLBACK => true,
                    EntityConstant::SETTINGS_ROLLBACK => true
                ];

            });

        $this->trace->info(TraceCode::FREE_PAYOUT_ROLLBACK_RESPONSE,
                           [
                               'rollback_response' => $response,
                           ]);

        return $response;
    }

    public function payoutSourceUpdate($input)
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::PAYOUTS_SOURCE_UPDATE, $input);

        $previousStatus        = $input[ENTITY::PREVIOUS_STATUS];
        $expectedCurrentStatus = $input[ENTITY::EXPECTED_CURRENT_STATUS];

        $payout = $this->getAPIModelPayoutFromPayoutService($input[Entity::PAYOUT_ID]);

        // pushing a message in the queue to update the source for payout
        $mode = app('rzp.mode') ? app('rzp.mode') : Constants\Mode::LIVE;

        SourceUpdater::dispatchToQueue($mode, $payout, $previousStatus, $expectedCurrentStatus);

        return [
            'sources_updated' => true,
        ];
    }

    public function statusDetailsSourceUpdate($input)
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::STATUS_DETAILS_SOURCE_UPDATE, $input);

        $payout = $this->getAPIModelPayoutFromPayoutService($input[Entity::PAYOUT_ID]);

        $payout->setAttribute(Entity::SOURCE_DETAILS, $input[Entity::SOURCE_DETAILS]);
        $payout->setAttribute(Entity::STATUS_DETAILS, $input[Entity::STATUS_DETAILS]);

        $sourcesUpdated = (new PayoutsStatusDetailsCore())->statusDetailsSourceUpdate($payout);

        return [
            'sources_updated' => $sourcesUpdated
        ];
    }

    protected function rollbackFreePayoutsCountAndSupportedModes($balance, $request)
    {
        $freePayoutObj = new FreePayout();

        if (isset($request[Balance\FreePayout::FREE_PAYOUTS_COUNT]))
        {
            $freePayoutsCount = $request[Balance\FreePayout::FREE_PAYOUTS_COUNT];

            $freePayoutObj->addNewAttribute($freePayoutsCount,
                                            $balance,
                                            FreePayout::FREE_PAYOUTS_COUNT);
        }

        if (isset($request[Balance\FreePayout::FREE_PAYOUTS_SUPPORTED_MODES]))
        {
            $freePayoutsSupportedModes = $request[Balance\FreePayout::FREE_PAYOUTS_SUPPORTED_MODES];

            $freePayoutObj->addNewAttribute($freePayoutsSupportedModes,
                                            $balance,
                                            FreePayout::FREE_PAYOUTS_SUPPORTED_MODES);
        }
    }

    protected function deletePayoutServiceEnabledFeature($merchantId)
    {
        $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
            EntityConstant::MERCHANT,
            $merchantId,
            Feature\Constants::PAYOUT_SERVICE_ENABLED);

        if (empty($feature))
        {
            $this->trace->error(TraceCode::PAYOUT_SERVICE_NOT_ENABLED_FOR_THE_MERCHANT, [
                Entity::MERCHANT_ID    => $merchantId,
            ]);

            throw new ServerErrorException(
                'payout_service_enabled feature is not assigned to the merchant',
                ErrorCode::SERVER_ERROR,
                [
                    Entity::MERCHANT_ID => $merchantId,
                ]);
        }

        (new Feature\Core)->disablePayoutService($feature);
    }

    public function rejectWorkflowViaWorkflowService(Entity $payout, array $input)
    {
        /** @var $auth BasicAuth */
        $auth = $this->app['basicauth'];

        $input['action'] = Workflow\Service\Adapter\Payout::REJECTED;

        $this->trace->info(TraceCode::PAYOUT_WORKFLOW_ACTION_INFO, [
            'payout_id' => $payout->getId(),
            'action'    => Workflow\Service\Adapter\Payout::REJECTED,
            'input'     => $input,
        ]);

        try
        {
            // Using Self Serve Workflow Feature, Owner role will be able to bulk reject payouts using Admin action
            if ($auth->isProxyAuth() === true && array_key_exists(Entity::BULK_REJECT_AS_OWNER, $input))
            {
                if ($payout->getIsPayoutService() === true)
                {
                    $this->payoutWorkflowServiceClient->rejectPayoutViaMicroservice(
                        $payout->getId()
                    );
                }
                else
                {
                    $this->rejectPendingPayout($payout);
                }

                return $this->workflowService->createDirectAction($payout, $input);
            }


            // Admin / Worker(for scheduled payouts) actions
            // On these auth, one can only reject a workflow
            if ((($auth->isAdminAuth() === true) ||
                    ($auth->isProxyAuth() === false)) and
                    ($auth->isSlackApp() === false) and
                    ($this->isXPartnerApproval() === false))
            {
                // casted to boolval, in case of dashboard request its coming as "1"
                if (boolval($input[Entity::FORCE_REJECT]) === true)
                {
                    if ($payout->getIsPayoutService() === true)
                    {
                        $this->payoutWorkflowServiceClient->rejectPayoutViaMicroservice(
                            $payout->getId()
                        );
                    }
                    else
                    {
                        $this->rejectPendingPayout($payout);
                    }
                }

                return $this->workflowService->createDirectAction($payout, $input);
            }

            // Dashboard user actions
            $actionResponse =  $this->workflowService->createActionOnEntity($payout, $input);

            //tracking slack app related events
            $this->trackPayoutEvent(EventCode::PENDING_PAYOUT_APPROVE_REJECT_ACTION,
                $payout);

            return $actionResponse;
        }
        catch (\Throwable $e)
        {
            //tracking slack app related events
            $this->trackPayoutEvent(EventCode::PENDING_PAYOUT_APPROVE_REJECT_ACTION,
                $payout,
                $e);

            $this->trace->count(Metric::PAYOUT_WORKFLOW_ACTION_FAILED_TOTAL);

            $this->trace->error(TraceCode::PAYOUT_WORKFLOW_SERVICE_ACTION_CREATE_FAILED, [
                'payout_id' => $payout->getId(),
                'action'    => Workflow\Service\Adapter\Payout::REJECTED,
            ]);

            throw $e;
        }
    }

    protected function processApproveActionOnPayoutViaWorkflowServiceForICICICAPayout(string $payout_id, string $merchant_id)
    {
        $optional_input = [];

        // get payout entity from payout_id
        $payout = $this->repo->payout->findByIdAndMerchantId($payout_id, $merchant_id);

        // get owner from merchant id
        $owner = $this->repo->merchant_user->fetchOwnerByMerchantIdAndBankingProduct($merchant_id);

        // get user details from owner_id
        $user = $this->repo->user->getUserFromId($owner->getUserId());

        $userComment = $this->fetchUserCommentFromSettingsEntity($payout) ?? '';

        $optional_input[WorkflowConstants::ACTOR_ID] = $owner->getUserId();
        $optional_input[WorkflowConstants::ACTOR_TYPE] = WorkflowConstants::USER;
        $optional_input[WorkflowConstants::ACTOR_PROPERTY_KEY] = WorkflowConstants::ROLE;
        $optional_input[WorkflowConstants::ACTOR_PROPERTY_VALUE] = WorkflowConstants::OWNER;
        $optional_input[WorkflowConstants::ACTOR_EMAIL] = $user->getEmail();
        $optional_input[WorkflowConstants::ACTOR_NAME] = $user->getName();

        try
        {
            $input = [];

            $input['action'] = Workflow\Service\Adapter\Payout::APPROVED;

            $input['user_comment'] = $userComment;

            $this->trace->info(TraceCode::PAYOUT_WORKFLOW_ACTION_INFO, [
                'payout_id' => $payout->getId(),
                'action'    => Workflow\Service\Adapter\Payout::APPROVED,
                'input'     => $input,
            ]);


            $this->workflowService->createActionOnEntity($payout, $input, $optional_input);

            $this->trace->info(TraceCode::PAYOUT_WORKFLOW_OWNER_APPROVE_SUCCESS_ICICI_CA, [
                'payout_id'     => $payout_id,
                'action'        => WorkflowConstants::APPROVED,
                'merchant_id'   => $merchant_id,
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::PAYOUT_WORKFLOW_OWNER_APPROVE_FAILED_ICICI_CA, [
                'payout_id'     => $payout_id,
                'action'        => WorkflowConstants::APPROVED,
                'merchant_id'   => $merchant_id,
            ]);

            $this->trace->count(Metric::PAYOUT_WORKFLOW_ACTION_FAILED_TOTAL);
        }
    }

    public function approveWorkflowViaWorkflowService(Entity $payout, array $input)
    {
        /** @var $auth BasicAuth */
        $auth = $this->app['basicauth'];

        $input['action'] = Workflow\Service\Adapter\Payout::APPROVED;

        $this->trace->info(TraceCode::PAYOUT_WORKFLOW_ACTION_INFO, [
            'payout_id' => $payout->getId(),
            'action'    => Workflow\Service\Adapter\Payout::APPROVED,
            'input'     => $input,
        ]);

        if ((($auth->isAdminAuth() === true) ||
                ($auth->isProxyAuth() === false)) and
                ($auth->isSlackApp() === false) and
                (!$auth->isAppleWatchApp()) and
                ($this->isXPartnerApproval() === false))
        {
            throw new Exception\BadRequestValidationFailureException('Auth is not proxy for payout approval');
        }

        try
        {
            // Dashboard user actions
            $actionResponse =  $this->workflowService->createActionOnEntity($payout, $input);

            //tracking slack app related events
            $this->trackPayoutEvent(EventCode::PENDING_PAYOUT_APPROVE_REJECT_ACTION,
                $payout);

            return $actionResponse;
        }
        catch (\Throwable $e)
        {
            //tracking slack app related events
            $this->trackPayoutEvent(EventCode::PENDING_PAYOUT_APPROVE_REJECT_ACTION,
                $payout,
                $e);

            $this->trace->count(Metric::PAYOUT_WORKFLOW_ACTION_FAILED_TOTAL);

            $this->trace->error(TraceCode::PAYOUT_WORKFLOW_SERVICE_ACTION_CREATE_FAILED, [
                'payout_id' => $payout->getId(),
                'action'    => Workflow\Service\Adapter\Payout::APPROVED,
            ]);

            throw $e;
        }
    }

    /**
     * Trim payout purpose with leading and trailing spaces
     *
     * @param PaginationEntity $paginationEntity
     */
    public function trimPayoutPurpose(PaginationEntity $paginationEntity)
    {
        $this->trace->info(
            TraceCode::START_PAYOUT_PURPOSE_TRIMMING,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );

        $payouts = $this->repo->payout->fetchPayoutsPurposeToTrim(
            $paginationEntity->getFinalMerchantList(),
            $paginationEntity->getCurrentStartTime(),
            $paginationEntity->getCurrentEndTime(),
            $paginationEntity->getLimit()
        );

        $payoutIds = $payouts->getIds();

        $typeFixedForMerchantIds = [];

        while (count($payouts) > 0)
        {
            foreach ($payouts as $payout)
            {
                try
                {
                    $purposeObj = new Purpose;

                    $merchant = $payout->merchant;

                    $merchantId = $merchant->getId();

                    if (in_array($merchantId, $typeFixedForMerchantIds, true) === false)
                    {
                        $allCustomKeys = $purposeObj->getCustom($merchant);

                        foreach ($allCustomKeys as  $purpose => $type)
                        {
                            if (strlen($purpose) !== strlen(trim($purpose)))
                            {
                                $purposeObj->trimPurpose($merchant, $purpose, $type);

                                $this->trace->info(
                                    TraceCode::PAYOUT_PURPOSE_TRIMMED,
                                    [
                                        'purpose'     => $purpose,
                                        'merchant_id' => $merchant->getId()
                                    ]
                                );
                            }
                        }

                        array_push($typeFixedForMerchantIds, $merchantId);
                    }

                    $purpose = $payout->getPurpose();

                    $trimmedPurpose = trim(str_replace('\n', ' ', $purpose));

                    $payout->setPurpose($trimmedPurpose);

                    $payout->saveOrFail();

                    $this->trace->info(
                        TraceCode::PAYOUT_ENTITY_PURPOSE_TRIMMED,
                        [
                            'payout_id' => $payout->getId(),
                            'old_purpose' => $purpose,
                            'new_purpose' => $trimmedPurpose
                        ]
                    );
                }
                catch (\Throwable $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::ERROR,
                        TraceCode::PAYOUT_ENTITY_PURPOSE_TRIM_FAILED,
                        [
                            'payout_id' => $payout->getId()
                        ]
                    );
                }
            }

            $newPayouts = $this->repo->payout->fetchPayoutsPurposeToTrim(
                $paginationEntity->getFinalMerchantList(),
                $paginationEntity->getCurrentStartTime(),
                $paginationEntity->getCurrentEndTime(),
                $paginationEntity->getLimit()
            );

            $newPayoutIds = $newPayouts->getIds();

            $nonCommonIdsFromLastPayouts = array_diff($newPayoutIds, $payoutIds);

            if ((count($newPayouts) === 0) or
                (count($nonCommonIdsFromLastPayouts) > 0))
            {
                $payoutIds = $newPayoutIds;

                $payouts = $newPayouts;
            }
            else
            {
                $data = [
                    'created_from'  => $paginationEntity->getCurrentStartTime(),
                    'created_till'  => $paginationEntity->getCurrentEndTime()
                ];

                $this->trace->info(
                    TraceCode::PAYOUT_PURPOSE_TRIM_FOR_MERCHANTS_FAILED,
                    $data
                );

                return;
            }
        }

        $this->trace->info(
            TraceCode::PAYOUT_PURPOSE_TRIMMED_FOR_MERCHANTS,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );
    }

    // ============================= PAYOUT SERVICE =============================

    public function createPayoutEntry(array $input)
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::PAYOUT_SERVICE_CREATE, $input);

        // Find merchant using merchant id and set merchant in get processor
        $merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);


        $processor = $this->getProcessor('fund_account_payout')
                    ->setMerchant($merchant);

        if (isset($input[Entity::IS_INTERNAL]) == true)
        {
            $processor->setInternal(true);
        }

       return $processor->createPayoutEntry($input);
    }

    public function fetchPayoutsDetailsForDcc(array $input) : array
    {
        $this->trace->info(
            TraceCode::DCC_PAYOUT_DATA_FETCH_REQUEST,
            [
                'input' => $input,
            ]);

        $payouts = $this->repo->payout->findMany($input[Entity::PAYOUT_IDS])->all();

        $payoutStatusDetails = $this->repo->payouts_status_details->fetchPayoutStatusDetailsByPayoutIds(
            $input[Entity::PAYOUT_IDS])->all();

        $reversals = $this->repo->reversal->findReversalForPayouts($input[Entity::PAYOUT_IDS])->all();

        $this->trace->info(
            TraceCode::DCC_PAYOUT_DETAILS_ENTITY_COUNTS,
            [
                'payouts'             => count($payouts),
                'payoutStatusDetails' => count($payoutStatusDetails),
                'reversals'           => count($reversals)
            ]);

        $mergedPayoutDetails = (new DataConsistencyChecker)->mergePayoutDetails(
            $payouts,
            $payoutStatusDetails,
            $reversals
        );

        $response = [
            'payout_details' => $mergedPayoutDetails
        ];

        $this->trace->info(
            TraceCode::DCC_PAYOUT_DATA_FETCH_RESPONSE,
            [
                'response' => $response
            ]);

        return $response;
    }

    public function initiatePayoutsConsistencyCheck()
    {
        return $this->payoutServiceDataConsistencyCheckerClient->initiateDataConsistencyChecker();
    }

    public function createWorkflowForPayout(array $input)
    {
        // Find merchant using merchant id and set merchant in get processor
        $merchant = $this->app['basicauth']->getMerchant();

        return $this->getProcessor('fund_account_payout')
                    ->setMerchant($merchant)
                    ->createWorkflowPayoutEntry($input);
    }

    public function createFTAForPayoutService(string $payoutId)
    {
        (new Validator)->validateInput(Validator::PAYOUT_SERVICE_FTS_CREATE,
            [
                Entity::ID => $payoutId
            ]);

        $payout = $this->getAPIModelPayoutFromPayoutService($payoutId);

        return $this->getProcessor('fund_account_payout')
                    ->createFTAForPayoutService($payout);
    }

    public function getAPIModelPayoutFromPayoutService(string $id)
    {
        $this->trace->info(
            TraceCode::FETCH_PAYOUT_SERVICE_PAYOUT,
            [
                Entity::PAYOUT_ID => $id
            ]);

        $payoutServicePayouts = $this->repo->payout->getPayoutServicePayout($id);

        if (count($payoutServicePayouts) === 0)
        {
            return null;
        }

        $payout = new Entity;

        $psPayout = $payoutServicePayouts[0];

        $merchantId = $psPayout->merchant_id;
        $merchant = null;

        if (isset($merchantId) === true)
        {
            $merchant = (new Merchant\Repository)->findOrFail($merchantId);
        }

        $payout->merchant()->associate($merchant);

        $payout->setIsPayoutService(1);
        $payout->setAmount($psPayout->amount);
        $payout->setBalanceId($psPayout->balance_id);
        $payout->setCancellationUserId($psPayout->cancellation_user_id);
        $payout->setChannel($psPayout->channel);
        $payout->setCreatedAt($psPayout->created_at);
        $payout->setCurrency($psPayout->currency);
        $payout->setFailureReason($psPayout->failure_reason);
        $payout->setFeeType($psPayout->fee_type);
        $payout->setFees($psPayout->fees);
        $payout->setFtsTransferId($psPayout->fts_transfer_id);
        $payout->setFundAccountId($psPayout->fund_account_id);
        $payout->setId($psPayout->id);
        $payout->setIdempotencyKey($psPayout->idempotency_key);
        $payout->setMerchantId($psPayout->merchant_id);
        $payout->setMethod($psPayout->method);
        $payout->setMode($psPayout->mode);
        $payout->setNarration($psPayout->narration);
        $payout->setOnHoldAt($psPayout->on_hold_at);
        $payout->setRawAttribute(Entity::ORIGIN, $psPayout->origin);
        $payout->setPayoutLinkId($psPayout->payout_link_id);
        $payout->setPricingRuleId($psPayout->pricing_rule_id);
        $payout->setQueuedAt($psPayout->queued_at);
        $payout->setQueuedReason($psPayout->queued_reason);
        $payout->setReferenceId($psPayout->reference_id);
        $payout->setRegisteredName($psPayout->registered_name);
        $payout->setRemarks($psPayout->remarks);
        $payout->setScheduledAt($psPayout->scheduled_at);
        $payout->setRawAttribute(Entity::STATUS, $psPayout->status);
        $payout->setStatusCode($psPayout->status_code);
        $payout->setTax($psPayout->tax);
        $payout->setUpdatedAt($psPayout->updated_at);
        $payout->setUserId($psPayout->user_id);
        $payout->setUtr($psPayout->utr);
        //$payout->setWorkflowFeature($psPayout->workflow_feature);
        $payout->setStatusDetailsId($psPayout->status_details_id);
        //$payout->setScheduledOn($psPayout->scheduled_on);

        // Directly calling setRawAttribute to avoid mutators and conversions.
        $payout->setRawAttribute(Entity::NOTES, $psPayout->notes);
        $payout->setRawAttribute(Entity::ORIGIN, $psPayout->origin);

        if (empty($psPayout->purpose) === false)
        {
            $payout->setPurpose($psPayout->purpose);
        }

        if (empty($psPayout->purpose_type) === false)
        {
            $payout->setPurposeType($psPayout->purpose_type);
        }

        if (empty($psPayout->batch_id) === false)
        {
            $payout->setBatchId($psPayout->batch_id);
        }

        if (empty($psPayout->transaction_id) === false)
        {
            $txn = new Transaction\Entity;
            $txn->setId($psPayout->transaction_id);
            $txn->setEntityId($payout->getId());
            $txn->setType('payout');
            $payout->transaction()->associate($txn);
            $payout->unsetRelation('transaction');
        }

        (new DualWrite\PayoutLogs)->dualWritePSPayoutLogs($payout);

        // This is need to showcase $payout as freshly fetched entity and not like a variable on which many
        // setters are called. After doing this isDirty will give false.
        $payout->syncOriginal();

        $payout->setConnection($this->mode);

        $this->trace->info(
            TraceCode::FETCH_PAYOUT_SERVICE_PAYOUT_SUCCESS,
            [
                'payout' => $payout->toArray()
            ]);

        return $payout;
    }

    public function getAPIModelIdempotencyKeyFromPayoutService(string $idempotencyKey,
                                                               string $merchantId)
    {
        $this->trace->info(
            TraceCode::FETCH_PAYOUT_SERVICE_IDEMPOTENCY_KEY,
            [
                Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                Entity::MERCHANT_ID     => $merchantId,
            ]);

        $payoutServiceIdempotencyKeys = $this->repo->payout->getPayoutServiceIdempotencyKey($idempotencyKey,
                                                                                           $merchantId);

        if (empty($payoutServiceIdempotencyKeys) === true)
        {
            return null;
        }

        $payoutServiceIdempotencyKey =  $payoutServiceIdempotencyKeys[0];

        $idempotencyKey = new IdempotencyKey\Entity;

        $idempotencyKey->setId($payoutServiceIdempotencyKey->id);
        $idempotencyKey->setRequestHash($payoutServiceIdempotencyKey->request_hash);
        $idempotencyKey->setMerchantId($payoutServiceIdempotencyKey->merchant_id);
        $idempotencyKey->setSourceType($payoutServiceIdempotencyKey->source_type);
        $idempotencyKey->setCreatedAt($payoutServiceIdempotencyKey->created_at);
        $idempotencyKey->setUpdatedAt($payoutServiceIdempotencyKey->updated_at);

        if (empty($payoutServiceIdempotencyKey->source_id) === false)
        {
            $idempotencyKey->setSourceId($payoutServiceIdempotencyKey->source_id);
        }

        // This is need to showcase $idempotencyKey as freshly fetched entity and not like a variable on which many
        // setters are called. After doing this isDirty will give false.
        $idempotencyKey->syncOriginal();

        $idempotencyKey->setConnection($this->mode);

        $this->trace->info(
            TraceCode::FETCH_PAYOUT_SERVICE_IDEMPOTENCY_KEY_SUCCESS,
            [
                'payout' => $idempotencyKey->toArray(),
            ]);

        return $idempotencyKey;
    }

    public function createPayoutServiceTransaction(array $input)
    {
        (new Validator)->validateInput(Validator::PAYOUT_SERVICE_TRANSACTION_CREATE, $input);

        return $this->getProcessor('fund_account_payout')
                    ->createPayoutServiceTransaction($input);
    }

    public function deductCreditsViaPayoutService(array $input)
    {
        (new Validator)->validateInput(Validator::DEDUCT_CREDITS_VIA_PAYOUT_SERVICE, $input);

        return $this->getProcessor('fund_account_payout')
                    ->deductCreditsViaPayoutService($input);
    }

    public function fetchPricingInfoForPayoutService(array $input)
    {
        return $this->getProcessor('fund_account_payout')
                    ->fetchPricingInfoForPayoutService($input);
    }

    public function reversePayoutService(Entity $payout,
                                         string $reverseReason = null,
                                         $ftaBankStatusCode = null,
                                         Reversal\Entity &$reversal = null,
                                         string $ftaStatus = null,
                                         array $ftsSourceAccountInformation = [])
    {
        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_INITIATED,
            [
                'payout_id' => $payout->getId(),
            ]);

        $app = App::getFacadeRoot();

        $this->mutex = $app['api.mutex'];

        // Keeping the mutex TTL high while updating the payout to reversed.
        // This is to ensure that the process that is working on the payout
        // resource, releases mutex on the payout only once all entities are
        // saved in the database.
        $this->mutex->acquireAndRelease(
            'reversal_payout_id_' . $payout->getId(),
            function () use ($payout, $reverseReason, $ftaBankStatusCode, &$reversal, $ftsSourceAccountInformation, $ftaStatus) {

                $payout->reload();

                if ($payout->isStatusReversed() === true) {
                    $this->trace->info(
                        TraceCode::PAYOUT_ALREADY_REVERSED,
                        [
                            'payout_id' => $payout->getId(),
                            'status' => $payout->getStatus(),
                            'reverse_reason' => $reverseReason,
                        ]);

                    return;
                }

                $this->repo->transaction(
                    function () use ($payout, $reverseReason, $ftaBankStatusCode, $ftsSourceAccountInformation, $ftaStatus) {
                        $reversalRequest = [
                            'failure_reason' => $reverseReason,
                        ];

                        $payout->setFailureReason($reverseReason);

                        $payout->setStatusCode($ftaBankStatusCode);

                        $ftsInfo = [
                            Constants\Entity::FTS_STATUS => $ftaStatus
                        ];

                        // error will be handled by service
                        $response = $this->payoutStatusServiceClient->updatePayoutStatusViaFTS(
                            $payout->getId(),
                            Status::REVERSED,
                            $reverseReason,
                            "",
                            $ftsInfo + $ftsSourceAccountInformation);

                        $balance = $payout->balance;

                        if ($balance->getType() === Merchant\Balance\Type::BANKING)
                        {
                            $this->decreaseFreePayoutsConsumedAndUnsetFeeTypeIfApplicable($payout);
                        }

                        //$previousStatus = $payout->getStatus();

                        // For certain cases like  where a payout is being marked
                        // as reversed  through recon flows(as in RBL), the above
                        // method handleReversalTransactionForDirectBanking updates
                        // the payout status to processed (to indicate the payout
                        // got processed at sometime by setting processed_at,
                        // so when the call returns from above method, we end up
                        // override payout status. In order to ensure status of
                        // payout is reversed in the system, we are setting the
                        // status at the end
                        //$payout->setStatus(Status::REVERSED);

                        //$reversal = $this->repo->reversal->findReversalForPayout($payout->getId());

                        // Need to keep this here because handlePayoutStatusUpdate needs the correct payout status
                        //if ($payout->isBalanceAccountTypeDirect() === true) {
                        //    (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout, $previousStatus, $reversal);
                        //}

                        //$this->repo->saveOrFail($payout);

                        //(new PayoutsStatusDetailsCore())->create($payout);

                        //return $reversal;
                    });
            },
            self::PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    public function updateStatusAfterFtaInitiatedForPayoutService(Entity $payout)
    {
        $payout->setStatus(Status::INITIATED);

        $this->repo->saveOrFail($payout);
    }

    public function updateWithDetailsBeforeFtaReconForPayoutService(Entity $payout, array $ftaData = [])
    {
        try
        {
            $input = [
                Entity::FAILURE_REASON              => $ftaData[Attempt\Constants::FAILURE_REASON] ?? null,
                Entity::REMARKS                     => $ftaData[Attempt\Constants::REMARKS] ?? null,
                Attempt\Entity::FUND_TRANSFER_ID    => $ftaData[Attempt\Entity::FTS_TRANSFER_ID] ?? null,
                Attempt\Constants::BENEFICIARY_NAME => $ftaData[Attempt\Constants::BENEFICIARY_NAME] ?? null,
                Attempt\Entity::BANK_STATUS_CODE    => $ftaData[Attempt\Entity::BANK_STATUS_CODE] ?? null,
                Attempt\Constants::FTA_STATUS       => $ftaData[Attempt\Constants::FTA_STATUS] ?? null,
                Attempt\Entity::CMS_REF_NO          => $ftaData[Attempt\Entity::CMS_REF_NO] ?? null,
                Attempt\Entity::GATEWAY_REF_NO      => $ftaData[Attempt\Entity::GATEWAY_REF_NO] ?? null,
                Entity::STATUS_DETAILS              => [
                    'beneficiary_bank'      => $payout->provideBeneBankName() ?? 'beneficiary bank',
                    'processed_by_time'     => $ftaData[Attempt\Entity::STATUS_DETAILS][Attempt\Entity::PARAMETERS][Attempt\Constants::PROCESSED_BY_TIME] ?? null,
                    'reason'                => $ftaData[Attempt\Entity::STATUS_DETAILS][Attempt\Entity::REASON] ?? '',
                ]
            ];

            //
            // For VPA type, we always set it to UPI only
            // at build and we don't take the mode from FTA.
            //
            // Also, we don't want to override the payout's mode if it's already set.
            //
            if ((empty($ftaData[Attempt\Constants::VPA_ID]) === true) and
                ($payout->getMode() === null))
            {
                $input[Entity::MODE] = $ftaData[Attempt\Constants::MODE];
            }

            // we want to override return UTR only if there is no value for UTR before
            // since return_utr column has a unique constraint, so checking for empty
            // value.
            if (empty($payout->getReturnUtr()) === true)
            {
                if (empty($ftaData[Entity::RETURN_UTR]) === false)
                {
                    $input[Entity::RETURN_UTR] = $ftaData[Entity::RETURN_UTR];
                }
            }

            $initialChannel = $payout->getChannel();

            $updatedChannel = $ftaData[Attempt\Constants::CHANNEL] ?? null;

            if (($updatedChannel !== null) and
                ($initialChannel !== $updatedChannel))
            {
                $this->checkOrUpdateChannelToPayoutAndTransaction($payout, $initialChannel, $updatedChannel);

                $input[Entity::CHANNEL] = $updatedChannel;
            }

            $input[Entity::UTR] = $ftaData[Attempt\Constants::UTR];

            $this->payoutDetailsServiceClient->updatePayoutDetailsViaFTS($payout, $input);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_UPDATE_AFTER_FTA_RECON_SERVICE_DATA_SYNC_FAILED,
                [
                    'payout_id' => $payout->getId()
                ]);

            throw $exception;
        }
    }

    public function handlePayoutProcessedForPayoutService(
        Entity $payout,
        string $ftaStatus = null,
        array  $ftsSourceAccountInformation = [])
    {
        $ftsInfo = [
            Constants\Entity::FTS_STATUS => $ftaStatus
        ];

        $this->payoutStatusServiceClient->updatePayoutStatusViaFTS(
            $payout->getId(),
            Status::PROCESSED,
            "",
            "",
            $ftsInfo + $ftsSourceAccountInformation);

        $merchant = $payout->merchant;
        if (empty($merchant) === false)
        {
            if ($payout->isBalanceAccountTypeDirect() === true)
            {
                $this->app['x-segment']->sendEventToSegment(SegmentEvent::CA_PAYOUT_PROCESSED, $merchant);
            }
        }

    }

    public function handlePayoutReversedForPayoutService(Entity $payout,
                                                         string $ftaFailureReason = null,
                                                         string $ftaBankStatusCode = null,
                                                         Reversal\Entity &$reversal = null,
                                                         string $ftaStatus = null,
                                                         array $ftsSourceAccountInformation = [])
    {
        $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_REVERSE_REQUEST,
            [
                'payout_id'      => $payout->getId(),
                'failure_reason' => $ftaFailureReason,
            ]);

        // webhook fired via payout service
        $this->reversePayoutService($payout,
                                    $ftaFailureReason,
                                    $ftaBankStatusCode,
                                    $reversal,
                                    $ftaStatus,
                                    $ftsSourceAccountInformation);
    }

    public function checkIfBeneBankIsDown(Entity $payout)
    {
        // This is to avoid using on hold payouts feature for Non-IMPS feature
        if ($payout->getMode() !== Mode::IMPS)
        {
            return false;
        }

        $beneIfsc = substr($payout->fundAccount->account->getIfscCode(), 0, 4);

        $beneBankStatus = self::DEFAULT_BENE_BANK_STATUS;

        $eventConfigFromFTS = (new Admin\Service)->getConfigKey([
            'key' => Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT
        ]);

        if(isset($eventConfigFromFTS[self::BENEFICIARY]) === true)
        {
            if (in_array($beneIfsc, array_keys($eventConfigFromFTS[self::BENEFICIARY]), true) === true)
            {
                $beneBankStatus = $eventConfigFromFTS[self::BENEFICIARY][$beneIfsc]['status'];
            }
        }

        if ($beneBankStatus === self::BENE_BANK_DOWNTIME_STARTED)
        {
            return true;
        }

        return false;
    }

    public function getPartnerBankStatus()
    {
        $redis = $this->app['redis'];

        $result = $redis->hgetall(Core::PARTNER_BANK_HEALTH_REDIS_KEY);

        $partnerBankStatus = [];

        foreach ($result as $key => $value)
        {
            $value = json_decode($value);

            $partnerBankStatusValue[PayoutConstants::ACCOUNT_TYPE] = $value->account_type;

            $partnerBankStatusValue[PayoutConstants::CHANNEL] = $value->channel;

            $partnerBankStatusValue[PayoutConstants::MODE] = $value->mode;

            $partnerBankStatusValue[PayoutConstants::STATUS] = $value->status;

            $partnerBankStatus[$key] = $partnerBankStatusValue;
        }

        return $partnerBankStatus;
    }

    public function checkIfPartnerBankIsDown(Entity $payout)
    {
        $redis = $this->app['redis'];
        $merchantId = $payout->merchant->getId();

        $accountType = $payout->balance->getAccountType();

        $mode = $payout->getMode();

        $channel =  $payout->getChannel();

        $configKey = $this->getPartnerBankHealthConfigKey($accountType, $mode, $channel);

        $keyValue = json_decode($redis->hget(self::PARTNER_BANK_HEALTH_REDIS_KEY, $configKey));

        // this logic is currently only for direct account merchants
        if (($keyValue != null) &&
            ($keyValue->status === Events::STATUS_DOWNTIME)) {

            // if in exclude merchants list. We don't hold payouts
            if (($keyValue->exclude_merchants != null) &&
                (in_array($merchantId, $keyValue->exclude_merchants))) {
                return false;
            }

            if ($keyValue->include_merchants[0] === "ALL") {
                return true;
            }
        }
        return false;
    }

    public function processEventNotificationFromFts(array $input)
    {
        try
        {
            switch ($input['payload']['source'])
            {
                case self::BENEFICIARY:

                    $beneBankIfsc = $input['payload']['instrument']['bank'];

                    $status = $input['payload']['status'];

                    (new Validator)->validateBeneStatusReceivedFromFts($status);

                    $this->mutex->acquireAndRelease(
                        Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT,
                        function () use ($beneBankIfsc, $status, $input)
                        {
                            $eventConfigFromFTS = (new Admin\Service)->getConfigKey([
                                                                                        'key' => Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT
                                                                                    ]);

                            $this->trace->info(
                                TraceCode::BENE_BANK_EVENT_NOTIFICATION_RECEIVED,
                                [
                                    'bank' => $beneBankIfsc,
                                    'status' => $status,
                                    'downtime_id' => $input['payload']['id'],
                                ]);

                            if ($status === 'resolved')
                            {
                                if (in_array($beneBankIfsc, array_keys($eventConfigFromFTS[self::BENEFICIARY]), true) === true)
                                {
                                    unset($eventConfigFromFTS[self::BENEFICIARY][$beneBankIfsc]);
                                }
                            }
                            else
                            {
                                $eventConfigFromFTS[self::BENEFICIARY][$beneBankIfsc] = array('status' => $status);
                            }

                            (new Admin\Service)->setConfigKeys(
                                [Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $eventConfigFromFTS]);

                            $this->trace->info(
                                TraceCode::BENE_BANK_EVENT_NOTIFICATION_CONFIG_UPDATE_SUCCESS,
                                [
                                    'bene_bank_redis_config' => $eventConfigFromFTS,
                                ]);
                        },
                        self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                        ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS,
                        2);

                    $this->payoutServiceBeneEventUpdateClient->processBeneEventUpdateViaMicroservice($input);
                    break;

                case Events::PARTNER_BANK_HEALTH:
                    $this->setEventConfigForPartBankDowntime($input['payload']);
                    break;

                case Events::FAIL_FAST_HEALTH:
                case Events::DOWNTIME:

                    $serviceInstance = new \RZP\Models\PartnerBankHealth\Service();

                    $serviceInstance->processStatusUpdateFromFTS($input['payload']);

                    break;
                default:
                    throw new Exception\LogicException("Not a valid source : " . $input['payload']['source']);
            }
        }
        catch (\Throwable $exception)
        {
            if (($input['payload']['source'] === self::BENEFICIARY) ||
                ($input['payload']['source'] === Events::PARTNER_BANK_HEALTH))
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::EVENT_NOTIFICATION_CONFIG_UPDATE_FAILED,
                    [
                        'input' => $input,
                    ]);
                $operation = 'Event for uptime downtime config update failed';

                (new SlackNotification)->send($operation, $input, null, 1, 'x-payouts-core-alerts');
            }

            if ($input['payload']['source'] === Events::FAIL_FAST_HEALTH or
                $input['payload']['source'] === Events::DOWNTIME)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::PARTNER_BANK_HEALTH_UPDATE_PROCESSING_FAILED,
                    $input
                );

                throw $exception;
            }

        }
    }

    private function setEventConfigForPartBankDowntime($payload) {

        (new Validator())->validatePartnerBankHealthNotificationFromFTS($payload);

        $redis = $this->app['redis'];

        $channel = $payload['channel'];

        $mode = $payload['mode'];

        $accountType = $payload['account_type'];

        $configKey = $this->getPartnerBankHealthConfigKey($accountType, $mode, $channel);

        $redis->hset(self::PARTNER_BANK_HEALTH_REDIS_KEY, $configKey, json_encode($payload));

        $this->trace->info(
            TraceCode::PARTNER_BANK_HEALTH_NOTIFICATION_SUCCESSFUL,
            [
                'payload'   => $payload,
                'key'       =>  $redis->hgetall(self::PARTNER_BANK_HEALTH_REDIS_KEY),
                'configKey' => $configKey,
            ]);
    }

    public function initiateScheduledPayoutsViaPayoutService($input)
    {
        return $this->payoutScheduledServiceClient->processSchedulePayoutViaMicroservice($input);
    }

    public function getScheduleTimeSlotsViaPayoutService()
    {
        try
        {
          return  $this->payoutGetTimeSlotsForDashboardServiceClient->getScheduleTimeSlotsViaMicroservice();
        }
        catch (\Exception $exception)
        {
            throw new Exception\BadRequestValidationFailureException('Schedule Timeslot via Microservices Failed.');
        }
    }

    public function retryPayoutsOnPayoutService($input)
    {
        return $this->payoutRetryServiceClient->retryPayoutViaMicroservice($input);
    }

    public function getFetchWorkflowSummary($skipFetchFromWfs = false, $configType = Adapter\Constants::PAYOUT_APPROVAL_TYPE)
    {
        // if the flag $skipFetchFromWfs is set to false then we only fetch the workflow summary from the api db's workflow tables. If set true we fetch from Workflow service as well
        if (($skipFetchFromWfs === false) and
            ($this->isWorkflowServiceEnabled() === true))
        {
            return (new WorkflowConfigService)->getConfigByType($configType, $this->merchant->getId());
        }

        $permissionId = $this->repo
            ->permission
            ->retrieveIdsByNamesAndOrg(Permission\Name::CREATE_PAYOUT, Org\Entity::RAZORPAY_ORG_ID)
            ->first();

        $workflowRules = $this->repo
            ->workflow_payout_amount_rules
            ->fetchBankingWorkflowSummaryForPermissionId($permissionId, $this->merchant->getId());

        $data = [];

        foreach ($workflowRules as $wfRule)
        {
            $wfRuleData = $wfRule->toArray();

            $hasWorkflow = (empty($wfRuleData['workflow_id']) === false);

            $data[] = array_only($wfRuleData, ['min_amount', 'max_amount', 'workflow_id']) + [
                    'has_workflow' => $hasWorkflow,
                    'steps'        => Entity::serializeWorkflowSteps($wfRuleData['workflow']['steps'] ?? []),
                ];
        }

        return $data;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isWorkflowServiceEnabled(): bool
    {
        $isBlacklistedMerchant = $this->merchant->isFeatureEnabled(FeatureConstants::BLOCKLIST_FOR_WORKFLOW_SERVICE);

        return $isBlacklistedMerchant === false;
    }

    public function updatePayoutEntry($payoutId, $input) {

        $this->trace->info(TraceCode::UPDATE_PAYOUT_INPUT, [
                'payout_id' => $payoutId,
                'input'     => $input,
            ]);

        try {
            $merchantId = $input[Entity::MERCHANT_ID];

            $updates = $this->filterPayoutUpdateFields($input);
            $this->trace->info(TraceCode::UPDATE_PAYOUT_INPUT, [
                'payout_id' => $payoutId,
                'updates' => $updates,
            ]);

            (new Repository())->updatePayout($payoutId, $merchantId, $updates);

            return [
                Entity::STATUS => "SUCCESS",
                Entity::ERROR  => NULL,
            ];

        } catch (\Exception $e) {

            $this->trace->error(TraceCode::UPDATE_PAYOUT_INPUT_FAILURE, [
                'payout_id' => $payoutId,
                'message'      => $e->getMessage()
            ]);

            return [
                Entity::STATUS => "FAIL",
                Entity::ERROR  => $e->getMessage(),
            ];
        }

    }

    public function filterPayoutUpdateFields($input)
    {
        $updatableFields = [
            Entity::STATUS => true
        ];

        foreach ($input as $key => $item) {
            if (isset($updatableFields[$key]) == false) {
                unset($input[$key]);
            }
        }

        return $input;
    }

    public function dispatchPendingPayoutApprovalPushNotification($pendingPayout)
    {
        if(empty($pendingPayout)) {
            return;
        }

        $notificationData = array(
            'ownerId'       => $pendingPayout['merchant_id'],
            'ownerType'     => 'merchant',
            'count'         => $pendingPayout['total_count'],
            'amount'        => amount_format_IN($pendingPayout['amount_total']),
            'identityList'  => [$pendingPayout['user_id']],
            'tags'          => array(
                'payoutIds'             => $pendingPayout['payoutIds'],
                'merchantId'            => $pendingPayout['merchant_id'],
                'userId'                => $pendingPayout['user_id'],
                'payoutCount'           => $pendingPayout['total_count'],
                'email'                 => $pendingPayout['email'],
                'amount'                => amount_format_IN($pendingPayout['amount_total']),
                'notificationPurpose'   => 'bulkaction',
                'wzrk_dl'               => 'xmobile://payouts?status=pending&pending_on_roles=' . $pendingPayout['role']
            ),
            'tagGroup'      => 'bulkaction',
        );

        $pushNotification = new PendingApprovalsPN($notificationData);
        $pushNotification->send();

        $this->trace->info(TraceCode::PUSH_NOTIFICATION_DISPATCHED_FOR_PENDING_PAYOUTS, [$notificationData]);
    }

    public function getPendingPayoutsDataAndDispatchEvents($approverList)
    {
        $pendingPayoutsCount = 0;

        $response = ['reminderEventCount' => $pendingPayoutsCount];

        if (empty($approverList)) {
            return $response;
        }

        //User wise grouping the merchant - user information.
        // We will be sending separate emails to a user for different merchants on whom user has payouts awaiting their approval.
        $dataGroupedByUserId = [];
        foreach ($approverList as  $approver){
            $dataGroupedByUserId[$approver['user_id']][] = $approver;
        }

        //picking a user one by one
        foreach ($dataGroupedByUserId as $userData) {
            //Merchant wise grouping the information for the picked up user.
            $dataGroupedByMerchantId = [];
            foreach ($userData as  $user){
                $dataGroupedByMerchantId[$user['merchant_id']][] = $user;
            }

            //Picking information specific to the selected merchant-user combination.
            foreach ($dataGroupedByMerchantId as $merchantId => $data) {
                $input = [
                    'user_id'       => $data[0]['user_id'],
                    'merchant_id'   => $data[0]['merchant_id'],
                    'email'         => $data[0]['email'],
                    'name'          => $data[0]['name'],
                    'business_name' => $data[0]['business_name'],
                    'role'          => $data[0]['role'],
                    'amount_total'  => $data[0]['payout_total'],
                    'total_count'   => $data[0]['payout_count'],
                    'data'          => [],
                    'payoutIds'     => [],
                ];

                $startAt = millitime();

                //Fetching top 10 pending payouts in chronological order for selected merchant-user combination
                $payouts = $this->repo->payout->fetchNPendingPayoutsToDisplay($merchantId, $input['role']);

                $this->trace->info(TraceCode::PENDING_APPROVAL_REMINDER_PAYOUTS_QUERY_DURATION, [
                    'query_execution_time' => millitime() - $startAt,
                    'merchant_user_data' => $input,
                    'payouts_data' => $payouts
                ]);

                $eventData = self::preparePendingPayoutDataForEvents($payouts);

                $input['payoutIds'] = $eventData['payoutIds'];

                self::dispatchPendingPayoutApprovalPushNotification($input);

                $pendingPayoutsCount++;
            }
        }
        $response['reminderEventCount'] = $pendingPayoutsCount;
        return $response;
    }

    private function preparePendingPayoutDataForEvents($payouts)
    {
        $payouts = $payouts->sortByDesc(Entity::CREATED_AT, 1);
        $payouts = $payouts->toArray();
        $payoutIds = array();

        foreach ($payouts as $payout)
        {
            array_push($payoutIds, $payout['id']);
        }

        return [
            'payoutIds' => $payoutIds
        ];
    }

    public function prepareTemplateAndDispatchEmail($approverList, $payoutLinksApproverList, $pendingLinksMeta)
    {
        $count = 0;

        if ((sizeof($approverList) > 0) or ($payoutLinksApproverList->isEmpty() === false))
        {
            //User wise grouping the merchant - user information
            //We will be sending separate emails to a user for different merchants on whom user has payouts awaiting their approval
            $dataGroupedByUserId = [];
            foreach ($approverList as  $approver){
                $dataGroupedByUserId[$approver['user_id']][] = $approver;
            }
            $payoutsDataGroupedByUserId = sizeof($approverList) > 0 ?
                $dataGroupedByUserId : new Base\PublicCollection();

            $payoutLinksDataGroupedByUserId = ($payoutLinksApproverList->isEmpty() === false) ?
                $payoutLinksApproverList->groupBy(Merchant\MerchantUser\Entity::USER_ID) : new Base\PublicCollection();

            $uniqueUserIds = $this->getUniqueIdsForPendingEmails($payoutsDataGroupedByUserId, $payoutLinksDataGroupedByUserId);

            foreach ($uniqueUserIds as $userId)
            {
                $pendingPayoutsData = $payoutsDataGroupedByUserId[$userId];

                $pendingPayoutLinksData = $payoutLinksDataGroupedByUserId->get($userId);
                $dataGroupedByMerchantId = [];
                foreach ($pendingPayoutsData as  $user){
                    $dataGroupedByMerchantId[$user['merchant_id']][] = $user;
                }
                $pendingPayoutsGroupedByMerchantId = ($pendingPayoutsData !== null) ?
                    $dataGroupedByMerchantId : new Base\PublicCollection();

                $pendingPayoutLinksGroupedByMerchantId = ($pendingPayoutLinksData !== null) ?
                    $pendingPayoutLinksData->groupBy(Merchant\MerchantUser\Entity::MERCHANT_ID) : new Base\PublicCollection();

                $uniqueMerchantIds = $this->getUniqueIdsForPendingEmails($pendingPayoutsGroupedByMerchantId, $pendingPayoutLinksGroupedByMerchantId);

                foreach ($uniqueMerchantIds as $merchantId)
                {
                    $payoutsMailData = array();

                    $payoutLinksMailData = array();

                    $pendingPayoutsUserMerchantData = $pendingPayoutsGroupedByMerchantId[$merchantId];

                    if ($pendingPayoutsUserMerchantData !== null)
                    {
                        $payoutsMailData = $this->preparePendingPayoutMailData($merchantId, $pendingPayoutsUserMerchantData);
                    }

                    $pendingPayoutLinksUserMerchantData = $pendingPayoutLinksGroupedByMerchantId->get($merchantId);

                    if ($pendingPayoutLinksUserMerchantData !== null)
                    {
                        $payoutLinksMailData = $this->preparePendingPayoutLinkMailData($merchantId, $pendingPayoutLinksUserMerchantData, $pendingLinksMeta);
                    }

                    $this->triggerPendingEmail(array_merge($payoutsMailData, $payoutLinksMailData));

                    $this->trace->info(TraceCode::EMAIL_DISPATCHED_FOR_PENDING_PAYOUTS, [
                        'pending_payouts_data'      => $payoutsMailData,
                        'pending_payout_links_data' => $payoutLinksMailData,
                    ]);

                    $count = $count + 1;
                }
            }
        }

        return ['Queued email count' => $count];
    }

    public function isPayoutToFundAccountAllowed($fundAccountId, $mode, $isCompositePayout)
    {
        $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

        if (($fundAccount->getAccountType() === Entity::CARD) and
            (isset($mode) === true))
        {
            $isTokenised = ($fundAccount->account->isTokenPan() === true) ? true : $fundAccount->account->isNetworkTokenisedCard();

            if (($isTokenised === true) and
                ($mode !== Mode::CARD))
            {
                $this->trace->error(TraceCode::MODE_NOT_SUPPORTED_FOR_PAYOUT_TO_TOKENISED_CARDS,
                                    [
                                        'is_composite_payout' => $isCompositePayout,
                                        'payout_mode'         => $mode,
                                        'is_tokenised'        => $isTokenised
                                    ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MODE_NOT_SUPPORTED_FOR_PAYOUT_TO_TOKENISED_CARDS);
            }

            if ($isTokenised === false)
            {
                $this->trace->error(TraceCode::STANDALONE_PAYOUT_TO_CARDS_NOT_ALLOWED,
                                    [
                                        'is_composite_payout' => $isCompositePayout,
                                        'payout_mode'         => $mode,
                                        'is_tokenised'        => $isTokenised
                                    ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_STANDALONE_PAYOUT_TO_CARDS_NOT_ALLOWED);
            }
        }
    }

    public function fetchPayoutAnalyticsfromPayoutsService(Merchant\Entity $merchant): array
    {
        $this->trace->info(
            TraceCode::PAYOUT_GET_REQUEST_FROM_MICROSERVICE,
            [
                'merchant_id' => $merchant->getId()
            ]);

        $response = $this->payoutGetApiServiceClient->GetPayoutsAnalyticsViaMicroservice($merchant->getId());

        return $response;
    }

    // payout transaction dual write is required for ledger_reverse_shadow and payout_service_enabled merchant
    public static function isPayoutTransactionDualWriteEnabled($payout)
    {
        /* API transaction Dual write should happen only if one of the below is true
        1. merchant is on API<>Ledger reverse shadow integration - LEDGER_REVERSE_SHADOW
        2. merchant is on Payout MS<>Ledger integration which is always reverse shadow - PAYOUT_SERVICE_ENABLED

        Note: In case of point 2, we should ensure merchant is not on ledger shadow mode via API<>Ledger integration
        */
        $featureChecks = (($payout->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true) or
                          ($payout->merchant->isFeatureEnabled(Feature\Constants::PAYOUT_SERVICE_ENABLED) === true));

        if ($featureChecks and
            ($payout->getBalanceType() === Merchant\Balance\Type::BANKING) and
            ($payout->getBalanceAccountType() === Merchant\Balance\AccountType::SHARED))
        {
            return true;
        }

        return false;
    }

    public function createTransactionInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse)
    {
        $isPayoutServicePayout = false;

        /** @var Entity $payout */
        $payout = $this->repo->payout->find($entityId);

        if (empty($payout) === true)
        {
            $payout = $this->getAPIModelPayoutFromPayoutService($entityId);

            $isPayoutServicePayout = true;
        }

        if (self::isPayoutTransactionDualWriteEnabled($payout) === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                , ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                                               ['merchant_id' => $payout->getMerchantId()]);
        }

        // Fixing fund account payout here for now
        // Since we are only exploring X balance based payouts
        // Customer wallet payouts and Merchant payouts are usually on PG balance
        // TODO: fix this when PG moves to ledger
        $payoutType = 'fund_account_payout';

        $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                       $payout,
                                                       $this->mode,
                                                       $payout->fundAccount->account);

        $subProcessor = $downstreamProcessor->getSubProcessorClass();

        $txn = $this->mutex->acquireAndRelease('pout_' . $entityId,
            function() use ($payout, $ledgerResponse, $subProcessor, $isPayoutServicePayout) {

                $payout->reload();

                return $this->repo->transaction(function() use ($ledgerResponse, $payout, $subProcessor, $isPayoutServicePayout) {
                    $txn = $subProcessor->createTransactionForLedgerReverseShadow($payout, $ledgerResponse);

                    // No need to update payout if it doesn't exists in api db. PS dual write will take care of it.
                    if ($isPayoutServicePayout === true)
                    {
                        return $txn;
                    }

                    $payout->transaction()->associate($txn);

                    $this->repo->saveOrFail($payout);

                    return $txn;
                });
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );


        return [
            'entity' => $payout->getPublicId(),
            'txn'    => $txn->getPublicId(),
        ];
    }

    /**
     * This function will perform the following tasks after successful journal creation on CLS for RX reverse shadow merchants
     * update the merchant balance, update the transaction id in the payout entity (API payouts), save fee breakup entity,
     * push api.transaction.created event
     * @param string $entityId
     * @param array $ledgerResponse
     * @return mixed
     * @throws Exception\LogicException
     */
    public function updateBalanceAndTransactionIDInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse)
    {
        $isPayoutServicePayout = false;

        /** @var Entity $payout */
        $payout = $this->repo->payout->find($entityId);

        if (empty($payout) === true)
        {
            $payout = $this->getAPIModelPayoutFromPayoutService($entityId);

            $isPayoutServicePayout = true;
        }

        if (self::isPayoutTransactionDualWriteEnabled($payout) === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                , ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                ['merchant_id' => $payout->getMerchantId()]);
        }

        $journalId = $ledgerResponse[Entity::ID];

        // Check if worker is processing duplicate request
        if ($this->isTransactionIdUpdated($payout, $journalId) === true)
        {
            return [
                self::IS_DUPLICATE => true
            ];
        }

        // Fixing fund account payout here for now
        // Since we are only exploring X balance based payouts
        // Customer wallet payouts and Merchant payouts are usually on PG balance
        // TODO: fix this when PG moves to ledger
        $payoutType = 'fund_account_payout';

        $downstreamProcessor = new DownstreamProcessor($payoutType,
            $payout,
            $this->mode,
            $payout->fundAccount->account);

        $subProcessor = $downstreamProcessor->getSubProcessorClass();

        $merchantId = $ledgerResponse[LedgerConstants::LEDGER_ENTRY][0][Entity::MERCHANT_ID];
        $newBalance = Ledger\Payout::getMerchantBalanceFromLedgerResponse($ledgerResponse);

        $this->mutex->acquireAndRelease('pout_' . $entityId,
            function() use ($payout, $subProcessor, $isPayoutServicePayout, $entityId, $journalId, $merchantId, $newBalance)
            {
                if ($isPayoutServicePayout === false)
                {
                    $payout->reload();
                }

                if ($this->isTransactionIdUpdated($payout, $journalId) === true)
                {
                    return [
                        self::IS_DUPLICATE => true
                    ];
                }

                $this->repo->transaction(function() use ($payout, $subProcessor, $isPayoutServicePayout, $entityId, $journalId, $newBalance) {
                    $subProcessor->updateBalanceForLedgerReverseShadow($payout, $entityId, $journalId, $newBalance);

                    // No need to update payout if it doesn't exists in api db. PS dual write will take care of it.
                    if ($isPayoutServicePayout === false)
                    {
                        $payout->setTransactionId($journalId);
                        $this->repo->saveOrFail($payout);
                    }
                });

                if ($payout->getIsPayoutService() === false)
                {
                    (new TransactionCore())->dispatchEventForLedgerTransactionCreatedWithoutEmailOrSmsNotification($journalId, $merchantId);
                }
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );


        return [
            'entity_id' => $entityId,
            'txn_id'    => $journalId
        ];
    }

    /**
     * @throws \Throwable
     */
    public function processPayoutAfterLedgerStatusCheck($payout, $ledgerResponse)
    {

        $this->trace->info(
            TraceCode::PROCESS_PAYOUT_AFTER_LEDGER_STATUS_SUCCESS,
            [
                'payout_id'         => $payout->getId(),
                'entity_name'       => EntityConstant::PAYOUT,
            ]);

        if ($payout->isVaToVaPayout() === true)
        {
            $this->handleTransferForVaToVaPayouts($payout);
        }
        else
        {
            $fta = $this->repo->fund_transfer_attempt->getAttemptBySourceId($payout->getId(), Entity::PAYOUT);

            if (empty($fta) === true)
            {
                // Only calling downstreamProcessor if FTA is not created for a payout.
                $downstreamProcessor = new DownstreamProcessor('fund_account_payout',
                    $payout,
                    $this->mode,
                    $payout->fundAccount->account);

                $downstreamProcessor->processCreateFundTransferAttempt();
            }
        }
    }

    /**
     * This function is used to call ledger within the ledger status async job for payouts.
     * When we find in this async job that a debit journal entry wasn't created for a particular payout in ledger,
     * we use this function to retry creation of journal entries.
     *
     * @throws Exception\BaseException | \Throwable
     */
    public function tryProcessingOfPayoutPostLedgerFailureElseFail($payout)
    {
        $ledgerRequest = null;
        $ledgerResponse = null;

        try
        {
            $payoutsLedgerProcessor = new PayoutsLedgerProcessor($payout);

            $ledgerRequest  = $payoutsLedgerProcessor->createLedgerPayloadFromEntity($payout);
            $ledgerResponse = $payoutsLedgerProcessor->createJournalEntryFromJob($ledgerRequest);
        }
        catch (Exception\BaseException $be)
        {
            $exceptionData = $be->getData();

            $this->trace->traceException($be, Trace::ERROR, TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR,
                [
                    'ledger_request'    => $ledgerRequest,
                ]);

            if (strpos($exceptionData['response_body']['msg'], ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE) !== false or
                strpos($exceptionData['response_body']['msg'], ErrorCode::BAD_REQUEST_VALIDATION_FAILURE) !== false)
            {
                $this->failPayoutPostLedgerFailure($payout);

                return;
            }

            throw $be;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_TIMEOUT,
                [
                    'ledger_request'    => $ledgerRequest,
                ]);

            // We are throwing this error again so that it gets caught at the LedgerStatus.php job class level
            // If it is caught there, we check if the SQS job will be retried
            // This ensures retry logic for creation of this payout in the async job.
            throw $e;
        }

        $this->processPayoutAfterLedgerStatusCheck($payout, $ledgerResponse);
    }

    public function failPayoutPostLedgerFailure($payout)
    {
        $this->trace->info(
            TraceCode::FAIL_PAYOUT_AFTER_LEDGER_STATUS_SUCCESS,
            [
                'payout_id'         => $payout->getId(),
                'entity_name'       => EntityConstant::PAYOUT,
            ]);

        $downstreamProcessor = new DownstreamProcessor('fund_account_payout',
            $payout,
            $this->mode,
            $payout->fundAccount->account);

        $subProcessor = $downstreamProcessor->getSubProcessorClass();

        // mark payout as failed
        $subProcessor->failPayoutPostLedgerFailure($payout);
    }

    public function trackPayoutEvent(array $eventCode, $payout = null, $error = null)
    {
        $auth       = $this->app['basicauth'];
        $merchantId = null;

        //In case of admin auth merchnat won't be set
        if (isset($this->merchant) === false)
        {
            return;
        }

        $merchantId = $this->merchant->getId();
        $user   = $auth->getUser();
        $role   = $auth->getUserRole();;

        $userId = null;
        //For outh user will be there for normal private auth user won't be there
        if (isset($user) === true )
        {
            $userId        = $user->getId();
        }

        $eventAttribute = [
            'merchant_id'   => $merchantId,
            'request'       => $this->app['api.route']->getCurrentRouteName(),
            'user_id'       => $userId,
            'user_role'     => $role,
            'channel'       => $auth->getSourceChannel()
        ];

        $this->app['diag']->trackPayoutApproveRejectActionEvents($eventCode,
            $payout,
            $error,
            $eventAttribute);
    }

    public function createPayoutViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        if(empty($whitelistIds) === false)
        {
            $payouts = $this->repo->payout->fetchCreatedPayoutsWhereTxnIdNullAndIdsIn($whitelistIds);

            return $this->processPayoutViaLedgerCronJob($blacklistIds, $payouts, true);
        }

        for ($i = 0; $i < 3; $i++)
        {
            // Fetch all payouts created in the last 24 hours.
            // Doing this 3 times in for loop to fetch payouts created in last 72 hours.
            // This is done so as to not put extra load on the database while querying.
            $payouts = $this->repo->payout->fetchCreatedPayoutsAndTxnIdNullBetweenTimestamp($i, $limit);

            $this->processPayoutViaLedgerCronJob($blacklistIds, $payouts);
        }
    }

    private function processPayoutViaLedgerCronJob(array $blacklistIds, $payouts, bool $skipChecks = false)
    {
        foreach ($payouts as $payout)
        {
            try
            {
                // If merchant is onboarded on payout service then we skip cron processing.
                // Any intermittent failures are handled at payout service end.
                if ($payout->merchant->isFeatureEnabled(FeatureConstants::PAYOUT_SERVICE_ENABLED) === true)
                {
                    $this->trace->info(
                        TraceCode::LEDGER_STATUS_CRON_SKIP_MERCHANT_ON_PAYOUT_SERVICE,
                        [
                            'payout_id'   => $payout->getPublicId(),
                            'merchant_id' => $payout->getMerchantId(),
                        ]
                    );
                    continue;
                }

                /*
                 * If merchant is not on reverse shadow, and is not present in $forcedMerchantIds array,
                 * only then skip the merchant.
                 */
                if ($payout->merchant->isFeatureEnabled(FeatureConstants::LEDGER_REVERSE_SHADOW) === false)
                {
                    $this->trace->info(
                        TraceCode::LEDGER_STATUS_CRON_SKIP_MERCHANT_NOT_REVERSE_SHADOW,
                        [
                            'payout_id'   => $payout->getPublicId(),
                            'merchant_id' => $payout->getMerchantId(),
                        ]
                    );
                    continue;
                }

                if($skipChecks === false)
                {

                    if(in_array($payout->getPublicId(), $blacklistIds) === true)
                    {
                        $this->trace->info(
                            TraceCode::LEDGER_STATUS_CRON_SKIP_BLACKLIST_PAYOUT,
                            [
                                'payout_id' => $payout->getPublicId(),
                            ]
                        );
                        continue;
                    }
                }

                $this->trace->info(
                    TraceCode::LEDGER_STATUS_CRON_PAYOUT_INIT,
                    [
                        'payout_id' => $payout->getPublicId(),
                    ]
                );

                $ledgerRequest = (new PayoutsLedgerProcessor())->createLedgerPayloadFromEntity($payout, Status::CREATED);

                (new LedgerStatus($this->mode, $ledgerRequest, null, false))->handle();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::LEDGER_STATUS_CRON_PAYOUT_FAILED,
                    [
                        'payout_id' => $payout->getPublicId(),
                    ]
                );

                $this->trace->count(Metric::LEDGER_STATUS_CRON_FAILURE_COUNT,
                                    [
                                        'environment' => $this->app['env'],
                                        'entity'      => 'payout'
                                    ]);

                continue;
            }
        }
    }

    public function processTdsForPayout(Entity $payout, string $oldStatus)
    {
        $this->trace->info(
            TraceCode::TDS_PROCESSOR_PAYOUT_STATE_TRANSITION_DEBUG_INFO,
            [
                'payout_id'         => $payout->getId(),
                'previous_status'   => $oldStatus,
                'new_status'        => $payout->getStatus(),
            ]
        );

        if (Status::isMoneyTransferredState($payout->getStatus()) === true)
        {
            try
            {
                $this->tdsProcessor->processTds($payout);
            }
            catch (\Throwable $e)
            {
                $data = [
                    'payout_id' => $payout->getPublicId(),
                ];

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::ERROR_PROCESSING_TDS_FOR_PAYOUT,
                    $data
                );

                (new SlackNotification)->send(
                    'Failed to process TDS for Payout',
                    $data,
                    $e,
                    1,
                    'x-alerts');
            }

        }
    }

    public static function isHighTpsMerchantWithSubBalance(Entity $payout): bool
    {
        return (($payout->isBalanceAccountTypeShared() === true) and
                (($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT) === true) or
                 ($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_PAYOUT_EGRESS) === true)));
    }

    public static function isHighTpsMerchant(Entity $payout): bool
    {
        return (($payout->isBalanceAccountTypeShared() === true) and
                (($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT) === true) or
                 ($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_PAYOUT_EGRESS) === true) or
                 ($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_PAYOUT_INGRESS) === true)));
    }

    /**
     * @param Entity $payout
     * @param $bankAccStmt
     */
    public function sendExtToPayoutEventToLedger(Entity $payout, $bankAccStmt)
    {
        if (($payout->isBalanceAccountTypeDirect() === true) and ($bankAccStmt !== null))
        {
            if ($payout->getPurpose() === Purpose::RZP_FEES)
            {
                $this->processLedgerPayoutForDirect($payout, Transaction\Processor\Ledger\Payout::DA_EXT_FEE_PAYOUT_PROCESSED, null, null, $bankAccStmt);
            }
            else
            {
                $this->processLedgerPayoutForDirect($payout, Transaction\Processor\Ledger\Payout::DA_EXT_PAYOUT_PROCESSED, null, null, $bankAccStmt);
                $this->processLedgerPayoutForDirect($payout, Transaction\Processor\Ledger\Payout::DA_PAYOUT_PROCESSED_RECON, null, null, $bankAccStmt);
            }
        }
    }

    /**
     * @param Entity $payout
     * @param $bankAccStmtForReversal
     * @param $reversal
     */
    public function sendExtToReversalEventToLedger(Entity $payout, $bankAccStmtForReversal, $reversal): void
    {
        if (($payout->isBalanceAccountTypeDirect() === true) and ($bankAccStmtForReversal !== null))
        {
            if ($payout->getPurpose() === Purpose::RZP_FEES)
            {
                $this->processLedgerPayoutForDirect($payout, Transaction\Processor\Ledger\Payout::DA_EXT_FEE_PAYOUT_REVERSED, $reversal, null, $bankAccStmtForReversal);
            }
            else
            {
                $this->processLedgerPayoutForDirect($payout, Transaction\Processor\Ledger\Payout::DA_EXT_PAYOUT_REVERSED, $reversal, null, $bankAccStmtForReversal);
                $this->processLedgerPayoutForDirect($payout, Transaction\Processor\Ledger\Payout::DA_PAYOUT_REVERSED_RECON, $reversal, null, $bankAccStmtForReversal);
            }
        }
    }

    public function checkStatusOfTestPayouts($input)
    {
        $merchantId      = Merchant\Account::FUND_LOADING_DOWNTIME_DETECTION_SOURCE_ACCOUNT_MID;

        $this->merchant  = $this->addMerchantForTestPayouts($merchantId);

        $merchantId      = $this->merchant->getId();

        $modes           = $input[\RZP\Models\Payout\Service::MODES];

        foreach ($modes as $mode)
        {
            $testPayoutsICICI = $this->repo->payout->fetchTestPayouts($merchantId, $mode, self::NARRATION_ICICI);

            list($successfulIciciTestPayouts,
                $delayedIciciTestPayouts,
                $unsuccessfulIciciTestPayouts,
                $utr[]
                ) = $this->calculateSuccessfulUnsuccessfulDelayedTestPayouts($testPayoutsICICI);

            if (($successfulIciciTestPayouts + $delayedIciciTestPayouts + $unsuccessfulIciciTestPayouts) > 0)
            {
                if (($delayedIciciTestPayouts + $unsuccessfulIciciTestPayouts) / ($successfulIciciTestPayouts + $delayedIciciTestPayouts + $unsuccessfulIciciTestPayouts) > 0.75)
                {
                    $isICICIDowntimeDetected = true;
                }
                else
                {
                    $isICICIDowntimeDetected = false;
                }

                $responseIcici = [
                    self::BANK => 'ICICI',
                    self::STATUS => [
                        self::MODE => 'IFT',
                        self::IS_DOWNTIME_DETECTED => $isICICIDowntimeDetected,
                        self::SUCCESSFUL_ICICI_TEST_PAYOUTS => $successfulIciciTestPayouts,
                        self::DELAYED_ICICI_TEST_PAYOUTS => $delayedIciciTestPayouts,
                        self::UNSUCCESSFUL_ICICI_TEST_PAYOUTS => $unsuccessfulIciciTestPayouts,
                        self::UTR_FOR_DELAYED_AND_UNSUCCESSFUl_CASES => $utr,
                    ]
                ];
            }

            else
            {
                $responseIcici = [
                    self::BANK => 'ICICI',
                    self::MODE => 'IFT',
                    self::STATUS => [
                        self::MESSAGE => 'No test payout found',
                    ]
                ];
            }

            $finalResponseIcici[] = $responseIcici;

        // commenting status check for YESB as we are starting with only ICICI IFT mode for now.
        $testPayoutsForYESBNotEnabled = true;

            if ($testPayoutsForYESBNotEnabled === false)
            {
                $testPayoutsYESB = $this->repo->payout->fetchTestPayouts($merchantId, $mode, self::NARRATION_YESB);

                list($successfulYesbTestPayouts,
                    $delayedYesbTestPayouts,
                    $unsuccessfulYesbTestPayouts,
                    $utr[]
                    ) = $this->calculateSuccessfulUnsuccessfulDelayedTestPayouts($testPayoutsYESB);

                if (($successfulYesbTestPayouts + $delayedYesbTestPayouts + $unsuccessfulYesbTestPayouts) > 0)
                {
                    if (($delayedYesbTestPayouts + $unsuccessfulYesbTestPayouts) / ($successfulYesbTestPayouts + $delayedYesbTestPayouts + $unsuccessfulYesbTestPayouts) > 0.75)
                    {
                        $isYESBDowntimeDetected = true;
                    }
                    else
                    {
                        $isYESBDowntimeDetected = false;
                    }

                    $responseYESB = [
                        self::BANK => 'YESB',
                        self::STATUS => [
                            self::MODE => $mode,
                            self::IS_DOWNTIME_DETECTED => $isYESBDowntimeDetected,
                            self::SUCCESSFUL_YESB_TEST_PAYOUTS => $successfulYesbTestPayouts,
                            self::DELAYED_YESB_TEST_PAYOUTS => $delayedYesbTestPayouts,
                            self::UNSUCCESSFUL_YESB_TEST_PAYOUTS => $unsuccessfulYesbTestPayouts,
                        ]
                    ];
                }
                else
                {
                    $responseYESB = [
                        self::BANK => 'YESB',
                        self::MODE => $mode,
                        self::STATUS => [
                            self::MESSAGE => 'No test payout found',
                        ]
                    ];
                }

                $finalResponseYESB[] = $responseYESB;
            }

        }

        $response = [$finalResponseIcici];

        $this->trace->info(TraceCode::STATUS_OF_TEST_PAYOUTS,
            ['response' => $response]);

        return $response;
    }

    protected function calculateSuccessfulUnsuccessfulDelayedTestPayouts($testPayouts)
    {
        $countOfUnsuccessfulFundLoading = 0;

        $countOfSuccessfulFundLoading   = 0;

        $countOfDelayedFundLoading      = 0;

        // threshold time is in minutes
        $thresholdForReceivingCallback  = 40;

        $payeeAccountNumber = self::PAYEE_ACCOUNT_NUMBER;

        $utrArray = [];

        foreach ($testPayouts as $testPayout)
        {
            $utr          = $testPayout->getUtr();
            $processedAt  = $testPayout->getProcessedAt();

            $bankTransfer = $this->repo->bank_transfer->findByUtrAndPayeeAccountAndAmount($utr,$payeeAccountNumber,100);

            if ($bankTransfer !== null)
            {
                $createdAt = $bankTransfer->getCreatedAt();

                 if (abs(($createdAt -$processedAt)) / 60 > $thresholdForReceivingCallback)
                {
                    $countOfDelayedFundLoading++;
                    $utrArray[] = $utr;
                }
                else
                {
                    $countOfSuccessfulFundLoading++;
                }
            }

            else
            {
                $countOfUnsuccessfulFundLoading++;
                $utrArray[] = $utr;
            }
        }

        // this is done to show only 5 utrs in response in case of more number of utrs.
        if (sizeof($utrArray) > 5)
        {
            $utrResult = array_slice($utrArray,0,5);
        }
        else
        {
            $utrResult = $utrArray;
        }


        return [$countOfSuccessfulFundLoading, $countOfDelayedFundLoading, $countOfUnsuccessfulFundLoading, $utrResult];
    }

    public function payoutServiceRedisKeySet($input)
    {
        return $this->payoutServiceRedisClient->payoutsMicroserviceRedisKeySet($input);
    }

    public function payoutServiceDualWrite($input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_DUAL_WRITE_DISPATCH_TO_QUEUE_REQUEST,
            $input
        );

        PayoutServiceDualWrite::dispatch($this->mode, $input);

        $this->trace->info(
            TraceCode::PAYOUT_DUAL_WRITE_DISPATCHED_TO_QUEUE,
            $input
        );

        return ['status' => 'success'] ;
    }

    public function payoutUpdateByBASRecon($input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_UPDATE_BY_BAS_RECON,
            $input
        );

        (new Validator)->validateInput(Validator::PAYOUT_UPDATE_BY_BAS_RECON, $input);

        $basId = $input[BankingAccountStatement\Entity::BAS_ID];
        $payoutId = $input[BankingAccountStatement\Entity::ENTITY_ID];
        $type = $input[BankingAccountStatement\Entity::ENTITY_TYPE];

        $merchantId = $input[Entity::MERCHANT_ID];
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        try
        {
            /** @var Entity $payout */
            $payout = $this->repo->payout->findOrFail($payoutId);

            if (is_null($payout) or $payout->getIsPayoutService())
            {
                return $this->payoutServiceBankingAccountStatementClient->updatePayoutAfterBASRecon($input);
            }

            if ($type == 'payout')
            {
                $payout->setTransactionId($basId);
                $payout->setTransactionType(Entity::TRANSACTION);

                $payout->saveOrFail();

                $this->sendLedgerEventPostBasLinking($merchant, $payout, $input);
            }
            else
            {
                /** @var Reversal\Entity $reversal */
                $reversal = null;

                if ($payout->getStatus() == Status::FAILED)
                {
                    $reverseReason = $payout->getFailureReason() ?? 'REVERSAL';

                    $this->reversePayout(
                        $payout,
                        $reverseReason,
                        'FAILURE',
                        null,
                        $reversal,
                        true);
                }
                else
                {
                    $reversal = $payout->reversal;
                }

                if (!is_null($reversal))
                {
                    $reversal->setTransactionId($basId);
                    $reversal->saveOrFail();

                    $this->sendLedgerEventPostBasLinking($merchant, $reversal, $input, $payout);
                }
            }
        }
        catch (\throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_UPDATE_BY_BAS_RECON_FAILURE,
                ['input' => $input]
            );

            throw $exception;
        }

        $this->trace->info(
            TraceCode::PAYOUT_UPDATE_BY_BAS_RECON_COMPLETE,
            $input
        );

        return ['status' => 'success'];
    }

    public function sendLedgerEventPostBasLinking($merchant, $sourceEntity, $input, $payout = null)
    {
        $basId = $input['bas_id'];
        $transactionDate = $input[BankingAccountStatement\Entity::TRANSACTION_DATE];
        $isConvertedFromExternal = $input[BankingAccountStatement\Entity::CONVERTED_FROM_EXTERNAL];

        $bas = new BankingAccountStatement\Entity;
        $bas->setId($basId);
        $bas->setTransactionDate($transactionDate);

        if ($isConvertedFromExternal)
        {
            if ($sourceEntity->getEntityName() === Constants\Entity::PAYOUT)
            {
                $this->sendExtToPayoutEventToLedger($sourceEntity, $bas);
            }
            else if ($sourceEntity->getEntityName() === Constants\Entity::REVERSAL)
            {
                $this->sendExtToReversalEventToLedger($payout, $bas, $sourceEntity);
            }

            return;
        }

        (new BankingAccountStatement\Core)->sendToLedgerPostSourceEntityProcessing($merchant, $sourceEntity, $bas);
    }

    public function payoutServiceDeleteCardMetaData($input)
    {
        $this->trace->info(
            TraceCode::DELETE_VAULT_TOKEN_AND_CARD_META_DATA_PAYOUT_SERVICE_REQUEST,
            $input
        );

        $deleteCardMetaDataResponse = [];

        try
        {
            $deleteCardMetaDataResponse = $this->app['card.cardVault']->deleteToken($input[Entity::VAULT_TOKEN]);

            $this->trace->info(TraceCode::PAYOUT_TO_CARDS_VAULT_TOKEN_SUCCESSFULLY_DELETED_FOR_PAYOUT_SERVICE, $input);
        }
        catch (\Exception $exception)
        {
            $deleteCardMetaDataResponse['success'] = false;

            $response = [];

            if (method_exists($exception, 'getData') === true)
            {
                $response = $exception->getData();
            }

            if (isset($response['error']) === true)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_TO_CARDS_VAULT_TOKEN_DELETION_FAILED,
                    [
                        Entity::VAULT_TOKEN => $input[Entity::VAULT_TOKEN],
                        Entity::CARD_ID     => $input[Entity::CARD_ID]
                    ]);

                $deleteCardMetaDataResponse['error'] = $response['error'];
            }
            else
            {
                $deleteCardMetaDataResponse['error'] = "";
            }
        }

        return $deleteCardMetaDataResponse;
    }

    public function addMerchantForTestPayouts(string $merchantId)
    {
        $this->merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->app['basicauth']->setMerchant($this->merchant);

        return $this->merchant;
    }

    private function updateStatusFromFailedToReversedIfDebitAndCreditFoundForCA(&$status, Entity $payout)
    {
        if (($status !== Status::FAILED) or
            ($payout->balance->isAccountTypeShared() === true))
        {
            return;
        }

        if (($payout->getIsPayoutService() === true) and
            ($payout->balance->isAccountTypeDirect() === true))
        {
            $variant = $this->app['razorx']->getTreatment($payout->getMerchantId(),
                RazorxTreatment::ENABLE_BAS_CHECK_FOR_PAYOUTS_SERVICE, Constants\Mode::LIVE);

            if ($variant != 'on') {
                return;
            }
        }

        $this->trace->info(
            TraceCode::MODIFY_STATUS_FOR_CURRENT_ACCOUNT_CHECK_START,
            [
                Entity::PAYOUT . '_' . Entity::ID   => $payout->getId(),
                Entity::MERCHANT_ID                 => $payout->getMerchantId(),
                Entity::UTR                         => $payout->getUtr(),
            ]
        );

        $debitBAS = null;

        if (empty($payout->getUtr()) === false)
        {
            $debitBAS = $this->repo->banking_account_statement->fetchByUtrForPayout($payout);
        }
        if ($debitBAS === null)
        {
            $debitBAS = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout) ??
                        $this->repo->banking_account_statement->fetchByGatewayRefNumForPayout($payout);
        }

        if (empty($debitBAS) === false)
        {
            $creditBAS = null;

            if (empty($payout->getUtr()) === false)
            {
                $creditBAS = $this->repo->banking_account_statement->fetchByUtrForPayout($payout, BankingAccountStatement\Type::CREDIT);
            }
            if ($creditBAS === null)
            {
                $creditBAS = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout, BankingAccountStatement\Type::CREDIT) ??
                             $this->repo->banking_account_statement->fetchByGatewayRefNumForPayout($payout, BankingAccountStatement\Type::CREDIT);
            }

            if ($creditBAS === null)
            {
                return;
            }

            $status = Status::REVERSED;

            $this->trace->info(
                TraceCode::MODIFY_STATUS_FOR_CURRENT_ACCOUNT,
                [
                    Entity::PAYOUT . '_' . Entity::ID   => $payout->getId(),
                    Entity::STATUS                      => Status::REVERSED,
                    Entity::UTR                         => $payout->getUtr(),
                ]
            );
        }
    }

    public function initiateDataMigration(array $input)
    {
        $count = 0;

        foreach ($input as $data)
        {
            $this->trace->info(
                TraceCode::PAYOUTS_DATA_MIGRATION_JOB_DISPATCH,
                $data
            );

            PayoutServiceDataMigration::dispatch($this->mode, $data);

            $count++;
        }

        return ['dispatch_count' => $count];
    }

    public function payoutServiceMailAndSms($input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_MAIL_AND_SMS_REQUEST,
            $input
        );

        (new Validator)->validateInput(Validator::PAYOUT_SERVICE_MAIL_AND_SMS_INPUT, $input);

        $entity = $input['entity'];
        $type   = $input['type'];

        switch ($entity)
        {
            case Entity::PAYOUT:
                $payoutId = $input['entity_id'];

                $payout = $this->getAPIModelPayoutFromPayoutService($payoutId);

                (new Notifications\Factory)->getNotifier($type, $payout, $input['metadata'])->notify();

                break;

            case Entity::TRANSACTION:
                (new Validator)->validateInput(Validator::PAYOUT_SERVICE_TXN_MAIL_DATA, $input['metadata']);

                $txnInput = $input['metadata'];
                $payoutId = $txnInput[Entity::PAYOUT_ID];

                $payout = $this->getAPIModelPayoutFromPayoutService($payoutId);

                $txn = new Transaction\Entity;
                $txn->setId($input[Transaction\Entity::ENTITY_ID]);
                $txn->setAmount($txnInput[Entity::AMOUNT]);
                $txn->setCreatedAt($txnInput[Transaction\Entity::CREATED_AT]);

                $txn->source()->associate($payout);
                $txn->accountBalance()->associate($payout->balance);
                $txn->merchant()->associate($payout->merchant);

                (new Transaction\Notifier($txn, $type))->notify();

                break;
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_MAIL_AND_SMS_SUCCESS,
            $input
        );
    }

    public function psDataMigrationRedisCleanUp(array $input)
    {
        $count = 0;

        foreach ($input as $data)
        {
            $this->trace->info(
                TraceCode::PAYOUTS_DATA_MIGRATION_REDIS_CLEAN_UP,
                $data
            );

            $redisKey = $this->getPayoutServiceMigrationRedisKey($data[Entity::MERCHANT_ID], $data[Entity::BALANCE_ID]);

            $this->deleteRedisKeyForPayoutServiceMigration($redisKey);

            $count++;
        }

        return ['clean_up_count' => $count];
    }

    public function processDataMigration(array $input)
    {
        (new Validator)->validateInput(Validator::PAYOUT_SERVICE_DATA_MIGRATION_INPUT, $input);

        /** @var Merchant\Balance\Entity $balance */
        $balance = $this->repo->balance->findOrFailById($input[Entity::BALANCE_ID]);

        $input[Entity::MERCHANT_ID] = $balance->getMerchantId();

        $this->setLimitForDataMigration($input);

        $redisKey = $this->getPayoutServiceMigrationRedisKey($input[Entity::MERCHANT_ID], $input[Entity::BALANCE_ID]);

        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::PAYOUT_SERVICE_DATA_MIGRATION_BATCH_ATTEMPTS]);

        if (empty($limit) === true)
        {
            $limit = self::MAX_ATTEMPTS_FOR_DATA_MIGRATION;
        }

        $buffer = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::PAYOUT_SERVICE_DATA_MIGRATION_BUFFER]);

        if (empty($buffer) === true)
        {
            $buffer = self::BUFFER_FOR_DATA_MIGRATION;
        }

        $input['buffer'] = $buffer;

        return $this->mutex->acquireAndRelease(
            $redisKey,
            function() use ($input, $redisKey, $limit) {

                list($id, $createdAt) = $this->getPreviousIdAndCreatedAt($redisKey);

                $originalInput = $input;

                $batch = 1;

                while ($batch <= $limit)
                {
                    $input[DataMigrationProcessor::ID] = $id;

                    $input[DataMigrationProcessor::CREATED_AT] = max($createdAt, $originalInput[DataMigrationProcessor::FROM]);

                    unset($input[DataMigrationProcessor::FROM]);

                    // This is to make sure that we don't query db for more than 1 day time window.
                    $input[DataMigrationProcessor::END_TIMESTAMP] = min($input[DataMigrationProcessor::CREATED_AT] +
                                                                        DataMigrationProcessor::SECONDS_PER_DAY,
                                                                        $originalInput[DataMigrationProcessor::TO]);

                    unset($input[DataMigrationProcessor::TO]);

                    $this->trace->info(
                        TraceCode::PAYOUTS_DATA_MIGRATION_PROCESS_INPUT,
                        $input + ['batch_number' => $batch]
                    );

                    $result = (new DataMigrationProcessor)->processDataMigration($input);

                    $this->trace->info(
                        TraceCode::PAYOUTS_DATA_MIGRATION_PROCESS_RESPONSE,
                        ['result' => $result]
                    );

                    // If result is empty that means there are no payouts to migrate
                    if (empty($result) === true)
                    {
                        // If there are no payouts to migrate and end timestamp is equal to or greater than `to`
                        // that means we have scanned for whole time window and job is completed.
                        if ($input[DataMigrationProcessor::END_TIMESTAMP] >= $originalInput[DataMigrationProcessor::TO])
                        {
                            $this->trace->info(
                                TraceCode::PAYOUTS_DATA_MIGRATION_JOB_DELETE, [
                                'input'          => $input,
                                'original_input' => $originalInput,
                            ]);

                            $this->deleteRedisKeyForPayoutServiceMigration($redisKey);

                            return 'completed';
                        }

                        $createdAt = $input[DataMigrationProcessor::END_TIMESTAMP] + 1;
                    }
                    else
                    {
                        $id        = $result[DataMigrationProcessor::ID];
                        $createdAt = $result[DataMigrationProcessor::CREATED_AT];
                    }

                    $this->setPreviousIdAndCreatedAt($redisKey, $id, $createdAt);

                    $batch++;
                }

                return 'incomplete';
            },
            self::MUTEX_LOCK_TIMEOUT_PS_DATA_MIGRATION,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    public function processDualWrite(array $input)
    {
        (new Validator)->validateInput(Validator::PAYOUT_SERVICE_DUAL_WRITE_INPUT, $input);

        $payoutId = $input[Entity::PAYOUT_ID];

        $this->mutex->acquireAndRelease(
            'payout_service_fee_recovery_ca_merchant' . $payoutId,
            function() use ($payoutId)
            {
                $this->repo->transaction(function() use ($payoutId)
                {
                    $payout = (new DualWrite\Payout)->getAPIPayoutFromPayoutService($payoutId);

                    if($payout->isBalanceAccountTypeDirect() === true)
                    {
                        $status = $payout->getStatus();
                        $this->trace->info(
                            TraceCode::PAYOUT_SERVICE_FEE_RECOVERY_FOR_CA_PAYOUT_REQUEST,[
                            "payout_entity" => $payout->toArray(),
                            "payout_id" => $payoutId,
                        ]);
                        if ($status === Status::PROCESSED || $status === Status::INITIATED || $status === Status::FAILED || $status === Status::REVERSED)
                        {
                            // We need to create a fee_recovery entity for every CA payout in PS when there is either initiated or processed or reversed ot failed state.
                            // We will check fee recovery table whether the fee recovery is already done or not for that Payout ID.

                            $this->trace->info(
                                TraceCode::PAYOUT_SERVICE_FEE_RECOVERY_FOR_CA_PAYOUT,[
                                    "payout_entity" => $payout->toArray(),
                                    "payout_id" => $payoutId,
                                ]);

                            (new DualWrite\Processor)->feeRecoveryForPSCAPayout($payout);
                        }
                    }
                });
            },
            self::PS_CA_PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $metadata = $this->getMetaDataFromPayoutServiceForDualWrite($payoutId);

        if (empty($metadata) == false)
        {
            $timestamp = get_object_vars(json_decode($metadata['meta_value']))['timestamp'];

            if ($input['timestamp'] < $timestamp)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_DUAL_WRITE_NO_ACTION,
                    [
                        'input' => $input,
                        'metaData' => $metadata
                    ]
                );

                return;
            }
        }

        (new DualWrite\Processor)->dualWriteDataForPayoutId($payoutId);

        $this->upsertMetaDataInPayoutServiceForDualWrite($payoutId, $currentTime, $metadata);
    }

    public function getMetaDataFromPayoutServiceForDualWrite($payoutId)
    {
        $metadata = $this->repo->payout->getPayoutServicePayoutMetaDataForDualWrite($payoutId);

        if (count($metadata) === 0)
        {
            return [];
        }

        $metadata = $metadata[0];

        return get_object_vars($metadata);
    }

    public function upsertMetaDataInPayoutServiceForDualWrite(string $payoutId, $currentTime, $metadata)
    {
        $tableName = self::PAYOUT_SERVICE_TEMPORARY_METADATA_TABLE;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        if (empty($metadata) === true)
        {
            $data = [
                Entity::ID         => Entity::generateUniqueId(),
                Entity::PAYOUT_ID  => $payoutId,
                'meta_name'        => self::DUAL_WRITE_META_NAME,
                'meta_value'       => json_encode(['timestamp' => $currentTime]),
                Entity::CREATED_AT => Carbon::now(Timezone::IST)->getTimestamp(),
                Entity::UPDATED_AT => Carbon::now(Timezone::IST)->getTimestamp(),
            ];

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_INSERT_METADATA,
                $data
            );

            $this->repo->payout->insertIntoPayoutServiceDB($tableName, $data);
        }
        else
        {
            $id = $metadata[Entity::ID];

            $data = [
                'meta_value'       => json_encode(['timestamp' => $currentTime]),
                Entity::UPDATED_AT => Carbon::now(Timezone::IST)->getTimestamp(),
            ];

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_UPDATE_METADATA,
                $data
            );

            $this->repo->payout->updateInPayoutServiceDB($tableName, $id, $data);
        }
    }

    protected function getPayoutServiceMigrationRedisKey(string $merchantId, string $balanceId)
    {
        return self::REDIS_KEY_PREFIX . $merchantId . '_' . $balanceId;
    }

    protected function setLimitForDataMigration(array & $input)
    {
        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::PAYOUT_SERVICE_DATA_MIGRATION_LIMIT_PER_BATCH]);

        if (empty($limit) === true)
        {
            $limit = self::PS_DATA_MIGRATION_LIMIT;
        }

        $input['limit'] = $limit;
    }

    // Returns previously stored id and created_at of the last record migrated.
    protected function getPreviousIdAndCreatedAt(string $redisKey)
    {
        $redis = $this->app['redis']->connection();

        $value = $redis->get($redisKey);

        // On running first time it will return id = '' and created_at = 0 as default values.
        if ($value === null)
        {
            return ['', 0];
        }

        $id = substr($value, 0, Entity::ID_LENGTH);

        $createdAt = intval(substr($value, Entity::ID_LENGTH + 1));

        return [$id, $createdAt];
    }

    // After every job completion we will be updating the id and created_at.
    protected function setPreviousIdAndCreatedAt(string $redisKey, string $id, int $createdAt)
    {
        $app = App::getFacadeRoot();

        $redis = $app['redis']->connection();

        $value = $id . '_' . $createdAt;

        $redis->set($redisKey, $value);
    }

    protected function deleteRedisKeyForPayoutServiceMigration(string $redisKey)
    {
        $app = App::getFacadeRoot();

        $redis = $app['redis']->connection();

        $redis->del($redisKey);
    }

    private function getUniqueIdsForPendingEmails($payoutsGroupedData, $payoutLinksGroupedData): array
    {
        $uniqueIds = array();

        foreach ($payoutsGroupedData as $id => $idData)
        {
            array_push($uniqueIds, $id);
        }

        foreach ($payoutLinksGroupedData as $id => $idData)
        {
            array_push($uniqueIds, $id);
        }

        return array_unique($uniqueIds);
    }

    private function preparePendingPayoutMailData($merchantId, $data): array
    {
        $input = [
            'user_id'       => $data[0]['user_id'],
            'merchant_id'   => $data[0]['merchant_id'],
            'email'         => $data[0]['email'],
            'name'          => $data[0]['name'],
            'business_name' => $data[0]['business_name'],
            'role'          => $data[0]['role'],
            'amount_total'  => $data[0]['payout_total'],
            'total_count'   => $data[0]['payout_count']
        ];

        $startAt = millitime();

        //Fetching top 5 pending payouts in chronological order for selected merchant-user combination
        $payouts = $this->repo->payout->fetchNPendingPayoutsToDisplay($merchantId, $input['role'], 5);

        $this->trace->info(TraceCode::PENDING_APPROVAL_EMAILS_PAYOUTS_QUERY_DURATION, [
            'query_execution_time' => millitime() - $startAt,
            'merchant_user_data'   => $input,
            'payouts_data'         => $payouts
        ]);

        $payouts = $payouts->sortByDesc(Entity::CREATED_AT, 1);

        $payoutsGroupedByPurpose = $payouts->groupBy(Entity::PURPOSE);

        $data = $payoutsGroupedByPurpose->toArray();

        foreach ($payoutsGroupedByPurpose as $purpose => $payout)
        {
            foreach ($payoutsGroupedByPurpose[$purpose] as $index => $p)
            {
                $data[$purpose][$index]['contact_name'] = $p['contact_name'];
                $data[$purpose][$index]['created_at']   = Carbon::createFromTimestamp($data[$purpose][$index]['created_at'], Timezone::IST)->format('d M\'y . g:i A');
                $data[$purpose][$index]['amount']       = $data[$purpose][$index]['amount'];
            }
        }

        $input['data'] = $data;

        return $input;
    }

    private function preparePendingPayoutLinkMailData($merchantId, $userDataCollection, $pendingLinksData): array
    {
        $userData = $userDataCollection->first();

        $mailData = [
            'user_id'                   => $userData['user_id'],
            'merchant_id'               => $userData['merchant_id'],
            'email'                     => $userData['email'],
            'name'                      => $userData['name'],
            'business_name'             => $userData['business_name'],
            'role'                      => $userData['role'],
            'payout_links_count'        => $pendingLinksData[$merchantId][$userData['role']]['payout_link_count'],
            'payout_links_amount_total' => $pendingLinksData[$merchantId][$userData['role']]['payout_link_amount'],
        ];

        $data = array();

        $payoutLinksCollection = $this->app['payout-links']->fetchTopFivePendingLinksForApprovalEmail($merchantId, $userData['role']);

        if (array_key_exists('count', $payoutLinksCollection) === true && ($payoutLinksCollection['count'] > 0))
        {
            foreach ($payoutLinksCollection['items'] as $payoutLink)
            {
                array_push($data, [
                    'contact_name' => $payoutLink['contact']['name'],
                    'amount'       => $payoutLink['amount'],
                    'created_at'   => Carbon::createFromTimestamp($payoutLink['created_at'], Timezone::IST)->format('d M\'y . g:i A'),
                ]);
            }
        }

        $mailData['payoutLinksData'] = $data;

        return $mailData;
    }

    private function triggerPendingEmail(array $mailData)
    {
        if(array_key_exists('payout_links_count', $mailData) === true)
        {
            $this->transformPendingPayoutsMailData($mailData);

            $mailable = new Approval($mailData);

            Mail::queue($mailable);
        }
        else
        {
            $mailable = new PendingApprovals($mailData);

            Mail::queue($mailable);
        }
    }

    private function transformPendingPayoutsMailData(array &$mailData)
    {
        $payoutsCount = array_pull($mailData, 'total_count', 0);

        if ($payoutsCount > 0)
        {
            $mailData['payouts_count'] = $payoutsCount;
        }

        $payoutsAmount = array_pull($mailData, 'amount_total', 0);

        if ($payoutsAmount > 0)
        {
            $mailData['payouts_amount_total'] = $payoutsAmount;
        }

        $payoutsData = array_pull($mailData, 'data', []);

        if (empty($payoutsData) === false)
        {
            $mailData['payoutsData'] = $this->transformPendingPayoutsDataToPayoutLinksFormat($payoutsData);
        }
    }

    private function transformPendingPayoutsDataToPayoutLinksFormat(array $payoutsByPurpose)
    {
        $transformedData = array();

        foreach ($payoutsByPurpose as $purpose => $payouts)
        {
            foreach ($payouts as $payout)
            {
                array_push($transformedData, [
                    'contact_name' => $payout['contact_name'],
                    'amount'       => $payout['amount'],
                    'created_at'   => $payout['created_at'],
                ]);
            }
        }

        return $transformedData;
    }

    /**
     * Checks if a merchant is allowed to make a ICICI direct account payout with ICICI 2FA
     *
     * @param Balance\Entity $balance
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public static function checkIfMerchantIsAllowedForIciciDirectAccountPayoutWith2Fa(Balance\Entity $balance, Merchant\Entity $merchant): bool
    {
        $balanceType = $balance->getType();
        $accountType = $balance->getAccountType();
        $channel = $balance->getChannel();
        $isFeatureEnabled = $merchant->isFeatureEnabled(FeatureConstants::ICICI_2FA);

        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::PAYOUT_2FA_ICICI_CA_CHECK,
            [
                'balance_type'     => $balanceType,
                'account_type'     => $accountType,
                'channel'          => $channel,
                'isFeatureEnabled' => $isFeatureEnabled,
            ]
        );

        if ($balanceType === Balance\Type::BANKING and $accountType === Balance\AccountType::DIRECT and
            $channel === Channel::ICICI and $isFeatureEnabled === true)
        {
            return true;
        }

        return false;
    }

    public static function shouldBlockIciciDirectAccountPayoutsForNonBaasMerchants(Balance\Entity $balance, Merchant\Entity $merchant)
    {
        if (($balance->getType() !== Balance\Type::BANKING) or
            ($balance->getAccountType() !== Balance\AccountType::DIRECT) or
            ($balance->getChannel() !== Channel::ICICI))
        {
            return false;
        }

        $isBaasEnabled = $merchant->isFeatureEnabled(FeatureConstants::ICICI_BAAS);

        if ($isBaasEnabled === false)
        {
            $blockPayout = (bool) Admin\ConfigKey::get(Admin\ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA, false);

            if ($blockPayout == true)
            {
                return true;
            }
        }

        return false;
    }

    // since this is only used by ledger reverse shadow flow
    // this function assumes that we are only dealing with X banking balance based fund account payouts
    // this needs to be checked to decide how to call stork when merchant/customer wallet payouts are involved later.
    // check the call `new Stork(...)->list($merchantId)`, where the product is passed as 'banking' by default in the constructor
    public function checkIfPayoutFailedWebhookIsSubscribed(string $merchantId): bool
    {
        try
        {
            $response = (new Stork($this->mode ?? ModeConstants::LIVE, Product::BANKING))->list($merchantId);

            $this->trace->info(
                TraceCode::WEBHOOK_V2_PATH_STORK_OPERATION_SUCCESS,
                ['response' => $response]
            );

            foreach ($response['items'][0]['subscriptions'] as $subscription)
            {
                if ($subscription['eventmeta']['name'] === 'payout.failed')
                {
                    return true;
                }
            }

            return false;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::STORK_CALL_FOR_PAYOUT_FAILED_EVENT_STATUS_FAILED,
                ['merchant_id' => $merchantId]
            );
        }

        // For any exception or other issue, we shall assume that failed webhook is not subscribed as default.
        return false;
    }

    protected function fetchUserCommentFromSettingsEntity(Entity $payout): string
    {
        $accessor = Settings\Accessor::for($payout, Settings\Module::PAYOUTS);

        $userComment = $accessor->get(self::USER_COMMENT_KEY_IN_SETTINGS_FOR_ICICI_2FA);

        return $userComment;
    }

    protected function deleteUserCommentInSettingsEntity(Entity $payout)
    {
        $accessor = Settings\Accessor::for($payout, Settings\Module::PAYOUTS);

        $accessor->delete(self::USER_COMMENT_KEY_IN_SETTINGS_FOR_ICICI_2FA);

        $accessor->save();
    }

    public function negateODIfApplicable(Merchant\Entity $merchant, $merchantBalance)
    {
        $isOdFeatureEnabled = $merchant->isFeatureEnabled(FeatureConstants::REDUCE_OD_BALANCE_FOR_CA);

        if ($isOdFeatureEnabled === true)
        {
            $configuredOD = (int) (new AdminService)->getConfigKey(
                [
                    'key' => ConfigKey::RX_OD_BALANCE_CONFIGURED_FOR_MAGICBRICKS
                ]);

            $this->trace->info(TraceCode::DEDUCT_OD_FROM_GATEWAY_BALANCE, [
                'merchant'          => $merchant->getId(),
                'configured_od'     => $configuredOD,
                'gateway_balance'   => $merchantBalance,
                'available_balance' => $merchantBalance - $configuredOD
            ]);

            return $merchantBalance - $configuredOD;
        }

        return $merchantBalance;
    }

    public function negateMinimumBalance(Merchant\Entity $merchant, $merchantBalance, $balanceID)
    {
        try
        {
            $configValue = (new AdminService)->getConfigKey(
                [
                    'key' => ConfigKey::MINIMUM_BALANCE_QUEUING_THRESHOLD
                ]);
            if (array_key_exists($balanceID, $configValue) === true) {
                $configuredMinimumBalance = $configValue[$balanceID];

                $this->trace->info(TraceCode::DEDUCT_MINIMUM_BALANCE_FROM_GATEWAY_BALANCE, [
                    'merchant' => $merchant->getId(),
                    'configured_minimum_balance' => $configuredMinimumBalance,
                    'gateway_balance' => $merchantBalance,
                    'available_balance' => $merchantBalance - $configuredMinimumBalance
                ]);

                return $merchantBalance - $configuredMinimumBalance;
            }
            return $merchantBalance;
        }
        catch (\Exception $e)
        {
            return $merchantBalance;
        }
    }

    private function getPartnerBankHealthConfigKey($accountType, $mode, $channel): string
    {
        return strtolower($accountType . "_" . $channel . "_" . $mode);
    }

    private function holdIfBeneBankDown(Entity $payout): bool
    {
        $isBeneBankDown = $this->checkIfBeneBankIsDown($payout);

        if ($isBeneBankDown === true) {

            $payout->setStatus(Status::ON_HOLD);

            $payout->setQueuedReason(QueuedReasons::BENE_BANK_DOWN);

            $timeNow = Carbon::now(Timezone::IST);

            $updatedTime   = $timeNow->getTimestamp();

            $payout->setOnHoldAt($updatedTime);

            $payout->saveOrFail();

            $this->trace->info(
                TraceCode::PARTNER_BANK_ON_HOLD_MOVED_TO_BENE_BANK_DOWNTIME,
                [
                    'payout_id' => $payout->getId(),
                    'payout_status' => $payout->getStatus(),
                    'failure_reason' => $payout->getFailureReason(),
                ]);

            return true;
        }
        return false;
    }

    public function updateAttachmentsForPayoutServicePayout(string $payoutId, array $input)
    {
        return $this->payoutServiceUpdateAttachmentsClient->updateAttachments($payoutId, $input);
    }

    public function bulkUpdateAttachmentsForPayoutServicePayout(array $payoutIds, array $updateRequest)
    {
        return $this->payoutServiceUpdateAttachmentsClient->bulkUpdateAttachments($payoutIds, $updateRequest);
    }

    protected function isExperimentEnabled($experiment,$merchantId)
    {
        $app = $this->app;

        if(empty($merchantId) === false)
        {
            $variant = $app['razorx']->getTreatment($merchantId,
                                                    $experiment, $app['basicauth']->getMode() ?? Constants\Mode::LIVE);

            return ($variant === 'on');
        }

        return false;
    }

    public function initiateFundManagementPayoutIfRequired($input)
    {
        $channel = $input[Entity::CHANNEL];

        $destinationType = $input[PayoutConstants::DESTINATION_TYPE] ?? null;

        $destinationChannel = $input[PayoutConstants::DESTINATION_CHANNEL] ?? null;

        $merchantId = $input[Entity::MERCHANT_ID];

        $thresholds = $input[PayoutConstants::THRESHOLDS];

        $this->mutex->acquireAndReleaseStrict(
            'fund_management_payout_check_' . $merchantId . '_' . $channel,
            function() use ($merchantId, $channel, $thresholds, $destinationChannel, $destinationType) {
                /**
                 * @var Balance\Entity| BankingAccountStatement\Details\Entity $destinationDetails
                 * @var BankingAccountStatement\Details\Entity                 $sourceBasDetails
                 *
                 * Fetch & Validate destination account details and source Current Account details
                 *
                 * Kept $destinationDetails as polymorphic, since for POBO integration basDetails entity would be
                 * fetched from Payout Service.
                 */
                [$destinationDetails, $fundLoadingBankAccountDetails, $sourceBasDetails] =
                    $this->fetchMerchantAccountDetailsAndValidate($merchantId, $channel, $destinationChannel, $destinationType);

                $this->trace->info(TraceCode::FMP_DESTINATION_AND_CA_DETAILS_FETCHED, [
                    'merchant_id'                       => $merchantId,
                    'channel'                           => $channel,
                    'destination'                       => $destinationDetails->getEntityName(),
                    'destination_id'                    => $destinationDetails->getId(),
                    'destination_type'                  => $destinationDetails->getAccountType(),
                    'fund_loading_bank_account_details' => $fundLoadingBankAccountDetails,
                    'bas_details'                       => $sourceBasDetails->getId(),
                ]);

                if ($destinationDetails->getAccountType() === AccountType::SHARED)
                {
                    // Get Lite Balance from Ledger (In Paisa)
                    $destinationBalance = $destinationDetails->getSharedBankingBalanceFromLedgerWithoutFallbackOnApi();
                }
                else
                {
                    $destinationBalance = $this->getLatestGatewayBalanceForFundManagementPayout(collect(), $destinationDetails);
                }

                $destinationBalanceThreshold = $thresholds[PayoutConstants::LITE_BALANCE_THRESHOLD];

                $destinationBalanceThresholdWithAllowance = (int)
                round($destinationBalanceThreshold -
                      ($thresholds[PayoutConstants::LITE_DEFICIT_ALLOWED] / 10000) * $destinationBalanceThreshold);

                $destinationBalanceThresholdWithFiftyPercentAllowance = (int) round($destinationBalanceThreshold
                                                                                    - 0.5 * $destinationBalanceThreshold);

                // Add metric counter if Lite balance is less than fifty percent of Lite balance threshold
                if ($destinationBalance <= $destinationBalanceThresholdWithFiftyPercentAllowance)
                {
                    $this->trace->info(TraceCode::DESTINATION_BALANCE_LESS_THAN_FIFTY_PERCENT_OF_LITE_THRESHOLD, [
                        'merchant_id'                                   => $merchantId,
                        'channel'                                       => $channel,
                        'destination'                                   => $destinationDetails->getEntityName(),
                        'destination_id'                                => $destinationDetails->getId(),
                        'destination_type'                              => $destinationDetails->getAccountType(),
                        'destination_balance'                           => $destinationBalance,
                        'destination_balance_threshold'                 => $destinationBalanceThreshold,
                        'destination_threshold_fifty_percent_allowance' => $destinationBalanceThresholdWithFiftyPercentAllowance,
                    ]);

                    $this->trace->count(Metric::FMP_LESS_THAN_FIFTY_PERCENT_LITE_BALANCE_COUNT, [
                        'channel' => $channel,
                    ]);
                }

                $this->trace->info(TraceCode::DESTINATION_BALANCE_FETCHED_FOR_FMP, [
                    'merchant_id'                                  => $merchantId,
                    'channel'                                      => $channel,
                    'destination'                                  => $destinationDetails->getEntityName(),
                    'destination_id'                               => $destinationDetails->getId(),
                    'destination_type'                             => $destinationDetails->getAccountType(),
                    'destination_balance'                          => $destinationBalance,
                    'destination_balance_threshold'                => $destinationBalanceThreshold,
                    'destination_balance_threshold_with_allowance' => $destinationBalanceThresholdWithAllowance,
                ]);

                if ($destinationBalance >= $destinationBalanceThresholdWithAllowance)
                {
                    throw new Exception\LogicException(PayoutConstants::LITE_BALANCE_IS_ABOVE_THRESHOLD, null, [
                        'merchant_id'                                  => $merchantId,
                        'channel'                                      => $channel,
                        'destination'                                  => $destinationDetails->getEntityName(),
                        'destination_id'                               => $destinationDetails->getId(),
                        'destination_type'                             => $destinationDetails->getAccountType(),
                        'destination_balance'                          => $destinationBalance,
                        'destination_balance_threshold'                => $destinationBalanceThreshold,
                        'destination_balance_threshold_with_allowance' => $destinationBalanceThresholdWithAllowance,
                    ]);
                }

                // Get FMPs within retrieval period
                $retrievalThreshold = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::FUND_MANAGEMENT_PAYOUTS_RETRIEVAL_THRESHOLD]);

                if (empty($retrivalThreshold) === true)
                {
                    $retrievalThreshold = self::FUND_MANAGEMENT_PAYOUTS_RETRIEVAL_THRESHOLD; // In secs
                }

                $fundManagementPayouts = $this->repo->payout->fetchFundManagementPayoutsWithinRange(
                    $merchantId, $retrievalThreshold);

                $fundManagementPayoutDetails = [];

                $fundManagementPayouts->each(function($fundManagementPayout, $key) use (&$fundManagementPayoutDetails) {
                    $fundManagementPayoutDetail[Entity::ID]     = $fundManagementPayout->getId();
                    $fundManagementPayoutDetail[Entity::STATUS] = $fundManagementPayout->getStatus();

                    $fundManagementPayoutDetails[] = $fundManagementPayoutDetail;
                });

                $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUTS_FOUND_WITHIN_RETRIEVAL_THRESHOLD, [
                    'merchant_id'                     => $merchantId,
                    'channel'                         => $channel,
                    'fund_management_payouts_details' => $fundManagementPayoutDetails,
                    'fund_management_payouts_count'   => count($fundManagementPayoutDetails),
                    'fmp_retrieval_threshold'         => $retrievalThreshold,
                ]);

                // Calculate Offset Amount for initiating FMPs
                $offsetAmount = $this->calculateOffsetAmountForFundManagementPayout(
                    $fundManagementPayouts, $destinationBalance, $thresholds, $merchantId, $channel);

                if ($offsetAmount < 100)  // offset should be greater than 1 rupee because it is the min payout amount.
                {
                    throw new Exception\LogicException(PayoutConstants::INVALID_OFFSET_AMOUNT_FOR_FMP, null, [
                        'merchant_id'     => $merchantId,
                        'channel'         => $channel,
                    ]);
                }

                // Fetch Latest source direct account balance
                $gatewayBalance = $this->getLatestGatewayBalanceForFundManagementPayout($fundManagementPayouts, $sourceBasDetails);

                /**
                 * Get minimum amount to be maintained at Current Account
                 * Todo:: Add merchant level configuration for this.
                 */
                $fmpGatewayBalanceThreshold = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::FUND_MANAGEMENT_PAYOUTS_GATEWAY_BALANCE_THRESHOLD]);

                if (empty($fmpGatewayBalanceThreshold) === true)
                {
                    $fmpGatewayBalanceThreshold = self::FUND_MANAGEMENT_PAYOUTS_GATEWAY_BALANCE_THRESHOLD; // In paisa
                }

                $this->trace->info(TraceCode::CA_BALANCE_FETCHED_FOR_FMP, [
                    'merchant_id'               => $merchantId,
                    'channel'                   => $channel,
                    'gateway_balance'           => $gatewayBalance,
                    'offset_amount'             => $offsetAmount,
                    'gateway_balance_threshold' => $fmpGatewayBalanceThreshold,
                ]);

                if (($gatewayBalance <= $offsetAmount) or
                    ($gatewayBalance - $offsetAmount < $fmpGatewayBalanceThreshold))
                {
                    if ($gatewayBalance <= $fmpGatewayBalanceThreshold)
                    {
                        throw new Exception\LogicException(PayoutConstants::CA_BALANCE_NOT_ENOUGH_FOR_FMP, null, [
                            'merchant_id'               => $merchantId,
                            'channel'                   => $channel,
                            'gateway_balance'           => $gatewayBalance,
                            'offset_amount'             => $offsetAmount,
                            'amount_difference'         => $offsetAmount - $gatewayBalance,
                            'gateway_balance_threshold' => $fmpGatewayBalanceThreshold,
                        ]);
                    }
                    else
                    {
                        $offsetAmount = $gatewayBalance - $fmpGatewayBalanceThreshold;
                    }
                }

                //Create Input for FMPs
                $fmpInput = $this->createInputForFundManagementPayouts($fundLoadingBankAccountDetails, $merchantId, $sourceBasDetails, $offsetAmount);

                $preferredMode = $fmpInput[Entity::MODE];

                $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CREATION_INPUT, [
                    'merchant_id'         => $merchantId,
                    'channel'             => $channel,
                    'input'               => $fmpInput,
                    'final_offset_amount' => $offsetAmount,
                ]);

                // Calculate the number of FMPs and its Amount
                $fmpConfiguration = $this->calculateNumberOfFundManagementPayoutsBasedOnThresholds($thresholds, $preferredMode, $offsetAmount);

                $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CREATION_CONFIGURATION, [
                    'merchant_id'                => $merchantId,
                    'channel'                    => $channel,
                    'payout_count_to_amount_map' => $fmpConfiguration,
                ]);

                // Dispatch FMPs for creation
                $this->dispatchFundManagementPayouts($merchantId, $channel, $fmpInput, $fmpConfiguration);
            },
            600,
            ErrorCode::BAD_REQUEST_ANOTHER_FUND_MANAGEMENT_REQUEST_IN_PROGRESS
        );
    }

    public function fetchMerchantAccountDetailsAndValidate(
        $merchantId, $channel, $destinationChannel, $destinationType)
    {
        $isDestinationLite = (is_null($destinationType) or
                              ($destinationType === AccountType::SHARED));

        // Fetch Destination balance details
        if ($isDestinationLite === true)
        {
            /* @var Merchant\Entity $merchant*/
            $merchant = $this->repo->merchant->findByPublicId($merchantId);

            $isLive = (($merchant->isLive() === true) or
                       ((new Merchant\Core())->isXVaActivated($merchant) === true));

            if ($isLive === false)
            {
                throw new BadRequestValidationFailureException('X is not live for merchant_id ' . $merchantId);
            }

            // Fetch Lite Accounts For Fund Loading
            $virtualAccounts = $this->repo->virtual_account->fetchActiveBankingVirtualAccountsFromMerchantId($merchantId);

            $liteBalanceEntity = null;
            $virtualAccountIds = [];
            $fundLoadingBankAccountDetails = [];

            /* @var VirtualAccount\Entity $virtualAccount*/
            foreach ($virtualAccounts as $virtualAccount)
            {
                $virtualAccountIds[] = $virtualAccount->getId();

                /* @var BankAccount\Entity $bankAccount*/
                $bankAccount = $virtualAccount->bankAccount;

                if (isset($bankAccount) === false)
                {
                    continue;
                }

            $fundLoadingBankAccountDetails = [
                BankAccount\Entity::ACCOUNT_NUMBER  => $bankAccount->getAccountNumber(),
                BankAccount\Entity::IFSC            => $bankAccount->getIfscCode(),
                BankAccount\Entity::NAME            => $bankAccount->getName(),
            ];

                $trimmedBankAccountDetails = $this->trimSpaces($fundLoadingBankAccountDetails);

                try
                {
                    // Validate Bank Account before initiating Fund Management Payouts
                    (new BankAccount\Validator())->validateInput('addFundAccountBankAccount', $trimmedBankAccountDetails);
                }
                catch (\Exception $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        null,
                        TraceCode::BANK_ACCOUNT_DETAILS_NOT_SUITABLE_FOR_FUND_MANAGEMENT,
                        [
                            'account_details'     => $trimmedBankAccountDetails,
                            'merchant_id'         => $merchantId,
                            'channel'             => $channel,
                            'destination_type'    => $destinationType,
                            'destination_channel' => $destinationChannel,
                        ]);

                    $fundLoadingBankAccountDetails = [];

                    continue;
                }

                $liteBalanceEntity = $virtualAccount->balance;

                break;
            }

            if ((empty($fundLoadingBankAccountDetails) === true) or
                (isset($liteBalanceEntity) === false))
            {
                throw new BadRequestValidationFailureException('No Suitable Bank Account found for Fund Loading for ' . $merchantId, null, [
                    'channel'                  => $channel,
                    'lite_account_ids_scanned' => $virtualAccountIds,
                    'lite_balance_Entity'      => optional($liteBalanceEntity)->getId(),
                    'count_of_lite_account'    => count($virtualAccountIds),
                    'destination_type'         => $destinationType,
                    'destination_channel'      => $destinationChannel,
                ]);
            }
        }
        else
        {
            // Fetch destination direct/rx_wallet banking_account_statement_details for merchantId and channel
            // This call needs to go to PS in case of rx_wallet bas_details
            $destinationBasDetails = $this->repo->banking_account_statement_details->getDirectBasDetailEntityByMerchantIdAndChannel($merchantId, $destinationChannel);

            if (isset($destinationBasDetails) === false)
            {
                $this->trace->error(TraceCode::BAS_DETAILS_NOT_FOUND, [
                    'merchant_id' => $merchantId,
                    'channel'     => $channel,
                    'type'        => 'destination',
                ]);

                throw new BadRequestValidationFailureException('Bas Details not found for ' . $merchantId, null, [
                    'merchant_id' => $merchantId,
                    'channel'     => $channel,
                    'type'        => 'destination',
                ]);
            }

            // Get Destination Bank Account Name from Merchant Billing Label
            $merchantBillingLabel = $destinationBasDetails->merchant->getBillingLabel();

            // Remove all characters other than a-z, A-Z, 0-9 and space
            $formattedLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantBillingLabel);

            $fundLoadingBankAccountDetails = [
                BankAccount\Entity::ACCOUNT_NUMBER => $destinationBasDetails->getAccountNumber(),
                BankAccount\Entity::IFSC           => PayoutConstants::FMP_DESTINATION_CHANNEL_TO_IFSC_MAP[strtolower($destinationChannel)],
                BankAccount\Entity::NAME           => str_limit($formattedLabel, 120, ''),
            ];

            try
            {
                // Validate Bank Account before initiating Fund Management Payouts
                (new BankAccount\Validator())->validateInput('addFundAccountBankAccount',
                                                             $this->trimSpaces($fundLoadingBankAccountDetails));
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::BANK_ACCOUNT_DETAILS_NOT_SUITABLE_FOR_FUND_MANAGEMENT,
                    [
                        'account_details'     => $this->trimSpaces($fundLoadingBankAccountDetails),
                        'merchant_id'         => $merchantId,
                        'channel'             => $channel,
                        'destination_type'    => $destinationType,
                        'destination_channel' => $destinationChannel,
                    ]);

                throw new BadRequestValidationFailureException('No Suitable basDetails found for Fund Loading for ' . $merchantId, null, [
                    'channel'                  => $channel,
                    'destination_type'         => $destinationType,
                    'destination_channel'      => $destinationChannel,
                ]);
            }
        }

        // Fetch source direct banking_account_statement_details for merchantId and channel
        $sourceBasDetails = $this->repo->banking_account_statement_details->getDirectBasDetailEntityByMerchantIdAndChannel($merchantId, $channel);

        if (isset($sourceBasDetails) === false)
        {
            $this->trace->error(TraceCode::BAS_DETAILS_NOT_FOUND, [
                'merchant_id' => $merchantId,
                'channel'     => $channel,
                'type'        => 'source',
            ]);

            throw new BadRequestValidationFailureException('Bas Details not found for ' . $merchantId, null, [
                'merchant_id' => $merchantId,
                'channel'     => $channel,
                'type'        => 'source',
            ]);
        }

        if ($isDestinationLite === true)
        {
            $this->validateBankingAccountTpv($sourceBasDetails, $liteBalanceEntity);

            return [$liteBalanceEntity, $fundLoadingBankAccountDetails, $sourceBasDetails];
        }

        return [$destinationBasDetails, $fundLoadingBankAccountDetails, $sourceBasDetails];
    }

    public function validateBankingAccountTpv($basDetails, $liteBalanceEntity)
    {
        $disableTpvFeature = $basDetails->merchant->isFeatureEnabled(Feature\Constants::DISABLE_TPV_FLOW);

        if ($disableTpvFeature === false)
        {
            $bankingAccountTpv = $this->repo->banking_account_tpv->getApprovedActiveTpvAccountWithPayerAccountNumber(
                $basDetails->getMerchantId(),
                $liteBalanceEntity->getId(),
                $basDetails->getAccountNumber()
            );

            if (isset($bankingAccountTpv) === false)
            {
                $this->trace->error(TraceCode::FUND_MANAGEMENT_PAYOUT_BANKING_ACCOUNT_TPV_FAILURE, [
                    'disable_tpv_feature' => false,
                    'merchant_id'         => $basDetails->getMerchantId(),
                    'balance_id'          => $liteBalanceEntity->getId(),
                ]);

                throw new BadRequestValidationFailureException('Banking Account TPV not setup for FMP.', null, [
                    'merchant_id' => $basDetails->getMerchantId(),
                    'balance_id'  => $basDetails->getBalanceId(),
                    'channel'     => $liteBalanceEntity->getId(),
                ]);
            }
        }
    }

    public function calculateOffsetAmountForFundManagementPayout(
        $fundManagementPayouts,
        $liteBalance,
        $thresholds,
        $merchantId,
        $channel)
    {
        $liteBalanceThreshold = $thresholds[PayoutConstants::LITE_BALANCE_THRESHOLD];

        $offsetAmount = ($liteBalanceThreshold - $liteBalance);

        $netAmountAlreadyDone = 0;

        $startTime = Carbon::now(Timezone::IST)
                           ->subSeconds($thresholds[PayoutConstants::FMP_CONSIDERATION_THRESHOLD])->getTimestamp();

        $endTime   = Carbon::now(Timezone::IST)->getTimestamp();

        $countOfOnHoldPayouts = 0;
        $countOfOnHoldPayoutsConsidered = 0;
        $countOfInitiatedPayouts = 0;
        $countOfInitiatedPayoutsConsidered = 0;
        $countOfProcessedPayouts = 0;

        foreach ($fundManagementPayouts as $fundManagementPayout)
        {
            switch ($fundManagementPayout->getStatus())
            {
                case Status::ON_HOLD:
                    if (($fundManagementPayout->getCreatedAt() >= $startTime) and
                        ($fundManagementPayout->getCreatedAt() <= $endTime))
                    {
                        $offsetAmount -= $fundManagementPayout->getAmount();

                        $netAmountAlreadyDone += $fundManagementPayout->getAmount();

                        $countOfOnHoldPayoutsConsidered++;
                    }

                    $countOfOnHoldPayouts++;

                    break;

                case Status::INITIATED:
                    if (($fundManagementPayout->getInitiatedAt() >= $startTime) and
                        ($fundManagementPayout->getInitiatedAt() <= $endTime))
                    {
                        $offsetAmount -= $fundManagementPayout->getAmount();

                        $netAmountAlreadyDone += $fundManagementPayout->getAmount();

                        $countOfInitiatedPayoutsConsidered++;
                    }

                    $countOfInitiatedPayouts++;

                    break;

                case Status::PROCESSED:
                    $netAmountAlreadyDone += $fundManagementPayout->getAmount();

                    $countOfProcessedPayouts++;

                    break;
            }
        }

        $offsetAmount = max($offsetAmount, 0);

        $availableAmount = $thresholds[PayoutConstants::TOTAL_AMOUNT_THRESHOLD] - $netAmountAlreadyDone;

        $this->trace->info(TraceCode::FMP_OFFSET_CALCULATIONS_PARAMETERS, [
            'merchant_id'                           => $merchantId,
            'channel'                               => $channel,
            'filter_start_time'                     => $startTime,
            'filter_end_time'                       => $endTime,
            'count_of_on_hold_payouts'              => $countOfOnHoldPayouts,
            'count_of_on_hold_payouts_considered'   => $countOfOnHoldPayoutsConsidered,
            'count_of_initiated_payouts'            => $countOfInitiatedPayouts,
            'count_of_initiated_payouts_considered' => $countOfInitiatedPayoutsConsidered,
            'count_of_processed_payouts'            => $countOfProcessedPayouts,
            'offset_amount'                         => $offsetAmount,
            'net_amount_already_done'               => $netAmountAlreadyDone,
            'total_amount_threshold'                => $thresholds[PayoutConstants::TOTAL_AMOUNT_THRESHOLD],
            'available_amount'                      => $availableAmount,
        ]);

        if ($offsetAmount >= $availableAmount)
        {
            return $availableAmount;
        }

        return $offsetAmount;
    }

    public function fetchFundManagementPayoutModeForMerchantViaFts($merchantId, $directBalance, $offsetAmount)
    {
        $channel = $directBalance->getChannel();

        $bankingAccount = $directBalance->bankingAccount;

        $ftsFundAccountId = optional($bankingAccount)->getFtsFundAccountId();

        if (empty($ftsFundAccountId) === true and
            (in_array($channel, BankingAccount\Core::$directChannelsForConnectBanking) === true))
        {
            $accountNumber = $directBalance->getAccountNumber();

            /** @var BankingAccountService|MockBankingAccountService $bankingAccountService */
            $bankingAccountService = app('banking_account_service');

            $ftsFundAccountId = $bankingAccountService->fetchFtsFundAccountIdFromBas($merchantId, $channel, $accountNumber);
        }

        /** @var \RZP\Services\FTS\FundTransfer $transferService */
        $transferService = App::getFacadeRoot()['fts_fund_transfer'];

        $input = [
            Entity::MERCHANT_ID                       => $merchantId,
            FTSConstants::OFFSET_AMOUNT               => $offsetAmount,
            FTSConstants::PREFERRED_SOURCE_ACCOUNT_ID => (int) $ftsFundAccountId,
            FTSConstants::ACTION                      => PayoutConstants::FUND_MANAGEMENT_PAYOUT,
        ];

        $this->trace->info(TraceCode::FTS_MODE_FETCH_PAYLOAD, $input);

        $transferService->setRequestTimeout(10);

        return $transferService->requestModeFromFts($input);
    }

    public function createInputForFundManagementPayouts($fundLoadingBankAccountDetails, $merchantId, $basDetails, $offsetAmount)
    {
        $compositePayoutPayload = [
            Entity::FUND_ACCOUNT         => [
                FundAccount\Entity::ACCOUNT_TYPE => FundAccount\Type::BANK_ACCOUNT,
                FundAccount\Type::BANK_ACCOUNT   => $fundLoadingBankAccountDetails,
                FundAccount\Entity::CONTACT      => [
                    Contact\Entity::TYPE => Contact\Type::SELF,
                ],
            ],
            Entity::CURRENCY             => Currency::INR,
            Entity::BALANCE_ID           => $basDetails->getBalanceId(),
            Entity::PURPOSE              => Purpose::RZP_FUND_MANAGEMENT,
            Entity::QUEUE_IF_LOW_BALANCE => false,
            Entity::NARRATION            => Purpose::RZP_FUND_MANAGEMENT,
        ];

        $preferredMode = Mode::IMPS;

        try
        {
            $ftsResponse = $this->fetchFundManagementPayoutModeForMerchantViaFts(
                $merchantId, $basDetails->balance, $offsetAmount);

            $preferredMode = $ftsResponse[FTSConstants::SELECTED_MODE];
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::FTS_MODE_FETCH_FAILED,
                [
                    'merchant_id' => $merchantId,
                    'offset_amount' => $offsetAmount
                ]);

            $this->trace->count(Metric::FTS_MODE_FETCH_FAILURES_COUNT);
        }

        $compositePayoutPayload[Entity::MODE] = $preferredMode;

        // Get Contact Name from Merchant Billing Label
        $merchantBillingLabel = $basDetails->merchant->getBillingLabel();

        // Remove all characters other than a-z, A-Z, 0-9 and space
        $formattedLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantBillingLabel);

        // If formattedLabel is non-empty, pick the first 50 chars, else fallback to 'Razorpay'
        $formattedLabel = ($formattedLabel ? $formattedLabel : 'Razorpay');

        $compositePayoutPayload[Entity::FUND_ACCOUNT][FundAccount\Entity::CONTACT][Contact\Entity::NAME] =
            str_limit($formattedLabel, 50, '');

        return $compositePayoutPayload;
    }

    public function calculateNumberOfFundManagementPayoutsBasedOnThresholds($thresholds, $preferredMode, $offsetAmount)
    {
        $neftThreshold = $thresholds[PayoutConstants::NEFT_THRESHOLD];

        switch ($preferredMode)
        {
            case Mode::NEFT:
                $countOfFmps = (int) floor($offsetAmount / $neftThreshold);

                return $this->generatePayoutAmountToCountMap($countOfFmps, $offsetAmount, $neftThreshold);

            case Mode::IMPS:
                $impsThreshold = NodalAccount::MAX_IMPS_AMOUNT * 100;

                $countOfFmps = (int) floor($offsetAmount / $impsThreshold);

                return $this->generatePayoutAmountToCountMap($countOfFmps, $offsetAmount, $impsThreshold);

            default:
                $countOfFmps = (int) floor($offsetAmount / self::DEFAULT_FMP_THRESHOLD);

                return $this->generatePayoutAmountToCountMap($countOfFmps, $offsetAmount, self::DEFAULT_FMP_THRESHOLD);
        }
    }

    public function generatePayoutAmountToCountMap($count, $totalAmount, $amountThreshold)
    {
        $amountToCountMap = [];

        $remainingAmount = $totalAmount - ($count * $amountThreshold);

        if ($count === 0)
        {
            $amountToCountMap[$totalAmount] = 1;
        }
        elseif ($remainingAmount < 100)
        {
            $amountToCountMap[$amountThreshold] = $count;
        }
        else
        {
            $amountToCountMap[$amountThreshold] = $count;

            $amountToCountMap[$remainingAmount] = 1;
        }

        return $amountToCountMap;
    }

    public function dispatchFundManagementPayouts($merchantId, $channel, $fmpInput, $fmpConfiguration)
    {
        $firstJobDispatch = true;

        foreach ($fmpConfiguration as $payoutAmount => $payoutCount)
        {
            $fmpInput[Entity::AMOUNT] = $payoutAmount;

            $params = [
                Entity::MERCHANT_ID                    => $merchantId,
                Entity::CHANNEL                        => $channel,
                PayoutConstants::PAYOUT_CREATE_INPUT   => $fmpInput,
            ];

            do
            {
                try
                {
                    $params[PayoutConstants::FMP_UNIQUE_IDENTIFIER] = UniqueIdEntity::generateUniqueId();

                    $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CREATION_DISPATCH_INITIATE, $params);

                    if ($firstJobDispatch === true)
                    {
                        FundManagementPayoutInitiate::dispatch($this->mode, $params);

                        $firstJobDispatch = false;
                    }
                    else
                    {
                        // Adding delay of 2 secs (accounting for replica lag) so that fund account dedupe
                        // happens properly during FMP creation
                        FundManagementPayoutInitiate::dispatch($this->mode, $params)->delay(2);
                    }

                    $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CREATION_DISPATCH_SUCCESS, $params);
                }
                catch (\Throwable $throwable)
                {
                    $this->trace->traceException(
                        $throwable,
                        Trace::ERROR,
                        TraceCode::FUND_MANAGEMENT_PAYOUT_CREATION_DISPATCH_FAILURE,
                        $params);

                    $this->trace->count(Metric::FUND_MANAGEMENT_PAYOUT_CREATION_DISPATCH_FAILURE_COUNT);
                }

                $payoutCount--;

            } while ($payoutCount > 0);
        }
    }

    /**
     * This function should not be a part of this class.
     * Ideally this should be at a central place that governs whether a token has acess to a resource
     * Since no new changes are being accepted in BasicAuth, adding it as a function here. Needs to be refactored.
     */
    private function isXPartnerApproval()
    {
        /** @var $auth BasicAuth */
        $auth = $this->app['basicauth'];

        if ($auth->getAccessTokenId() === null)
        {
            return false;
        }

        $scopes = $auth->getTokenScopes();

        if (empty($scopes) === true or (in_array(OAuthScopes::RX_PARTNER_READ_WRITE, $scopes, true) === false))
        {
            return false;
        }

        return $auth->getMerchant()->isFeatureEnabled(FeatureConstants::ENABLE_APPROVAL_VIA_OAUTH) === true;
    }

    public function caFundManagementPayoutCheck($merchantIDs): array
    {
        $failedMerchantIds     = [];
        $successfulMerchantIds = [];

        $redis = $this->app['redis'];
        foreach ($merchantIDs as $merchantId)
        {
            try
            {
                $keyValue = json_decode($redis->hget(self::CA_FUND_MANAGEMENT_PAYOUT_BALANCE_CONFIG_REDIS_KEY, $merchantId), true);

                (new Validator())->validateInput(Validator::UPDATE_BALANCE_MANAGEMENT_CONFIG, $keyValue);

                $jobRequest = [
                    Entity::CHANNEL                      => $keyValue[PayoutConstants::CHANNEL],
                    Entity::MERCHANT_ID                  => $merchantId,
                    PayoutConstants::DESTINATION_CHANNEL => $keyValue[PayoutConstants::DESTINATION_CHANNEL] ?? null,
                    PayoutConstants::DESTINATION_TYPE    => $keyValue[PayoutConstants::DESTINATION_TYPE] ?? null,
                    PayoutConstants::THRESHOLDS          => [
                        PayoutConstants::NEFT_THRESHOLD              => $keyValue[PayoutConstants::NEFT_THRESHOLD],
                        PayoutConstants::LITE_BALANCE_THRESHOLD      => $keyValue[PayoutConstants::LITE_BALANCE_THRESHOLD],
                        PayoutConstants::LITE_DEFICIT_ALLOWED        => $keyValue[PayoutConstants::LITE_DEFICIT_ALLOWED],
                        PayoutConstants::FMP_CONSIDERATION_THRESHOLD => $keyValue[PayoutConstants::FMP_CONSIDERATION_THRESHOLD],
                        PayoutConstants::TOTAL_AMOUNT_THRESHOLD      => $keyValue[PayoutConstants::TOTAL_AMOUNT_THRESHOLD],
                    ],
                ];

                $this->trace->info(
                    TraceCode::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_REQUEST, $jobRequest);

                FundManagementPayoutCheck::dispatch($this->mode, $jobRequest);

                $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_DISPATCHED);
                $successfulMerchantIds[] = $merchantId;
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_DISPATCH_FAILED,
                    [
                        Entity::MERCHANT_ID => $merchantId,
                    ]);

                $this->trace->count(Metric::FUND_MANAGEMENT_PAYOUT_CRON_DISPATCH_FAILURES_COUNT, [
                    Entity::MERCHANT_ID => $merchantId,
                ]);

                $failedMerchantIds[] = $merchantId;

                continue;
            }
        }

        return [
            'dispatch_failed'     => $failedMerchantIds,
            'dispatch_successful' => $successfulMerchantIds,
        ];
    }

    public function getCABalanceManagementConfig($merchantId): array
    {
        $redis = $this->app['redis'];

        $keyValue = json_decode($redis->hget(self::CA_FUND_MANAGEMENT_PAYOUT_BALANCE_CONFIG_REDIS_KEY, $merchantId), true);

        if (empty($keyValue) === false)
        {
            $this->trace->info(
                TraceCode::FUND_MANAGEMENT_BALANCE_CONFIG_GET_SUCCESSFUL,
                [
                    'config'      => $keyValue,
                    'configKey'   => self::CA_FUND_MANAGEMENT_PAYOUT_BALANCE_CONFIG_REDIS_KEY,
                    'merchant_id' => $merchantId
                ]);

            return $keyValue;
        }

        return [self::MESSAGE => 'config for ' . $merchantId . ' not present'];
    }

    public function updateCABalanceManagementConfig($merchantId, $config) : void
    {
        $redis = $this->app['redis'];

        $redis->hset(self::CA_FUND_MANAGEMENT_PAYOUT_BALANCE_CONFIG_REDIS_KEY, $merchantId, json_encode($config));

        $this->trace->info(
            TraceCode::FUND_MANAGEMENT_BALANCE_CONFIG_SET_SUCCESSFUL,
            [
                'new_config'  => $config,
                'key'         => $redis->hget(self::CA_FUND_MANAGEMENT_PAYOUT_BALANCE_CONFIG_REDIS_KEY, $merchantId),
                'configKey'   => self::CA_FUND_MANAGEMENT_PAYOUT_BALANCE_CONFIG_REDIS_KEY,
                'merchant_id' => $merchantId
            ]);
    }

    public function fetchValidDirectAccountsForSmartRouting(
        &$accountDetailsMap, &$validDirectAccounts, $input, $merchant, $fundAccountType = "")
    {
        // Fetch Active BasDetails
        $activeBasDetails = $this->repo->banking_account_statement_details->getActiveDirectAccountsForMerchantId($merchant->getId());

        /** @var  $activeBasDetail  BankingAccountStatement\Details\Entity */
        foreach ($activeBasDetails as $activeBasDetail)
        {
            $channel = $activeBasDetail->getChannel();

            $bankingAccount = $activeBasDetail->balance->bankingAccount;

            $ftsFundAccountId = optional($bankingAccount)->getFtsFundAccountId();

            // Check if the channel and mode combination is allowed
            $isChannelAndModeValid = (new Validator())->validateChannelAndModeForPayouts(
                $merchant->getId(), $channel, $fundAccountType, $input[Entity::MODE], AccountType::DIRECT);

            if ($isChannelAndModeValid === false)
            {
                continue;
            }

            if ((empty($ftsFundAccountId) === true) and
                (in_array($channel, BankingAccount\Core::$directChannelsForConnectBanking) === true))
            {
                $accountNumber = $activeBasDetail->getAccountNumber();

                try
                {
                    $ftsFundAccountId = app('banking_account_service')->fetchFtsFundAccountIdFromBas(
                        $merchant->getId(), $channel, $accountNumber);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        null,
                        TraceCode::SMART_ROUTING_BAS_FETCH_FAILED,
                        [
                            'merchant_id'    => $merchant->getId(),
                            'bas_details_id' => $activeBasDetail->getId(),
                            'balance_id'     => $activeBasDetail->getBalanceId(),
                        ]);

                    $this->trace->count(Metric::SMART_ROUTING_BAS_FETCH_FAILURES_COUNT);

                    throw $ex;
                }
            }

            if (empty($ftsFundAccountId) === false)
            {
                $accountDetailsMap[$activeBasDetail->getBalanceId()] = [
                    Entity::CHANNEL                           => $channel,
                    PayoutConstants::BALANCE_ENTITY           => $activeBasDetail->balance,
                    PayoutConstants::BALANCE                  => $activeBasDetail->getGatewayBalance(),
                    FTSConstants::PREFERRED_SOURCE_ACCOUNT_ID => (int) $ftsFundAccountId,
                ];

                $validDirectAccounts++;
            }
        }
    }

    public function assertPayoutsSmartRoutingFeasibility($validLiteAccounts, $validDirectAccounts, $input, $merchant)
    {
        if ($validLiteAccounts >= 2)
        {
            // Todo:: This section will be implemented once POBO model is live and we have multiple shared
            // accounts for a merchant.

            throw new LogicException('Merchant shouldn\'t have more than 1 lite account.', null, [
                'merchant_id'  => $merchant->getId(),
                'payout_input' => $input,
            ]);
        }

        if (($validDirectAccounts === 0) and
            ($validLiteAccounts === 0))
        {
            throw new LogicException('Merchant doesn\'t have any viable channels for routing.', null, [
                'merchant_id'  => $merchant->getId(),
                'payout_input' => $input,
            ]);
        }
    }

    public function initiateSmartRoutingViaFts($merchantId, $accountDetailsMap, $input)
    {
        foreach ($accountDetailsMap as $balanceId => $accountDetails)
        {
            unset($accountDetails[PayoutConstants::BALANCE_ENTITY]);

            $routingFtsInput[AccountType::DIRECT][$balanceId] = $accountDetails;
        }

        $routingFtsInput += [
            Entity::MODE        => $input[Entity::MODE],
            Entity::AMOUNT      => (int) $input[Entity::AMOUNT], // In paisa
            Entity::MERCHANT_ID => $merchantId
        ];

        $this->trace->info(TraceCode::FTS_SMART_ROUTING_PAYLOAD, $routingFtsInput);

        /** @var \RZP\Services\FTS\FundTransfer $transferService */
        $transferService = App::getFacadeRoot()['fts_fund_transfer'];

        $transferService->setRequestTimeout(1);

        try
        {
            return $transferService->smartRoutingThroughFts($routingFtsInput);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FTS_SMART_ROUTING_FAILED,
                [
                    'merchant_id' => $merchantId,
                ]);

            $this->trace->count(Metric::FTS_SMART_ROUTING_FAILURES_COUNT);

            throw $ex;
        }
    }

    public function validateAndTranslateAccountNumberWithSmartRoutingForBanking(
        array &$input, $merchant, $fundAccountType = "") : Balance\Entity
    {
        $startTime = microtime(true);

        // Validates input has valid Amount and Mode.
        (new Validator())->setStrictFalse()->validateInput(Validator::BEFORE_SMART_ROUTING_PAYOUT, $input);

        $merchantId = $input[Balance\Entity::MERCHANT_ID] ?? null;

        $merchantId = ($merchantId === null) ? $merchant->getId() : $merchantId;

        try
        {
            $balance = $this->fetchBalanceBasedOnSmartRoutingForBanking($input, $merchant, $fundAccountType);

            $input[Balance\Entity::BALANCE_ID] = $balance->getId();

            array_pull($input, Balance\Entity::ACCOUNT_NUMBER);

            array_pull($input, Balance\Entity::MERCHANT_ID);

            $routingResponseTime = get_diff_in_millisecond($startTime);

            $this->trace->info(TraceCode::SMART_ROUTING_FOR_BANKING_RESPONSE_TIME, [
                PayoutConstants::MERCHANT_ID => $merchantId,
                Entity::BALANCE_ID           => $input[Balance\Entity::BALANCE_ID],
                Balance\Entity::ACCOUNT_TYPE => $balance->getAccountType(),
                'routing_response_time'      => $routingResponseTime,
            ]);

            $this->trace->histogram(
                Metric::PAYOUTS_SMART_ROUTING_COMPLETED_DURATION_MS, $routingResponseTime, [
                'is_error' => false
            ]);

            $this->trace->count(Metric::TOTAL_SMART_ROUTING_PAYOUTS_COUNT);

            return $balance;
        }
        catch (\Throwable $ex)
        {
            $routingResponseTime = get_diff_in_millisecond($startTime);

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::SMART_ROUTING_FOR_BANKING_ERROR_RESPONSE_TIME, [
                PayoutConstants::MERCHANT_ID => $merchantId,
                'routing_response_time'      => $routingResponseTime,
            ]);

            $this->trace->histogram(Metric::PAYOUTS_SMART_ROUTING_COMPLETED_DURATION_MS, $routingResponseTime, [
                'is_error' => true
            ]);

            throw $ex;
        }
    }

    public function fetchBalanceBasedOnSmartRoutingForBanking($input, $merchant, $fundAccountType = "") : Balance\Entity
    {
        /**
         * Stores account identifier to account details Map,
         * For direct accounts, the account identifier is preferred_source_account_id
         */
        $accountDetailsMap = [];

        // Count of valid Direct Accounts for Smart Routing
        $validDirectAccounts = 0;

        $this->fetchValidDirectAccountsForSmartRouting(
            $accountDetailsMap, $validDirectAccounts, $input, $merchant, $fundAccountType);

        // Fetch Lite balances
        $liteBalances = $this->repo->balance->getMerchantBalancesByTypeAndAccountType(
            $merchant->getId(), Balance\Type::BANKING, AccountType::SHARED, $this->mode);

        // Count of valid Lite Accounts for Smart Routing
        $validLiteAccounts = count($liteBalances);

        $this->trace->info(TraceCode::PAYOUT_SMART_ROUTING_ACCOUNTS, [
            'active_direct_accounts' => $validDirectAccounts,
            'active_lite_accounts'   => $validLiteAccounts,
        ]);

        $this->assertPayoutsSmartRoutingFeasibility($validLiteAccounts, $validDirectAccounts, $input, $merchant);

        // Check if there is only one active account, in that case we choose it as default and don't call FTS
        if (($validLiteAccounts + $validDirectAccounts) === 1)
        {
            if ($validLiteAccounts === 1)
            {
                return $liteBalances->first();
            }
            else
            {
                return array_first($accountDetailsMap)[PayoutConstants::BALANCE_ENTITY];
            }
        }

        // Make a call to FTS to fetch channel according to routing rule engine
        $routingDetails = $this->initiateSmartRoutingViaFts($merchant->getId(), $accountDetailsMap, $input);

        switch ($routingDetails[Balance\Entity::ACCOUNT_TYPE])
        {
            case AccountType::SHARED:
                return $liteBalances->first();

            case AccountType::DIRECT:
                if (isset($accountDetailsMap[$routingDetails[Entity::BALANCE_ID]]) === true)
                {
                    $selectedAccountDetails = $accountDetailsMap[$routingDetails[Entity::BALANCE_ID]];

                    return $selectedAccountDetails[PayoutConstants::BALANCE_ENTITY];
                }

                break;
        }

        throw new LogicException('Invalid response from FTS Routing Engine.', null, [
            'fts_response' => $routingDetails ?? null,
            'merchant_id'  => $merchant->getId(),
            'payout_input' => $input,
        ]);
    }

    public function fetchDestinationTypeFromPayoutInput($input, $merchant)
    {
        if (isset($input[Entity::FUND_ACCOUNT]) === true)
        {
            return $input[Entity::FUND_ACCOUNT][FundAccount\Entity::ACCOUNT_TYPE] ?? "";
        }
        else
        {
            $fundAccountId = $input[Entity::FUND_ACCOUNT_ID] ?? "";

            $fundAccount = null;

            try
            {
                $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $merchant);
            }
            catch (\Throwable $ex)
            {
                // Ignoring the error here
                $this->trace->traceException($ex, Trace::ERROR);
            }

            return optional($fundAccount)->getAccountType() ?? "";
        }
    }

    public function checkIfWorkflowIsEnabledForMerchantForSmartRouting($input, $merchant)
    {
        $skipWorkflow = null;

        if (array_key_exists(Entity::SKIP_WORKFLOW, $input) === true)
        {
            (new Validator)->setStrictFalse()
                           ->validateInput('skip_workflow', $input);

            $skipWorkflow = isset($input[Entity::SKIP_WORKFLOW]) ? boolval($input[Entity::SKIP_WORKFLOW]) : null;
        }

        if ($this->isTestMode() === true)
        {
            return false;
        }

        $areWorkflowsEnabled = $merchant->isFeatureEnabled(Feature\Constants::PAYOUT_WORKFLOWS);

        //
        // Skip workflow if:
        // workflow is not enabled for the merchant
        //
        if ($areWorkflowsEnabled === false)
        {
            return false;
        }

        if ($skipWorkflow !== null)
        {
            $hasSkipWorkflowPayoutSpecificFeature = $merchant->isFeatureEnabled(Feature\Constants::SKIP_WF_AT_PAYOUTS);

            if (($hasSkipWorkflowPayoutSpecificFeature === false) or ($skipWorkflow === true))
            {
                return false;
            }
        }

        $hasSkipWorkflowFeature = $merchant->isFeatureEnabled(Feature\Constants::SKIP_WORKFLOWS_FOR_API);

        $isApiRequest = $this->app['basicauth']->isStrictPrivateAuth();

        //
        // Skip workflow if:
        // if the workflow is enabled, if the request is from API and merchant wants to
        // skip workflow for requests through API
        //
        if (($isApiRequest === true) and
            ($hasSkipWorkflowFeature === true))
        {
            return false;
        }

        return true;
    }

    public function checkIfSmartRoutingForBankingIsApplicable($input, $merchant, $fundAccountType = "")
    {
        $app = App::getFacadeRoot();

        $basicAuth = $app['basicauth'];

        $payoutMode = $input[Entity::MODE] ?? "";

        $currentRoute = $app['api.route']->getCurrentRouteName();

        $isAuthAllowed = ($basicAuth->isStrictPrivateAuth() === true);

        $isScheduledPayout = (isset($input[Entity::SCHEDULED_AT]) === true);

        $isEnvModeAllowed = (($this->isLiveMode() === true) or
                             (App::environment('testing') === true));

        $isRouteAllowed = Route::isSmartRoutingForBankingEnabledForRoute($currentRoute);

        $isValidMode = Mode::isValidModeForSmartRouting($payoutMode);

        $isFundAccountTypeAllowed = (in_array($fundAccountType,
                                              [FundAccount\Type::BANK_ACCOUNT, FundAccount\Type::VPA]) === true);

        $isSmartRoutingAllowed = (($isEnvModeAllowed === true) and
                                  ($isAuthAllowed === true) and
                                  ($isRouteAllowed === true) and
                                  ($isFundAccountTypeAllowed === true) and
                                  ($isValidMode === true) and
                                  ($isScheduledPayout === false) and
                                  ($merchant->isFeatureEnabled(Feature\Constants::ENABLE_SMART_ROUTING) === true));

        // Adding this check aside, to avoid calls to DCS for checking if workflows is enabled, if smart routing is not allowed
        $isSmartRoutingAllowed = ($isSmartRoutingAllowed === true) ?
            !$this->checkIfWorkflowIsEnabledForMerchantForSmartRouting($input, $merchant) : $isSmartRoutingAllowed;

        $this->trace->info(TraceCode::PAYOUT_SMART_ROUTING_CHECK, [
            'current_route'         => $currentRoute,
            'payout_mode'           => $payoutMode,
            'mode'                  => $this->mode,
            'is_scheduled_payout'   => $isScheduledPayout,
            'fund_account_type'     => $fundAccountType,
            'smart_routing_allowed' => $isSmartRoutingAllowed
        ]);

        return $isSmartRoutingAllowed;
    }

    // Function to check if the entity's transaction id field has been updated
    protected function isTransactionIdUpdated($payout, $journalId)
    {
        if (empty($payout->getTransactionId()) === false)
        {
            $this->trace->info(TraceCode::LEDGER_API_TXN_DUAL_WRITE_DUPLICATE_REQUEST, [
                'id' => $journalId
            ]);

            return true;
        }

        return false;
    }

    public function isPayoutApplicableForP2PDelay(Entity $payout): bool
    {
        if ($payout->merchant->isFeatureEnabled(Feature\Constants::ENABLE_APPROVAL_VIA_OAUTH) === false)
        {
            return false;
        }

        $p2pSchedulePostApprovalList = (new Admin\Service)->getConfigKey([
            'key' => Admin\ConfigKey::P2P_SCHEDULE_POST_APPROVAL_MERCHANT_LIST
        ]);

        if (in_array($payout->getMerchantId(), $p2pSchedulePostApprovalList) == false)
        {
            return false;
        }

        return true;
    }

    public function schedulePayoutPostApproval(Entity $payout)
    {
        $scheduledAtTimestamp = Carbon::now(Timezone::IST)->addMinutes(self::SCHEDULE_PAYOUT_POST_APPROVAL_VALUE_IN_MINUTES)->getTimestamp();

        $payout->setScheduledAt($scheduledAtTimestamp);

        $payout->setStatus(Status::SCHEDULED);

        $this->repo->saveOrFail($payout);
    }


    public function payoutsSmartRoutingSummary($input)
    {
        $smartRoutingSummary = $this->initializeSmartRoutingSummary();

        try
        {
            $merchant = $this->merchant;

            $mode = $input[Entity::MODE];

            $fundAccountType = ($mode === Mode::IMPS) ? Type::BANK_ACCOUNT : Type::VPA;

            $isSharedAccountType = true;

            $priorityBalanceList = [];

            $directBalances = [];

            $liteBalances = [];

            $this->trace->info(TraceCode::SMART_ROUTING_SUMMARY_REQUEST, [
                Entity::MERCHANT_ID => $merchant->getId(),
                Entity::MODE        => $mode,
                Entity::INPUT       => $input
            ]);

            //Get The List of Balance Ids for direct and lite accounts
            $listBalanceIds = $this->getBalanceIdsForPayoutsSummary($input, $merchant, $fundAccountType,
                $directBalances, $liteBalances);

            $validLiteAccounts = count($liteBalances);

            $validDirectAccounts = count($directBalances);

            // create and send Request to FTS priority route to get priority channel for valid direct accounts
            if($validDirectAccounts > 0)
            {
               $priorityBalanceList = $this->getPriorityBalanceListFromPriorityRoute($isSharedAccountType,
                   $directBalances, $merchant, $input);
            }

            // If no valid direct accounts or Account type shared by FTS is shared, then we will use the lite accounts
            if($validDirectAccounts == 0 || $isSharedAccountType)
            {
                if ($validLiteAccounts === 0) {
                    throw new LogicException('Merchant doesn\'t have any viable channels for routing.', null, [
                        Entity::MERCHANT_ID => $merchant->getId()
                    ]);
                }
                $priorityBalanceList[] = $liteBalances->first()->getId();
            }


            $this->getSmartRoutingSummaryFromHarvester($smartRoutingSummary, $priorityBalanceList,
                $isSharedAccountType, $listBalanceIds, $liteBalances, $directBalances, $merchant, $input);

            $this->calculateSuccessRates($smartRoutingSummary);

            $this->trace->info(TraceCode::SMART_ROUTING_SUMMARY_RESPONSE, [
                Entity::MERCHANT_ID => $merchant->getId(),
                Entity::MODE => $mode,
                Entity::SMART_ROUTING_SUMMARY => $smartRoutingSummary
            ]);

        }catch (\Throwable $ex) {

            $this->trace->traceException(
                $ex, Logger::ERROR, TraceCode::SMART_ROUTING_SUMMARY_FAILED,
                [
                    Entity::MODE => $mode,
                    Entity::MERCHANT_ID => $merchant->getId(),
                    Entity::SMART_ROUTING_SUMMARY => $smartRoutingSummary
                ]);

            $smartRoutingSummary = null;
        }

        return $smartRoutingSummary;
    }

    /**
     * @throws \Throwable
     * @throws LogicException
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     */
    protected function getSmartRoutingSummaryFromHarvester(&$smartRoutingSummary, $priorityBalanceList, $isSharedAccountType, $listBalanceIds, $liteBalances, $directBalances, $merchant, $input): void
    {
        $mode = $input[Entity::MODE];

        //Harvester Service Instance
        $harvesterService = $this->app['eventManager'];
        $harvesterPayload = [];

        /* If the account type is shared, then we will to make a single call to harvester, for the entire time range
         */
        try {
            if ($isSharedAccountType)
            {
                $priorityBalance = $priorityBalanceList[0];
                $timeRangesList = [
                    [   Entity::START_TIME => $input[Entity::START_TIME],
                        Entity::END_TIME   => $input[Entity::END_TIME],
                    ]
                ];
                $harvesterPayload = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, $mode, $timeRangesList);

                $harvesterResponse = $harvesterService->getDataFromPinot($harvesterPayload);

                $this->trace->info(TraceCode::HARVESTER_QUERY_RESPONSE, [
                    Entity::MERCHANT_ID => $merchant->getId(),
                    Entity::MODE => $mode,
                    Entity::HARVESTER_RESPONSE => $harvesterResponse
                ]);

                (new Validator())->validateSmartRoutingPayoutsSummaryHarvesterResponse($harvesterResponse);

                $this->processHarvesterResponse($smartRoutingSummary, $harvesterResponse, $priorityBalance);

            }
            else
            {
                foreach ($priorityBalanceList as $priorityBalance => $timeRangesList) {
                    $harvesterPayload = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, $mode, $timeRangesList);

                    $harvesterResponse = $harvesterService->getDataFromPinot($harvesterPayload);

                    $this->trace->info(TraceCode::HARVESTER_QUERY_RESPONSE, [
                        Entity::MERCHANT_ID => $merchant->getId(),
                        Entity::MODE => $mode,
                        Entity::HARVESTER_RESPONSE => $harvesterResponse
                    ]);

                    (new Validator())->validateSmartRoutingPayoutsSummaryHarvesterResponse($harvesterResponse);

                    $this->processHarvesterResponse($smartRoutingSummary, $harvesterResponse, $priorityBalance);

                }
            }
        }catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex, Logger::ERROR, TraceCode::HARVESTER_QUERY_FAILED,
                [
                    Entity::HARVESTER_PAYLOAD => $harvesterPayload
                ]);

            throw new LogicException('Failed to get smart routing summary from harvester', null, [
                Entity::MERCHANT_ID => $merchant->getId(),
                Entity::MODE => $mode,
                Entity::HARVESTER_PAYLOAD => $harvesterPayload
            ]);
        }
    }

    /**
     * @throws RuntimeException
     * @throws BadRequestValidationFailureException
     * @throws \Throwable
     */
    protected function getPriorityBalanceListFromPriorityRoute(&$isSharedAccountType, $directBalances, $merchant, $input): array
    {
        $mode = $input[Entity::MODE];
        $priorityBalanceList = [];
        $ftsRequest = $this->initializePriorityRouteRequest($merchant, $mode, $directBalances, $input);

        /** @var \RZP\Services\FTS\FundTransfer $ftsService */
        $ftsService = App::getFacadeRoot()['fts_fund_transfer'];
        $ftsService->setRequestTimeout(1);

        try {

            //Call to FTS to get priority channel
            $ftsResponse = $ftsService->getPriorityChannelThroughFts($ftsRequest);

            $this->trace->info(TraceCode::SMART_ROUTING_SUMMARY_FTS_RESPONSE, [
                Entity::MERCHANT_ID => $merchant->getId(),
                Entity::MODE => $mode,
                Entity::FTS_RESPONSE => $ftsResponse
            ]);

            (new Validator())->validateSmartRoutingSummaryFtsResponse($ftsResponse, array_keys($directBalances));

            if ($ftsResponse[Entity::ACCOUNT_TYPE] == AccountType::DIRECT)
            {
                $isSharedAccountType = false;
                $priorityBalanceList = $ftsResponse[Entity::BALANCE_ID];
            }

        } catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex, Logger::ERROR, TraceCode::SMART_ROUTING_SUMMARY_FTS_FAILED,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                    Entity::MODE => $mode,
                    Entity::SMART_ROUTING_FTS_REQUEST => $ftsRequest
                ]);

            throw new RuntimeException('Failed to get priority balance list from FTS', [
                Entity::MERCHANT_ID => $merchant->getId(),
                Entity::MODE => $mode,
                Entity::SMART_ROUTING_FTS_REQUEST => $ftsRequest
            ], null, $ex);
        }

        return $priorityBalanceList;
    }

    /**
     * @throws \Throwable
     */
    protected function getBalanceIdsForPayoutsSummary($input, $merchant, $fundAccountType, &$directBalances, &$liteBalances): array
    {
        // Fetch Direct balances
        $directBalances = $this->getDirectBalancesForPayoutsSummary($input, $merchant, $fundAccountType);
        $validDirectAccounts = count($directBalances);

        // Fetch Lite balances
        $liteBalances = $this->repo->balance->getMerchantBalancesByTypeAndAccountType(
            $merchant->getId(), Balance\Type::BANKING, AccountType::SHARED, $this->mode);
        $validLiteAccounts = count($liteBalances);

        if($validLiteAccounts === 0 && $validDirectAccounts === 0)
        {
            throw new LogicException('Merchant doesn\'t have any viable channels for routing.', null, [
                Entity::MERCHANT_ID  => $merchant->getId()
            ]);
        }

        //Taking the 1st Lite Balance and all Direct Balances for the summary
        $listBalanceIds = array_keys($directBalances);
        if ($validLiteAccounts > 0)
        {
            $listBalanceIds[] = $liteBalances->first()->getId();
        }
        sort($listBalanceIds);

        $this->trace->info(TraceCode::SMART_ROUTING_SUMMARY_BALANCE_LIST, [
            Entity::MERCHANT_ID => $merchant->getId(),
            Entity::BALANCE_IDS => $listBalanceIds
        ]);

        return $listBalanceIds;
    }

    protected function initializeSmartRoutingSummary(): array
    {
        return [
            Entity::SUCCESS_RATE_WITH_MAR                       => 0.0,
            Entity::SUCCESS_RATE_WITHOUT_MAR                    => 0.0,
            Entity::TOTAL_PAYOUTS                               => 0,
            Entity::TOTAL_SUCCESSFUL_PRIMARY_PAYOUTS            => 0,
            Entity::TOTAL_SUCCESSFUL_SECONDARY_PAYOUTS          => 0,
            Entity::TOTAL_PRIMARY_PAYOUTS                       => 0,
            Entity::TOTAL_SECONDARY_PAYOUTS                     => 0,
            Entity::TOTAL_PAYOUTS_AMOUNT                        => 0,
            Entity::TOTAL_PROCESSED_AMOUNT                      => 0,
            Entity::TOTAL_PRIMARY_PAYOUTS_AMOUNT                => 0,
            Entity::TOTAL_SECONDARY_PAYOUTS_AMOUNT              => 0,
            Entity::TOTAL_PROCESSED_PRIMARY_PAYOUTS_AMOUNT      => 0,
            Entity::TOTAL_PROCESSED_SECONDARY_PAYOUTS_AMOUNT    => 0,
        ];
    }

    protected function initializePriorityRouteRequest($merchant, $mode, $directBalances, $input): array
    {
        $ftsRequest = [
            Entity::MERCHANT_ID => $merchant->getId(),
            Entity::BALANCE_ID => $directBalances,
            Entity::MODE => $mode,
            Entity::START_TIME => $input[Entity::START_TIME],
            Entity::END_TIME => $input[Entity::END_TIME]

        ];

        $this->trace->info(TraceCode::SMART_ROUTING_SUMMARY_FTS_REQUEST, [
            Entity::MERCHANT_ID => $merchant->getId(),
            Entity::MODE => $mode,
            Entity::SMART_ROUTING_FTS_REQUEST => $ftsRequest
        ]);

        return $ftsRequest;
    }

    protected function calculateSuccessRates(&$smartRoutingSummary): void
    {
        $totalPayouts = $smartRoutingSummary[Entity::TOTAL_PAYOUTS];
        $totalPayoutsFromSecondaryChannel = $smartRoutingSummary[Entity::TOTAL_SECONDARY_PAYOUTS];
        $totalSuccessfulPayoutsFromPrimaryChannel = $smartRoutingSummary[Entity::TOTAL_SUCCESSFUL_PRIMARY_PAYOUTS];
        $totalSuccessfulPayoutsFromSecondaryChannel = $smartRoutingSummary[Entity::TOTAL_SUCCESSFUL_SECONDARY_PAYOUTS];

        if ($totalPayouts != 0) {
            $successRateWithMAR = round((($totalSuccessfulPayoutsFromPrimaryChannel + $totalSuccessfulPayoutsFromSecondaryChannel) / $totalPayouts) * 100.0, 2);
            $successRateWithoutMAR = round((($totalPayoutsFromSecondaryChannel / 2.0) + $totalSuccessfulPayoutsFromPrimaryChannel) / $totalPayouts * 100.0, 2);
        } else {
            $successRateWithMAR = 0.0;
            $successRateWithoutMAR = 0.0;
        }

        $smartRoutingSummary[Entity::SUCCESS_RATE_WITH_MAR] += $successRateWithMAR;
        $smartRoutingSummary[Entity::SUCCESS_RATE_WITHOUT_MAR] += $successRateWithoutMAR;
    }


    /**
     * @throws \Throwable
     */
    protected function getDirectBalancesForPayoutsSummary($input, $merchant, $fundAccountType): array
    {
        $accountDetailsMap = [];
        $validDirectAccounts = 0;
        $directBalances = [];

        try {
            $this->fetchValidDirectAccountsForSmartRouting(
                $accountDetailsMap, $validDirectAccounts, $input, $merchant, $fundAccountType);
        } catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::SMART_ROUTING_SUMMARY_DIRECT_ACCOUNT_FETCH_FAILED,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                    Entity::MODE => $input[Entity::MODE],
                ]);

            throw $e;
        }

        foreach ($accountDetailsMap as $balanceId => $accountDetails)
        {
            $directBalances[$balanceId] = $accountDetails[Entity::CHANNEL];
        }

        return $directBalances;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function checkNumeric($arg): float
    {
        if (is_numeric($arg))
        {
            return (double) $arg;
        }
        else
        {
            throw new Exception\InvalidArgumentException('
                Unsigned integer required. Supplied: ' . $arg);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function processHarvesterResponse(&$smartRoutingSummary, $harvesterResponse, $primaryBalanceId): void
    {

        $totalPayouts = 0;
        $totalPayoutsAmount = 0;
        $totalPayoutsFromPrimaryChannel = 0;
        $totalPayoutsFromSecondaryChannel = 0;
        $totalPayoutsAmountFromPrimaryChannel = 0;
        $totalPayoutsAmountFromSecondaryChannel = 0;
        $totalPayoutsProcessedAmount = 0;
        $totalPayoutsProcessedAmountFromPrimaryChannel = 0;
        $totalPayoutsProcessedAmountFromSecondaryChannel = 0;
        $totalSuccessfulPayoutsFromPrimaryChannel = 0;
        $totalSuccessfulPayoutsFromSecondaryChannel = 0;

        foreach ($harvesterResponse as $response) {
            try {
                $count = $this->checkNumeric($response['count']);
                $amount = $this->checkNumeric($response['amount']);
                $balanceId = $response['balance_id'];
                $status = $response['status'];

                $totalPayouts += $count;
                $totalPayoutsAmount += $amount;

                if ($balanceId === $primaryBalanceId) {
                    $totalPayoutsFromPrimaryChannel += $count;
                    $totalPayoutsAmountFromPrimaryChannel += $amount;
                } else {
                    $totalPayoutsFromSecondaryChannel += $count;
                    $totalPayoutsAmountFromSecondaryChannel += $amount;
                }

                if (strtoupper($status) === 'PROCESSED') {
                    $totalPayoutsProcessedAmount += $amount;

                    if ($balanceId === $primaryBalanceId) {
                        $totalSuccessfulPayoutsFromPrimaryChannel += $count;
                        $totalPayoutsProcessedAmountFromPrimaryChannel += $amount;
                    } else {
                        $totalSuccessfulPayoutsFromSecondaryChannel += $count;
                        $totalPayoutsProcessedAmountFromSecondaryChannel += $amount;
                    }
                }
            }catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage());
            }
        }

        //Adding the values of current time range to the overall summary
        $smartRoutingSummary[Entity::TOTAL_PAYOUTS] += (int) $totalPayouts;
        $smartRoutingSummary[Entity::TOTAL_SUCCESSFUL_PRIMARY_PAYOUTS] += (int) $totalSuccessfulPayoutsFromPrimaryChannel;
        $smartRoutingSummary[Entity::TOTAL_SUCCESSFUL_SECONDARY_PAYOUTS] += (int) $totalSuccessfulPayoutsFromSecondaryChannel;
        $smartRoutingSummary[Entity::TOTAL_PRIMARY_PAYOUTS] += (int) $totalPayoutsFromPrimaryChannel;
        $smartRoutingSummary[Entity::TOTAL_SECONDARY_PAYOUTS] += (int) $totalPayoutsFromSecondaryChannel;
        $smartRoutingSummary[Entity::TOTAL_PAYOUTS_AMOUNT] += $totalPayoutsAmount;
        $smartRoutingSummary[Entity::TOTAL_PROCESSED_AMOUNT] = $totalPayoutsProcessedAmount;
        $smartRoutingSummary[Entity::TOTAL_PRIMARY_PAYOUTS_AMOUNT] += $totalPayoutsAmountFromPrimaryChannel;
        $smartRoutingSummary[Entity::TOTAL_SECONDARY_PAYOUTS_AMOUNT] += $totalPayoutsAmountFromSecondaryChannel;
        $smartRoutingSummary[Entity::TOTAL_PROCESSED_PRIMARY_PAYOUTS_AMOUNT] += $totalPayoutsProcessedAmountFromPrimaryChannel;
        $smartRoutingSummary[Entity::TOTAL_PROCESSED_SECONDARY_PAYOUTS_AMOUNT] += $totalPayoutsProcessedAmountFromSecondaryChannel;

    }


    public function payoutSummaryHarvesterQueryBuilder($balancePriorityList, $mode, $timeRangesList): array
    {
        $balance = array_values($balancePriorityList);
        $balanceString = "'" . implode("','", $balance) . "'";

        $conditions = [];
        foreach ($timeRangesList as $range) {
            $startTime = $range[Entity::START_TIME];
            $endTime = $range[Entity::END_TIME];

            $conditions[] = "(created_at > $startTime AND created_at <= $endTime)";
        }
        $conditions = implode(' OR ', $conditions);

        $query = "SELECT balance_id, status, COUNT(*) as count, SUM(amount) as amount
              FROM {{table}}
              WHERE balance_id IN ($balanceString)
                AND mode = '$mode'
                AND ($conditions)
                AND status != 'cancelled'
              GROUP BY balance_id, status;";

        $harvesterPayload = [
            Entity::QUERY => $query,
            Entity::TABLE => Entity::PAYOUTS_TABLE,
            Entity::BACKEND => Entity::PINOT_BACKEND
        ];

        $this->trace->info(TraceCode::HARVESTER_QUERY_REQUEST, [
            Entity::BALANCE_IDS => $balancePriorityList,
            Entity::TIME_RANGE_LIST => $timeRangesList,
            Entity::HARVESTER_PAYLOAD => $harvesterPayload
        ]);

        return $harvesterPayload;
    }

    public function initializeFtsRequestForFetchSmartRoutingRules($merchantID, array $modeWiseActiveChannelsWithFundAccountIDs = null) : array
    {
        $ftsRequest = [];

        foreach ($modeWiseActiveChannelsWithFundAccountIDs as $mode => $activeChannelsWithFundAccountIDs) {
            // Remove mode from list if no active channels available for it
            if(empty($activeChannelsWithFundAccountIDs)) {
                unset($modeWiseActiveChannelsWithFundAccountIDs[$mode]);
            }
        }

        $ftsRequest[Entity::MERCHANT_ID] = $merchantID;
        $ftsRequest[self::MODE_WISE_ACTIVE_CHANNELS] = $modeWiseActiveChannelsWithFundAccountIDs;

        /* {
             'merchant_id' => 'XYZ,
             'mode_wise_active_channels' => ['IMPS' => ['RBL' => 123456, 'ICICI' => 78907], 'NEFT' => ['ICICI' => 78907, 'RBL' => 123456], 'UPI' => ['RBL' => 123456]]
           } */
        $this->trace->info(TraceCode::FETCH_SMART_ROUTING_RULES_FTS_REQUEST, [
            Entity::MERCHANT_ID => $merchantID,
            TraceCode::FTS_REQUEST => $ftsRequest,
        ]);

        return $ftsRequest;
    }

    public function getActiveChannelsWithFundAccountsForSmartRoutingRules($merchantID) : array {
        $channelShared = strtoupper(Balance\AccountType::SHARED);

        // Fetching lite balances
        $liteBalances = $this->repo->balance->getMerchantBalancesByTypeAndAccountType(
            $merchantID, Balance\Type::BANKING, AccountType::SHARED, $this->mode);
        $validLiteAccounts = count($liteBalances);

        // Fetch Active BasDetails
        $activeBasDetails = $this->repo->banking_account_statement_details->getActiveDirectAccountsForMerchantId($merchantID);

        // For storing Active Direct Channels
        $activeChannelsWithFundAccountsMap = [
            Mode::IMPS => [],
            Mode::NEFT => [],
            Mode::UPI => []
        ];

        // Fetching Active Direct Channels
        foreach ($activeBasDetails as $activeBasDetail)
        {
            $channel = $activeBasDetail->getChannel();

            // To maintain channel case uniformity
            $channelInUpperCase = strtoupper($channel);

            $bankingAccount = $activeBasDetail->balance->bankingAccount;

            // Fetch fund account ID for the MID, corresponding to every channel the MID is active on
            $ftsFundAccountId = optional($bankingAccount)->getFtsFundAccountId();

            if ((empty($ftsFundAccountId) === true) and
                (in_array($channel, BankingAccount\Core::$directChannelsForConnectBanking) === true))
            {
                $accountNumber = $activeBasDetail->getAccountNumber();

                try
                {
                    $ftsFundAccountId = app('banking_account_service')->fetchFtsFundAccountIdFromBas(
                        $merchantID, $channel, $accountNumber);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        null,
                        TraceCode::SMART_ROUTING_RULES_BAS_FETCH_FAILED,
                        [
                            'merchant_id'    => $merchantID,
                            'bas_details_id' => $activeBasDetail->getId(),
                            'balance_id'     => $activeBasDetail->getBalanceId(),
                        ]);

                    $this->trace->count(Metric::SMART_ROUTING_BAS_FETCH_FAILURES_COUNT);

                    throw $ex;
                }
            }

            foreach ($activeChannelsWithFundAccountsMap as $transferMode => $channels)
            {
                // If a lite account exists for the MID, it would be available for all modes
                if ($validLiteAccounts > 0) {
                    $activeChannelsWithFundAccountsMap[$transferMode][$channelShared] = "";
                }

                if ($transferMode == Mode::UPI)
                {
                    // Check if the channel is active for UPI mode or not
                    $isChannelActiveForDirectUPIPayouts = (new Validator())->validateChannelForDirectUPIPayouts($merchantID, $channel);
                    if ($isChannelActiveForDirectUPIPayouts === true)
                    {
                        $activeChannelsWithFundAccountsMap[$transferMode][$channelInUpperCase] = $ftsFundAccountId;
                    }
                }
                else
                {
                    // If a direct account exists for the MID on a certain channel, then channel is active for all non-UPI modes
                    $activeChannelsWithFundAccountsMap[$transferMode][$channelInUpperCase] = $ftsFundAccountId;
                }
            }
        }

        return $activeChannelsWithFundAccountsMap;
    }

    /**
     * @throws \Throwable
     * @throws LogicException
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     */
    public function fetchSmartRoutingRulesForMerchant($input): array
    {
        $merchantID = $input[Entity::MERCHANT_ID];

        $this->trace->info(TraceCode::FETCH_SMART_ROUTING_RULES_API_REQUEST, [
            Entity::MERCHANT_ID => $merchantID,
            Entity::INPUT       => $input
        ]);

        // Fetch Direct Routing Channels with fund account ID's
        /* ['IMPS' => ['RBL' => 123456, 'ICICI' => 78907, 'SHARED' => ''],
            'NEFT' => ['RBL' => 123456, 'ICICI' => 78907, 'SHARED' => ''],
            'UPI' => ['RBL' => 123456, 'SHARED' => '']] */
        $activeChannelsWithFundAccounts = $this->getActiveChannelsWithFundAccountsForSmartRoutingRules($merchantID);

        /* {
            "merchant_id": "10000000000000",
            "mode_wise_active_channels": {
                "IMPS": {
                    "SHARED": "",
                    "RBL": "10001",
                    "ICICI": "12345678"
                },
                "NEFT": {
                    "SHARED": "",
                    "RBL": "10001",
                    "ICICI": "12345678"
                },
                "UPI": {
                    "SHARED": "",
                    "RBL": "10001",
                    "ICICI": "12345678"
                }
            }
        } */

        $ftsRequest = $this->initializeFtsRequestForFetchSmartRoutingRules($merchantID, $activeChannelsWithFundAccounts);

        /** @var \RZP\Services\FTS\FundTransfer $ftsService */

        $ftsService = App::getFacadeRoot()['fts_fund_transfer'];
        $ftsService->setRequestTimeout(1);

        $ftsResponse = [];

        try {
            // Call to FTS to get merchant's smart routing rules
            // Sample response: ["IMPS" => ["RBL", "ICICI", "SHARED"], "NEFT" => ["YESBANK", "RBL", "SHARED"], "UPI" => ["RBL"]]
            $ftsResponse = $ftsService->fetchSmartRoutingRulesThroughFts($ftsRequest);

            $ftsResponseBody = $ftsResponse['body'];

            $this->trace->info(TraceCode::FETCH_SMART_ROUTING_RULES_FTS_RESPONSE, [
                Entity::MERCHANT_ID => $merchantID,
                TraceCode::FTS_RESPONSE_BODY => $ftsResponseBody
            ]);

            $modeWiseChannelPriorities = $ftsResponseBody[self::MERCHANT_ROUTING_PRIORITIES];

            (new Validator())->validateSmartRoutingRulesFTSResponse($modeWiseChannelPriorities);
        } catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex, Logger::ERROR, TraceCode::FETCH_SMART_ROUTING_RULES_FTS_REQUEST_FAILED,
                [
                    Entity::MERCHANT_ID => $merchantID,
                    TraceCode::FTS_REQUEST => $ftsRequest
                ]);

            throw new RuntimeException(ErrorCode::SMART_ROUTING_RULES_FETCH_FAILED_FTS, [
                Entity::MERCHANT_ID => $merchantID,
                TraceCode::FTS_REQUEST => $ftsRequest
            ], null, $ex);
        }

        return $modeWiseChannelPriorities;
    }

    /**
     * @throws \Throwable
     * @throws LogicException
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     */
    public function modifySmartRoutingRulesForMerchant($input): array
    {
        $merchantID = $input[Entity::MERCHANT_ID];
        $merchantCustomizedPriorities = $input[self::MERCHANT_CUSTOMIZED_PRIORITY_RULES];

        $this->trace->info(TraceCode::MODIFY_SMART_ROUTING_RULES_API_REQUEST, [
            Entity::MERCHANT_ID => $merchantID,
            Entity::INPUT       => $input
        ]);

        // Fetch Direct Routing Channels with fund account ID's
        /* ['IMPS' => ['RBL' => 123456, 'ICICI' => 78907],
            'NEFT' => ['RBL' => 123456, 'ICICI' => 78907],
            'UPI' => ['RBL' => 123456]] */
        $activeChannelsWithFundAccounts = $this->getActiveChannelsWithFundAccountsForSmartRoutingRules($merchantID);

    /* {
        "merchant_id": "10000000000000",
        "mode_wise_active_channels": {
            "IMPS": {
                "SHARED": "",
                "RBL": "10001",
                "ICICI": "12345678"
            },
            "NEFT": {
                "SHARED": "",
                "RBL": "10001",
                "ICICI": "12345678"
            },
            "UPI": {
                "SHARED": ""
            }
        },
        "merchant_customized_priority_rules": {
            "IMPS": [
                "RBL",
                "ICICI",
                "SHARED"
            ],
            "NEFT": [
                "ICICI",
                "RBL",
                "SHARED"
            ]
        }
    } */

        $ftsRequest = $this->initializeFtsRequestForFetchSmartRoutingRules($merchantID, $activeChannelsWithFundAccounts);
        $ftsRequest[self::MERCHANT_CUSTOMIZED_PRIORITY_RULES] = $merchantCustomizedPriorities;

        /** @var \RZP\Services\FTS\FundTransfer $ftsService */

        $ftsService = App::getFacadeRoot()['fts_fund_transfer'];
        $ftsService->setRequestTimeout(1);

        $ftsResponse = [];

        try {
            // Call to FTS to modify merchant's smart routing rules
            // Sample response: ["IMPS" => ["RBL", "ICICI", "SHARED"], "NEFT" => ["ICICI", "RBL", "SHARED"], "UPI" => ["RBL"]]
            $ftsResponse = $ftsService->modifySmartRoutingRulesThroughFts($ftsRequest);

            $ftsResponseBody = $ftsResponse['body'];

            $this->trace->info(TraceCode::FETCH_SMART_ROUTING_RULES_FTS_RESPONSE, [
                Entity::MERCHANT_ID => $merchantID,
                TraceCode::FTS_RESPONSE_BODY => $ftsResponseBody
            ]);

            $modeWiseChannelPriorities = $ftsResponseBody[self::MERCHANT_ROUTING_PRIORITIES];

            (new Validator())->validateSmartRoutingRulesFTSResponse($modeWiseChannelPriorities);
        } catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex, Logger::ERROR, TraceCode::MODIFY_SMART_ROUTING_RULES_FTS_REQUEST_FAILED,
                [
                    Entity::MERCHANT_ID => $merchantID,
                    TraceCode::FTS_REQUEST => $ftsRequest
                ]);

            throw new RuntimeException(ErrorCode::SMART_ROUTING_RULES_MODIFY_FAILED_FTS, [
                Entity::MERCHANT_ID => $merchantID,
                TraceCode::FTS_REQUEST => $ftsRequest
            ], null, $ex);
        }

        $this->trace->info(TraceCode::MODIFY_SMART_ROUTING_RULES_FTS_RESPONSE, [
            Entity::MERCHANT_ID => $merchantID,
            TraceCode::FTS_RESPONSE => $ftsResponse
        ]);

        return $modeWiseChannelPriorities;
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     * @throws BadRequestException
     */
    public function manualProcessedToProcessing($input) : array
    {
        $payoutId = $input['payout_id'];
        $isPsEntity = $input['is_payout_service'];
        (new Validator)->validatePayoutId($payoutId);

        if($isPsEntity){
            // TODO call PS service call.
            return [];
        }

        $payout = $this->repo->payout->findByPublicId('pout_'.$payoutId);
        $fta = $this->repo->fund_transfer_attempt->getAttemptBySourceId($payout->getId(), Entity::PAYOUT);

        if(empty($fta)){
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                "Fta entity not found for payoutId"
            );
        }

        $ftaId = $fta->getId();

        $payoutsStatusDetails = $this->repo->payouts_status_details->fetchPayoutStatusDetailsLatest($payoutId);

        if(empty($payoutsStatusDetails)){
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                "Payout Status Details entity not found for payoutId for status: processed"
            );
        }

        if ($payout->getStatus() != Status::PROCESSED || $fta->getStatus() != Status::PROCESSED || $payoutsStatusDetails->getStatus() != Status::PROCESSED) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                "Payout, fta, payoutStatusDetails entities are not in processed state"
            );
        }

        // Initialize the state machine for the payout using ManualStateTransitionMachine
        $payoutStateMachine = new ManualStateTransitionMachine($payout->getStatus());

        $ftaStateMachine = new ManualStateTransitionMachine($fta->getStatus());

        $payoutStatusDetailsMachine = new ManualStateTransitionMachine($payoutsStatusDetails->getStatus());

        \DB::beginTransaction();

        try {
            // Execute state transition
            $payoutStateMachine->transition($payout,Status::INITIATED, function () use ($fta, $ftaStateMachine, $payoutsStatusDetails, $payoutStateMachine) {
                // Transition the FTA by callback only if payout transition was successful
                $ftaStateMachine->transition($fta, Status::INITIATED, function () use ($payoutsStatusDetails, $payoutStateMachine) {
                    // Transition the payout status details only if payout transition was successful
                    $payoutStateMachine->transition($payoutsStatusDetails, 'deleted');
                });
            });

            // Commit the transaction
            \DB::commit();

        } catch (\Exception $e) {

            \DB::rollback();

            throw $e;
        }

        $response = [
            'payoutId' => $payoutId,
            'ftaId' => $ftaId,
            'payoutStatusDetailsId' => $payoutsStatusDetails->getId()
        ];

        $this->trace->info(
            TraceCode::MANUAL_ACTION_PROCESSED_TO_PROCESSING_SUCCESS, [
                "response" => $response,
                "description" => "Payout, fta, payoutStatusDetails entities updated "
            ]
        );
        return $response;
    }

    public function trackPayoutPropertiesEvent($payout)
    {
        $payoutId = $payout->getId() ?? $payout['id'];
        $auth = $this->app['basicauth'];
        $merchant = $this->merchant;

        if (empty($merchant) || empty($auth)) {
            $this->trace->warning(TraceCode::PAYOUT_PROPERTIES_EVENT_FAILED,[
                'payout_id' => $payoutId
            ]);
            return;
        }

        $merchantId = $merchant->getId();
        $experimentName = 'payout_properties_event_experiment_id';

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.'.$experimentName),
            'request_data' => json_encode(['merchant_id' => $merchantId])
        ];

        if($this->isSplitzExperimentEnable($properties,'enable', TraceCode::PAYOUT_PROPERTIES_EVENT_SPLITZ_ERROR) === false){
            return;
        }

        $origin = '';
        $internalApp = $auth->getInternalApp() ?? 'external';

        $user = $auth->getUser();
        $userId = $user ? $user->getId() : '';

        $role = $auth->getUserRole() ?? '';

        // In case of worker/job the route name is null
        if (isset($this->app['api.route']) && $this->app['api.route']->getCurrentRouteName())
        {
            $origin = $this->app['api.route']->getCurrentRouteName();

        } elseif (isset($this->app['request.ctx']) && $this->app['request.ctx']->getRoute())
        {
            $origin = $this->app['request.ctx']->getRoute();

        }elseif (isset($this->app['worker.ctx']) && $this->app['worker.ctx']->getJobName())
        {
            $origin = $this->app['worker.ctx']->getJobName();
        }

        $eventAttributes = [
            'merchant_id' => $merchantId,
            'origin' => $origin,
            'user_id'     => $userId,
            'user_role'   => $role,
            'app_name'    => $internalApp
        ];

        $this->app['diag']->trackPayoutPropertiesEvent(
            EventCode::PAYOUT_PROPERTIES,
            $payout,
            $eventAttributes
        );

        $this->trace->info(TraceCode::PAYOUT_PROPERTIES_EVENT_SUCCESS,[
            "payout_id" => $payoutId,
            "event_attributes" => $eventAttributes
        ]);
    }
}
