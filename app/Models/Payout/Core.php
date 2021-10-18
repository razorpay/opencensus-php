<?php

namespace RZP\Models\Payout;

use App;
use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Admin;
use RZP\Models\State;
use DeepCopy\DeepCopy;
use RZP\Models\Counter;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Services\Mutex;
use RZP\Models\Customer;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\External;
use RZP\Models\Workflow;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Jobs\FundTransfer;
use RZP\Models\Settlement;
use RZP\Models\FundAccount;
use RZP\Jobs\QueuedPayouts;
use RZP\Models\Transaction;
use RZP\Models\FeeRecovery;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\PayoutService;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Credits;
use RZP\Models\Admin\Permission;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Jobs\BatchPayoutsProcess;
use RZP\Models\Currency\Currency;
use RZP\Jobs\OnHoldPayoutsProcess;
use RZP\Jobs\QueuedPayoutsInitiate;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Payout\Notifications;
use RZP\Mail\Payout\PendingApprovals;
use RZP\Jobs\ScheduledPayoutsProcess;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccountStatement;
use RZP\Models\Workflow\PayoutAmountRules;
use RZP\Models\Workflow\Service\EntityMap;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Services\Pagination\Entity as PaginationEntity;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;
use RZP\Models\Workflow\Service\Config\Service as WorkflowConfigService;

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
    const MUTEX_RESOURCE                    = 'PAYOUT_PROCESSING_%s_%s';

    const CUSTOMER_WALLET_MUTEX_RESOURCE    = 'CUSTOMER_WALLET_PAYOUT_%s_%s_%s';

    const MAX_PAYOUT_AMOUNT                 = 800000000; // 80 Lakhs

    const MUTEX_LOCK_TIMEOUT                = 300;

    const PAYOUT_MUTEX_LOCK_TIMEOUT         = 180;

    const PAYOUT_FAILURE_MUTEX_LOCK_TIMEOUT = 600;

    const PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT = 3600;

    const FAILURE_STATUSES_FOR_PAYOUT_TO_AMEX = [Attempt\Status::FAILED, Attempt\Status::REVERSED];

    const DEFAULT_SLA_FOR_ON_HOLD_PAYOUTS_IN_MINS = 30;

    const DEFAULT_BENE_BANK_STATUS = 'resolved';

    const BENE_BANK_DOWNTIME_STARTED = 'started';

    const BENEFICIARY = 'BENEFICIARY';

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
     * @var PayoutService\Cancel
     */
    protected $payoutCancelServiceClient;

    /**
     * @var PayoutService\Schedule
     */
    protected $payoutScheduledServiceClient;

    /**
     * @var PayoutService\Retry
     */
    protected $payoutRetryServiceClient;

    /**
     * @var PayoutService\QueuedInitiate
     */
    protected $payoutServiceQueuedInitiateClient;

    /** @var Workflow\Service\Client  */
    protected $workflowService;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->payoutStatusServiceClient = $this->app[PayoutService\Status::PAYOUT_SERVICE_STATUS];

        $this->payoutDetailsServiceClient = $this->app[PayoutService\Details::PAYOUT_SERVICE_DETAIL];

        $this->payoutCancelServiceClient = $this->app[PayoutService\Cancel::PAYOUT_SERVICE_CANCEL];

        $this->payoutScheduledServiceClient = $this->app[PayoutService\Schedule::PAYOUT_SERVICE_SCHEDULE];

        $this->payoutRetryServiceClient = $this->app[PayoutService\Retry::PAYOUT_SERVICE_RETRY];

        $this->payoutServiceQueuedInitiateClient =
            $this->app[PayoutService\QueuedInitiate::PAYOUT_SERVICE_QUEUED_INITIATE];

        $this->workflowService = new Workflow\Service\Client;
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
                                              bool $isInternal = false): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_TO_FUND_ACCOUNT_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($merchant)
                       ->setBatch($batchId)
                       ->setInternal($isInternal)
                       ->createPayout($input);

        $this->dispatchFtaInitiate($payout);

        $this->processLedgerPayout($payout);

        return $payout;
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
     * @return Entity
     */
    public function createPayoutToFundAccountForCompositePayout(array $input,
                                                                Merchant\Entity $merchant,
                                                                FundAccount\Entity $fundAccount,
                                                                Merchant\Balance\Entity $balance): Entity
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_COMPOSITE_PAYOUT_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        // TODO: See if we can get balance from somewhere before and reuse here.
        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($merchant)
                       ->setFundAccount($fundAccount)
                        // NOT SUPPORTED: Workflow, Scheduled Payouts, Partner payouts,
                        // Payouts via Apps, Batch Payouts, Payout Microservice
                       ->createPayoutForCompositePayoutFlow($input, $balance);

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

        switch ($status)
        {
            case Status::PROCESSED:
                $this->handlePayoutProcessed($payout, null, $ftsSourceAccountInformation);
                break;

            case Status::REVERSED:
                if (($payout->isBalanceAccountTypeShared() === true) and
                    ($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT) === true))
                {
                    // this is only on shared account
                    $this->handlePayoutReversedForHighTpsMerchants($payout,
                                                                   $ftaFailureReason,
                                                                   $ftaBankStatusCode,
                                                                   null,
                                                                   $ftsSourceAccountInformation);
                    break;
                }

                $this->handlePayoutReversed($payout, $ftaFailureReason, $ftaBankStatusCode, null, $ftsSourceAccountInformation);
                break;

            case Status::FAILED:
                $this->handlePayoutFailed($payout, $ftaFailureReason, $ftaBankStatusCode, $ftsSourceAccountInformation);
                break;

            case Status::CREATED:
            case Status::INITIATED:
                break;

            default:
                $this->trace->warning(
                    TraceCode::UNKNOWN_FTA_STATUS_SENT_TO_PAYOUT,
                    $ftaData);
        }

        $payout->reload();

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

        $isPayoutService = $payout->getIsPayoutService();

        if ($isPayoutService === true)
        {
            $this->updateWithDetailsBeforeFtaReconForPayoutService($payout, $ftaData);
        }

        $initialUtr = $payout->getUtr();

        $this->repo->transaction(
            function() use ($payout, $ftaData, $initialUtr)
            {
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
                        $returnUtr = $ftaData[Attempt\Constants::RETURN_UTR];

                        $payout->setReturnUtr($returnUtr);
                    }
                }

                $this->repo->saveOrFail($payout);
            });

        if (($initialUtr === null) and
            ($payout->getUtr() !== null) and
            ($isPayoutService === false))
        {
            $this->app->events->fire('api.payout.updated', [$payout]);
        }

        $this->trace->info(
            TraceCode::PAYOUT_UPDATED_AFTER_FTA_RECON,
            [
                'payout_id' => $payout->getId(),
            ]);
    }

    public function fetchAndUpdateGatewayBalanceIfStale(Merchant\Balance\Entity $balanceEntity)
    {
        $input = [
            Merchant\Balance\Entity::CHANNEL        => $balanceEntity->getChannel(),
            Merchant\Balance\Entity::MERCHANT_ID    => $balanceEntity->getMerchantId(),
            Merchant\Balance\Entity::ACCOUNT_NUMBER => $balanceEntity->getAccountNumber()
        ];

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
            $basDetails = (new BankingAccount\Core)->fetchAndUpdateGatewayBalanceWrapper($input);
        }

        return $basDetails;
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
                $balanceAmount = $this->getLatestBalanceForDirectAccount($balanceEntity);
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

    public function getLatestBalanceForDirectAccount(Merchant\Balance\Entity $balanceEntity)
    {
        /** @var BankingAccountStatement\Details\Entity $basDetailsUpdated */
        $basDetailsUpdated = $this->fetchAndUpdateGatewayBalanceIfStale($balanceEntity);

        $balanceAmount = $balanceEntity->getBalanceWithLockedBalance();

        if ($basDetailsUpdated->isGatewayBalanceFetchCronMoreUpdated() === true)
        {
            $balanceAmount = $basDetailsUpdated->getGatewayBalance();
        }

        return $balanceAmount;
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

                $queuedPayouts = $this->repo->payout->fetchQueuedPayoutsForBalanceId($balanceId, $offset);

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

                $balanceAmount = $balanceEntity->getBalanceWithLockedBalance();

                if ($balanceEntity->isAccountTypeDirect() === true)
                {
                    $balanceAmount = $this->getLatestBalanceForDirectAccount($balanceEntity);
                }

                $totalQueuedPayouts = $this->repo->payout->fetchCountOfQueuedPayoutsForBalance($balanceId);

                $dispatchedData = $this->dispatchApplicablePayouts($balanceAmount, $queuedPayouts , $balanceEntity);

                $dispatchedPayoutCount = $dispatchedData['dispatched_payout_count'];

                $this->updateOffsetForBalance($balanceId,
                                              $offset,
                                              $queuedPayoutsPaginationData,
                                              $dispatchedPayoutCount,
                                              $totalQueuedPayouts);

                $summary[$balanceId] = [
                    'original_balance'         => $balanceAmount,
                    'balance_remaining'        => $dispatchedData['balance_remaining'],
                    'total_payout_count'       => count($queuedPayouts),
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
                $payout = $this->getProcessor('fund_account_payout')
                               ->setMerchant($payout->merchant)
                               ->processQueuedPayout($payout);

                //
                // There might be some type of payouts where we don't want to dispatch FTA.
                // Should handle that before adding any other type of payouts as queued.
                //
                $this->dispatchFtaInitiate($payout);

                $this->processLedgerPayout($payout);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
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

                //
                // There might be some type of payouts where we don't want to dispatch FTA.
                // Should handle that before adding any other type of payouts as batch_submitted.
                //
                $this->dispatchFtaInitiate($payout);

                $this->processLedgerPayout($payout);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    public function processPayoutPostCreate(string $payoutId, bool $queueFlag)
    {
        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId, $queueFlag)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                $payout->getValidator()->validatePostCreateProcessPayout();

                $payout = $this->getProcessor('fund_account_payout')
                                ->setMerchant($payout->merchant)
                                ->processPayoutPostCreate($payout, $queueFlag);

                $this->processLedgerPayout($payout);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    public function processPayoutPostCreateLowPriority(string $payoutId, bool $queueFlag)
    {
        return $this->mutex->acquireAndRelease(
            $payoutId,
            function() use ($payoutId, $queueFlag)
            {
                /** @var Entity $payout */
                $payout = $this->repo->payout->findOrFail($payoutId);

                $payout = $this->setSubBalance($payout);

                $payout->getValidator()->validatePostCreateProcessPayout();

                $payout = $this->getProcessor('fund_account_payout')
                               ->setMerchant($payout->merchant)
                               ->processPayoutPostCreate($payout, $queueFlag);

                $this->processLedgerPayout($payout);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
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
            $balanceNumber = rand(0,count($subBalances)-1);

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
                            'payout_id' => $payout->getId(),
                        ]);

                    return $payout;
                }

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

                $this->dispatchFtaInitiate($payout);

                $this->processLedgerPayout($payout);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
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
        if ($payout->getIsPayoutService() === true)
        {
            $this->payoutCancelServiceClient->cancelPayoutViaMicroservice($payout->getId(), $remarks);
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
            function() use ($payout, $remarks)
            {
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
            $this->rejectWorkflowViaWorkflowService($payout, [Entity::FORCE_REJECT => true]);

            return $payout;
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
        if ($this->shouldCallWorkflowService($payout) === true)
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

                        if (($approve === true) and
                            ($workflowAction->getApproved() === true))
                        {
                            $payout = $this->processApprovePayout($payout, $input);
                        }
                        else
                        {
                            if (($approve === false) and
                                ($workflowAction->isRejected() === true))
                            {
                                $payout = $this->processRejectPayout($payout);
                            }
                        }

                        return $payout;
                    });

                return $payout;
            });

        return $payout;
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

        if ($approved === true)
        {
            $this->approveWorkflowViaWorkflowService($payout, $input);
        }
        else
        {
            $this->rejectWorkflowViaWorkflowService($payout, $input);
        }

        return $payout;
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

        // error handling is done in the payout service layer
        $response = $this->workflowService->createWorkflow($payout, $input);

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

        foreach ($payouts as $key => $payout)
        {
            // We don't want to process queued payouts that have payout service enabled as those will be processed by
            // payout service.
            if ($payout->getIsPayoutService() === true)
            {
                unset($payouts[$key]);
                continue;
            }

            $purpose = $payout->getPurpose();

            if ($purpose === Purpose::RZP_FEES)
            {
                $totalPayoutAmount = $payout->getAmount();

                if ($totalBalance < $totalPayoutAmount)
                {
                    $rzpFeesRecoverySucceeded = false;

                    continue;
                }

                $totalBalance -= $totalPayoutAmount;

                $this->dispatchQueuedPayout($payout, 0, $totalBalance);

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

        // We are going to get the count of Free Payouts here but we shall not be incrementing or decrementing the
        // count at this point. Increments/Decrements should ideally reside in the same flow.
        // This will also help avoid issues with counter getting stuck or any other race conditions.
        $freePayoutsConsumed = (new CounterHelper)->getCounterForBalance($balance)->getFreePayoutsConsumed();

        foreach ($payouts as $payout)
        {
            $payoutAmount = $payout->getAmount();

            // We have to explicitly calculate fees here since if it's queued, transaction wouldn't
            // have been created and hence the fees also wouldn't have been calculated.
            list($payoutFees, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

            if ($payout->balance->isAccountTypeDirect() === true)
            {
                $totalPayoutAmount = $payoutAmount;
            }
            else
            {
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
                continue;
            }

            $totalBalance -= $totalPayoutAmount;

            $this->dispatchQueuedPayout($payout, $payoutFees, $totalBalance);

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

    protected function dispatchQueuedPayout(Entity $payout, int $fees, int $currentBalance)
    {
        $payoutId = $payout->getId();

        $traceInfo = [
            'payout_id'         => $payoutId,
            'amount'            => $payout->getAmount(),
            'fees'              => $fees,
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
                // Filter out the balance ids for which the merchants have payout_service_enabled feature
                $balanceIdList = $this->repo->balance
                    ->getBalanceIdsWithAMerchantsHavingPayoutServiceEnabled($balanceIdList);

                if (empty($balanceIdList) === true)
                {
                    return;
                }

                $payoutServiceInput = [Entity::BALANCE_IDS => $balanceIdList];

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
    public function getMerchantSlaForOnHoldPayouts(string $merchantId)
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
                $slaValue = self::DEFAULT_SLA_FOR_ON_HOLD_PAYOUTS_IN_MINS;
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
                    $payout = $this->repo->payout->findOrFail($payoutId);

                    $payout->getValidator()->validateOnHoldPayoutProcessing();

                    $isBeneDown = $this->checkIfBeneBankIsDown($payout);

                    if ($isBeneDown === false)
                    {
                        $payout = $this->getProcessor('fund_account_payout')
                                       ->setMerchant($payout->merchant)
                                       ->processOnHoldPayout($payout);

                        $this->processLedgerPayout($payout);

                        return $payout;
                    }
                    else
                    {
                        $isSlaBreached = $this->checkIfMerchantSlaBreachedForOnHoldPayout($payout);

                        if ($isSlaBreached === true)
                        {
                            $payout->setStatus(Status::FAILED);

                            //Failure reason is marked as BENE_BANK_DOWN since the sla is breached and the bank is still down.
                            $payout->setFailureReason(QueuedReasons::BENE_BANK_DOWN);

                            $payout->setStatusCode("BBANK_OFFLINE");

                            $this->repo->payout->saveOrFail($payout);

                            $this->trace->info(
                                TraceCode::ON_HOLD_PAYOUT_FAILED,
                                [
                                    'payout_id'      => $payout->getId(),
                                    'payout_status'  => $payout->getStatus(),
                                    'failure_reason' => $payout->getFailureReason(),
                                ]);

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

    protected function checkIfMerchantSlaBreachedForOnHoldPayout(Entity $payout)
    {
        $slaValue = $this->getMerchantSlaForOnHoldPayouts($payout->getMerchantId());

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

    public function handlePayoutProcessed(Entity $payout, $debit_bas = null, array $ftsSourceAccountInformation = [])
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

        if ($payout->getIsPayoutService() === true)
        {
            $this->handlePayoutProcessedForPayoutService($payout);
        }
        else
        {
            $this->repo->transaction(
                function() use ($payout, $debit_bas) {
                    $payout->setStatus(Status::PROCESSED);

                    $this->repo->saveOrFail($payout);

                    if ($payout->isBalanceAccountTypeDirect() === true)
                    {
                        $this->handlePayoutTransactionForDirectBanking($payout, $debit_bas);

                        (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout);
                    }
                });

            $this->app->events->dispatch('api.payout.processed', [$payout]);
        }

        $this->processLedgerPayout($payout, null, $ftsSourceAccountInformation);
    }

    /**
     * @param Entity               $payout
     * @param Reversal\Entity|null $reversal
     * @param array|null           $ftsSourceAccountInformation
     * Push to ledger sns when a payout status is changed. This will create this payout in ledger DB.
     * Since ledger keeps different records for all payout states, these events are triggered.
     */
    protected function processLedgerPayout(Entity $payout,
                                           Reversal\Entity $reversal = null,
                                           array $ftsSourceAccountInformation = [],
                                           string $previousStatus = null)
    {
        // Currently only shared fundAccount payout is pushed to ledger. So in case of direct, return.
        // In case env variable ledger.enabled is false, return.
        if (($this->app['config']->get('applications.ledger.enabled') === false) or
            ($payout->getBalanceAccountType() === AccountType::DIRECT))
        {
            return;
        }

        // If the mode is live but the merchant does not have the ledger journal write feature, we return.
        if (($this->isLiveMode()) and
            ($payout->merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES) === false))
        {
            return;
        }

        // If a payout goes from initiated to reversed, we wish to move the status
        // from initiated -> processed -> reversed, hence we are forcing a call to ledger with processed status
        if (($payout->getStatus() === Status::REVERSED) and
            ($previousStatus === Status::INITIATED or $previousStatus === Status::CREATED))
        {
            $clonedPayout = clone $payout;

            $clonedPayout->setStatus(Status::PROCESSED);

            $event = Status::getLedgerEventFromPayoutStatus($clonedPayout->getStatus());

            (new Transaction\Processor\Ledger\Payout)->pushTransactionToLedger($clonedPayout, $event, $reversal, $ftsSourceAccountInformation);
        }

        $event = Status::getLedgerEventFromPayoutStatus($payout->getStatus());

        (new Transaction\Processor\Ledger\Payout)->pushTransactionToLedger($payout, $event, $reversal, $ftsSourceAccountInformation);
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
     * @throws Exception\LogicException
     */
    public function handlePayoutTransactionForDirectBanking(Entity $payout, $debit_bas = null)
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

            return;
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
                    $bas = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout);
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

                return;
            }
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
        $payoutTransaction = $this->handleProcessedPayoutViaReversedPayout($reversal);

        //
        // If we were not able to find payout's transaction, we won't be able to find
        // reversal's transaction also. Hence, no point of doing all the below stuff.
        //
        if (empty($payoutTransaction) === true)
        {
            return;
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

            return;
        }

        $bas = $credit_bas;

        if ($bas === null)
        {
            $bas = $this->repo->banking_account_statement->fetchByUtrForReversal($reversal)->first() ??
                   $this->repo->banking_account_statement->fetchByCmsRefNumForReversal($reversal)->first();

            // This happens when account statement has not been fetched yet, or we were unable to map the BAS to a reversal
            if (empty($bas) === true)
            {
                $this->trace->info(
                    TraceCode::BAS_NOT_FOUND_FOR_CREDIT_MAPPING,
                    [
                        'reversal_id' => $reversal->getId(),
                    ]);

                return;
            }
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
    }

    protected function handleProcessedPayoutViaReversedPayout(Reversal\Entity $reversal)
    {
        /** @var Entity $payout */
        $payout = $reversal->entity;

        if ($payout->hasTransaction() === true)
        {
            return $payout->transaction;
        }

        $this->trace->info(
            TraceCode::PAYOUT_PROCESS_VIA_PAYOUT_REVERSE,
            [
                'reversal_id'   => $reversal->getId(),
                'payout_id'     => $payout->getId()
            ]);

        $this->handlePayoutTransactionForDirectBanking($payout);

        return $payout->transaction;
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
                        'transaction_details'   => $dummyTransaction->toArrayPublic(),
                        'fees_breakup_details'  => $dummyFeesBreakup->toArrayPublic(),
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
                                         array $ftsSourceAccountInformation = [])
    {
        $reversal = null;

        $previousStatus = $payout->getStatus();

        // check using service
        if ($payout->getIsPayoutService() === true)
        {
            $this->handlePayoutReversedForPayoutService($payout, $ftaFailureReason, $ftaBankStatusCode, $reversal);
        }
        else
        {
            // will be removed after new error object is released.
            $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

            $this->reversePayout($payout, $ftaFailureReason, $ftaBankStatusCode, $credit_bas, $reversal);

            $this->app->events->dispatch('api.payout.reversed', [$payout]);
        }

        $this->processLedgerPayout($payout, $reversal, $ftsSourceAccountInformation, $previousStatus);
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

        $this->app->events->dispatch('api.payout.reversed', [$payout]);

        $this->processLedgerPayout($payout, $reversal, $ftsSourceAccountInformation, $previousStatus);
    }

    protected function handlePayoutFailed(Entity $payout,
                                          string $ftaFailureReason = null,
                                          string $ftaBankStatusCode = null,
                                          array $ftsSourceAccountInformation = [])
    {
        // will be removed after new error object is released.
        $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

        $this->verifyPayoutFailedTransaction($payout);

        $currentStatus = $payout->getStatus();

        //
        // Payout can go to failed state from initiated or created state only
        //
        Status::validateStatusUpdate(Status::FAILED, $currentStatus);

        // Keeping the mutex TTL high while updating the payout to failed.
        // This is to ensure that the process that is working on the payout
        // resource, releases mutex on the payout only once all entities are
        // saved in the database.
        $this->mutex->acquireAndRelease(
            'failure_payout_id_' . $payout->getId(),
            function () use ($payout, $ftaFailureReason, $ftaBankStatusCode)
            {
                // reloading the payout here to ensure if any other process
                // gets a mutex on payout resource, it gets a fresh copy
                // of payout to work.
                $this->repo->reload($payout);

                $this->repo->transaction(
                    function() use ($payout, $ftaFailureReason, $ftaBankStatusCode) {

                        $previousStatus = $payout->getStatus();

                        $payout->setStatus(Status::FAILED);

                        $payout->setFailureReason($ftaFailureReason);

                        $payout->setStatusCode($ftaBankStatusCode);

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

        if ($payout->getIsPayoutService() === true)
        {
            // error will be handled by service
            $this->payoutStatusServiceClient->updatePayoutStatusViaFTS(
                $payout->getId(),
                Status::FAILED,
                $ftaFailureReason,
                $ftaBankStatusCode);
        }
        else
        {
            $this->app->events->dispatch('api.payout.failed', [$payout]);
        }
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

        $bas = null;

        if (empty($payout->getUtr()) === false)
        {
            $bas = $this->repo->banking_account_statement->fetchByUtrForPayout($payout);
        }
        if ($bas === null)
        {
            $bas = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout);
        }

        if (empty($bas) === false)
        {
            // raising an alert for RBL payouts for now, to inform the recon team to look into
            // this quickly and reduce the SLA for merchants to see final payout status

            $data = [
                'payout_id'                 => $payout->getId(),
                'account_statement_row'     => $bas->getId(),
            ];

            $operation = 'RBL payout could not be marked as failed, account statement row exists for it';

            (new SlackNotification)->send($operation, $data, null, 1, 'rx_rbl_recon_alerts');

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
                                  Reversal\Entity &$reversal = null)
    {
        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_INITIATED,
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
                        $reversal = (new Reversal\Core)->reverseForPayout($payout);

                        $payout->setFailureReason($reverseReason);

                        $payout->setStatusCode($ftaBankStatusCode);

                        if ($payout->isBalanceAccountTypeDirect() === true)
                        {
                            $this->handleReversalTransactionForDirectBanking($reversal, $credit_bas);
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

                        return $reversal;
                    });
            },
            self::PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
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

        switch ($status)
        {
            case Status::PROCESSED:
                $this->handlePayoutProcessed($payout);
                break;

            case Status::REVERSED:
                $this->handlePayoutReversed($payout, $ftaFailureReason);
                break;

            case Status::FAILED:
                $this->handlePayoutFailed($payout, $ftaFailureReason);
                break;

            default:
                $this->trace->warning(
                    TraceCode::UNKNOWN_STATUS_SENT_TO_PAYOUT,
                    $input);
        }

        return $payout;
    }

    public function updateFTAOfPayoutManually(Entity $payout, array $input)
    {
        /** @var Attempt\Entity $fta */
        $fta = $payout->fundTransferAttempts()->first();

        // Only updating fta failure reason if payout failure reason was updated during this request.
        if ((empty($input[Entity::FAILURE_REASON]) === false) and
            ($payout->wasChanged(Entity::FAILURE_REASON) === true))
        {
            $fta->setFailureReason($input[Entity::FAILURE_REASON]);
        }

        // Only updating fta status if payout status was updated during this request.
        if ((empty($input[Entity::STATUS]) === false) and
            ($payout->wasChanged(Entity::STATUS) === true))
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

                $this->dispatchFtaInitiate($payout);

                $this->processLedgerPayout($payout);

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

            $transaction->setChannel($ftsChannel);

            $this->repo->saveOrFail($transaction);
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

        if ($approved === true)
        {
            return $this->processApprovePayout($payout, $input);
        }

        return $this->processRejectPayout($payout);
    }

    /**
     * @param array $input
     * @param Entity $payout
     * @return Entity
     */
    protected function processApprovePayout(Entity $payout, array $input): Entity
    {
        //setting default queue flag to be true since Queued Payouts is always enabled
        // alongside Payout Workflows till now
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

    public function decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable(string $balanceId, $feeType)
    {
        if ($feeType === Entity::FREE_PAYOUT)
        {
            (new CounterHelper)->decreaseFreePayoutsConsumedInCaseOfTransactionFailure($balanceId);
        }
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

        $freePayoutsCount = (new Merchant\Balance\FreePayout)->getFreePayoutsCount($balance);

        $freePayoutsSupportedModes = (new Merchant\Balance\FreePayout)->getFreePayoutsSupportedModes($balance);

        /** @var Counter\Entity $counter */
        $counter = (new Counter\Repository)->getCounterByAccountTypeAndBalanceId($balance->getAccountType(),
                                                                                 $balanceId);

        $freePayoutsConsumed = ($counter === null) ? 0 : $counter->getFreePayoutsConsumed();

        $response = [
            Merchant\Balance\FreePayout::FREE_PAYOUTS_COUNT           => $freePayoutsCount,
            Counter\Entity::FREE_PAYOUTS_CONSUMED                     => $freePayoutsConsumed,
            Merchant\Balance\FreePayout::FREE_PAYOUTS_SUPPORTED_MODES => $freePayoutsSupportedModes,
        ];

        return $response;
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
            // Admin / Worker(for scheduled payouts) actions
            // On these auth, one can only reject a workflow
            if (($auth->isAdminAuth() === true) ||
                ($auth->isProxyAuth() === false))
            {
                if ($input[Entity::FORCE_REJECT] === true)
                {
                    $this->rejectPendingPayout($payout);
                }

                return $this->workflowService->createDirectAction($payout, $input);
            }

            // Dashboard user actions
            return $this->workflowService->createActionOnEntity($payout, $input);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::PAYOUT_WORKFLOW_ACTION_FAILED_TOTAL);

            $this->trace->error(TraceCode::PAYOUT_WORKFLOW_SERVICE_ACTION_CREATE_FAILED, [
                'payout_id' => $payout->getId(),
                'action'    => Workflow\Service\Adapter\Payout::REJECTED,
            ]);

            throw $e;
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

        if (($auth->isAdminAuth() === true) ||
            ($auth->isProxyAuth() === false))
        {
            throw new Exception\BadRequestValidationFailureException('Auth is not proxy for payout approval');
        }

        try
        {
            // Dashboard user actions
            return $this->workflowService->createActionOnEntity($payout, $input);
        }
        catch (\Throwable $e)
        {
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

        return $this->getProcessor('fund_account_payout')
                    ->createFTAForPayoutService($payoutId);
    }

    public function createPayoutServiceTransaction(array $input)
    {
        (new Validator)->validateInput(Validator::PAYOUT_SERVICE_TRANSACTION_CREATE, $input);

        return $this->getProcessor('fund_account_payout')
                    ->createPayoutServiceTransaction($input);
    }

    public function reversePayoutService(Entity $payout,
                                         string $reverseReason = null,
                                         $ftaBankStatusCode = null,
                                         Reversal\Entity &$reversal = null)
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
            function () use ($payout, $reverseReason, $ftaBankStatusCode, &$reversal) {

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

                $reversal = $this->repo->transaction(
                    function () use ($payout, $reverseReason, $ftaBankStatusCode) {

                        $reversalRequest = [
                            'failure_reason' => $reverseReason,
                        ];

                        $payout->setFailureReason($reverseReason);

                        $payout->setStatusCode($ftaBankStatusCode);

                        // error will be handled by service
                        $response = $this->payoutStatusServiceClient->updatePayoutStatusViaFTS(
                            $payout->getId(),
                            Status::REVERSED,
                            $reverseReason);

                        $balance = $payout->balance;

                        if ($balance->getType() === Merchant\Balance\Type::BANKING)
                        {
                            $this->decreaseFreePayoutsConsumedAndUnsetFeeTypeIfApplicable($payout);
                        }

                        $previousStatus = $payout->getStatus();

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

                        $reversal = $this->repo->reversal->findReversalForPayout($payout->getId());

                        // Need to keep this here because handlePayoutStatusUpdate needs the correct payout status
                        if ($payout->isBalanceAccountTypeDirect() === true) {
                            (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout, $previousStatus, $reversal);
                        }

                        $this->repo->saveOrFail($payout);

                        return $reversal;
                    });
            },
            self::PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    public function updateStatusAfterFtaInitiatedForPayoutService(Entity $payout)
    {
        $this->payoutStatusServiceClient->updatePayoutStatusViaFTS(
            $payout->getId(),
            Status::INITIATED);

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
                Attempt\Entity::FUND_TRANSFER_ID    => (int)$payout->getFTSTransferId(),
                Attempt\Constants::BENEFICIARY_NAME => $ftaData[Attempt\Constants::BENEFICIARY_NAME] ?? null,
                Attempt\Entity::BANK_STATUS_CODE    => $ftaData[Attempt\Entity::BANK_STATUS_CODE] ?? null,
                Attempt\Constants::FTA_STATUS       => $ftaData[Attempt\Constants::FTA_STATUS] ?? null
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
            //event will be fired via payout service.
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

    public function handlePayoutProcessedForPayoutService(Entity $payout)
    {
        $this->payoutStatusServiceClient->updatePayoutStatusViaFTS(
            $payout->getId(),
            Status::PROCESSED,
            "");

        $payout->setStatus(Status::PROCESSED);

        $this->repo->saveOrFail($payout);
        // webhook handled in payout service
    }

    public function handlePayoutReversedForPayoutService(Entity $payout,
                                                         string $ftaFailureReason = null,
                                                         string $ftaBankStatusCode = null,
                                                         Reversal\Entity &$reversal = null)
    {
        $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_REVERSE_REQUEST,
            [
                'payout_id'      => $payout->getId(),
                'failure_reason' => $ftaFailureReason,
            ]);

        // webhook fired via payout service
        $this->reversePayoutService($payout, $ftaFailureReason, $ftaBankStatusCode, $reversal);
    }

    public function checkIfBeneBankIsDown(Entity $payout)
    {
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

    public function processEventNotificationFromFts(array $input)
    {
        try
        {
            if ($input['payload']['source'] === self::BENEFICIARY)
            {
                $beneBankIfsc = $input['payload']['instrument']['bank'];

                $status = $input['payload']['status'];

                (new Validator)->validateBeneStatusReceivedFromFts($status);

                $eventConfigFromFTS = (new Admin\Service)->getConfigKey([
                    'key' => Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT
                ]);

                $this->trace->info(
                    TraceCode::BENE_BANK_EVENT_NOTIFICATION_RECEIVED,
                    [
                        'bank'        => $beneBankIfsc,
                        'status'      => $status,
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

                $this->trace->info(
                    TraceCode::BENE_BANK_EVENT_NOTIFICATION_CONFIG_UPDATE_SUCCESS,
                    [
                        'bene_bank_redis_config' => $eventConfigFromFTS,
                    ]);

                (new Admin\Service)->setConfigKeys(
                    [Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $eventConfigFromFTS]);
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::BENE_BANK_EVENT_NOTIFICATION_CONFIG_UPDATE_FAILED,
                [
                    'input' => $input,
                ]);
            $operation = 'Bene Bank uptime downtime config update failed';

            (new SlackNotification)->send($operation, $input, null, 1, 'x-payouts-core-alerts');
        }
    }

    public function initiateScheduledPayoutsViaPayoutService($input)
    {
        return $this->payoutScheduledServiceClient->processSchedulePayoutViaMicroservice($input);
    }

    public function retryPayoutsOnPayoutService($input)
    {
        return $this->payoutRetryServiceClient->retryPayoutViaMicroservice($input);
    }

    public function getFetchWorkflowSummary($skipFetchFromWfs = false)
    {
        // if the flag $skipFetchFromWfs is set to false then we only fetch the workflow summary from the api db's workflow tables. If set true we fetch from Workflow service as well
        if (($skipFetchFromWfs === false) and
            ($this->isWorkflowServiceEnabled() === true))
        {
            return (new WorkflowConfigService)->getConfigByType('payout-approval', $this->merchant->getId());
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
        $variant = $this->app['razorx']->getTreatment($this->merchant->getId(),
            Merchant\RazorxTreatment::PROCESS_VIA_WORKFLOW_SERVICE,
            $this->mode
        );

        return (strtolower($variant) === 'on');
    }

    public function prepareTemplateAndDispatchEmail($approverList)
    {
        $count = 0;

        if(empty($approverList) === false)
        {
            //User wise grouping the merchant - user information.
            // We will be sending separate emails to a user for different merchants on whom user has payouts awaiting their approval.
            $dataGroupedByUserId = $approverList->groupBy(Merchant\MerchantUser\Entity::USER_ID);

            //picking a user one by one
            foreach ($dataGroupedByUserId as $userData)
            {
                //Merchant wise grouping the information for the picked up user.
                $dataGroupedByMerchantId = $userData->groupBy(Merchant\MerchantUser\Entity::MERCHANT_ID);

                //Picking information specific to the selected merchant-user combination.
                foreach ($dataGroupedByMerchantId as $merchantId => $data) {
                    $input = [
                        'user_id'       => $data->first()['user_id'],
                        'merchant_id'   => $data->first()['merchant_id'],
                        'email'         => $data->first()['email'],
                        'name'          => $data->first()['name'],
                        'business_name' => $data->first()['business_name'],
                        'role'          => $data->first()['role'],
                        'amount_total'  => $data->first()['payout_total'],
                        'total_count'   => $data->first()['payout_count'],
                        'data'          => []
                    ];

                    //Fetching top 5 pending payouts in chronological order for selected merchant-user combination
                    $payouts = $this->repo->payout->fetchPendingPayoutsToDisplay($merchantId, $input['role']);
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

                    $mailable = new PendingApprovals($input);

                    Mail::queue($mailable);

                    $this->trace->info(TraceCode::EMAIL_DISPATCHED_FOR_PENDING_PAYOUTS, [$input]);

                    $count = $count + 1;
                }
            }
        }

        return ['Queued email count' => $count];
    }
}
