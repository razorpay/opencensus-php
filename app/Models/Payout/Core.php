<?php

namespace RZP\Models\Payout;

use App;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use DeepCopy\DeepCopy;
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
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Permission;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccountStatement;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;

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

    const PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT = 3600;

    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
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

        $ftaFailureReason = $ftaData[Attempt\Constants::FAILURE_REASON] ?? null;
        $ftaBankStatusCode = $ftaData[Attempt\Entity::BANK_STATUS_CODE] ?? null;

        switch ($status)
        {
            case Status::PROCESSED:
                $this->handlePayoutProcessed($payout);
                break;

            case Status::REVERSED:
                $this->handlePayoutReversed($payout, $ftaFailureReason, $ftaBankStatusCode);
                break;

            case Status::FAILED:
                $this->handlePayoutFailed($payout, $ftaFailureReason, $ftaBankStatusCode);
                break;

            case Status::CREATED:
            case Status::INITIATED:
                break;

            default:
                $this->trace->warning(
                    TraceCode::UNKNOWN_FTA_STATUS_SENT_TO_PAYOUT,
                    $ftaData);
        }
    }

    public function updateStatusAfterFtaInitiated(Entity $payout, Attempt\Entity $fta)
    {
        $payout->batchFundTransfer()->associate($fta->batchFundTransfer);

        Status::validateStatusUpdate(Status::INITIATED, $payout->getStatus());

        $payout->setStatus(Status::INITIATED);

        $this->repo->saveOrFail($payout);
    }

    public function updateWithDetailsBeforeFtaRecon(Entity $payout, array $ftaData = [])
    {
        $this->trace->info(
            TraceCode::PAYOUT_UPDATE_BEFORE_FTA_RECON,
            [
                'payout_id' => $payout->getId(),
            ]);

        // For non-Yesbank, we will not get public_failure_reason
        $ftaFailureReason = $ftaData[Attempt\Constants::FAILURE_REASON] ?? null;

        $initialUtr = $payout->getUtr();

        $payout->setUtr($ftaData[Attempt\Constants::UTR]);

        $payout->setRemarks($ftaData[Attempt\Constants::REMARKS]);

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

        if (($initialUtr === null) and
            ($payout->getUtr() !== null))
        {
            $this->app->events->fire('api.payout.updated', [$payout]);
        }
    }

    public function fetchAndUpdateGatewayBalance(BankingAccount\Entity $merchantBankingAccount)
    {
        $balanceLastFetchedAt = $merchantBankingAccount->getBalanceLastFetchedAt();

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
            $merchantBankingAccount = (new BankingAccount\Core)->fetchAndUpdateGatewayBalance($merchantBankingAccount);
        }

        return $merchantBankingAccount;
    }

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
                /** @var BankingAccount\Entity $merchantBankingAccount */
                $merchantBankingAccount = $balanceEntity->bankingAccount;

                $merchantBankingAccount = $this->fetchAndUpdateGatewayBalance($merchantBankingAccount);

                $balanceAmount = $merchantBankingAccount->getGatewayBalance();

                // Suppose merchant makes request soon after code is deployed and cron hasn't run yet,
                // then gateway_balance will be null . In that case use balance from balance table
                $balanceAmount = $balanceAmount ?? $balanceEntity->getBalance();
            }

            $dispatchedData = $this->dispatchApplicablePayouts($balanceAmount, $payouts);

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

                    return $payout;
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    public function cancelPayout(Entity $payout): Entity
    {
        // If Payout has purpose 'rzp_fees' we won't allow merchant to cancel that
        if (Purpose::isInInternal($payout->getPurpose()) === true)
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
                function() use ($payout)
                {
                    $payout->getValidator()->validateCancel();

                    $payout->setStatus(Status::CANCELLED);

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

    public function rejectPayout(Entity $payout, array $input): Entity
    {
        $payout = $this->processWorkflowActionOnPayout($payout, false, $input);

        return $payout;
    }

    protected function processWorkflowActionOnPayout(Entity $payout, bool $approve, array $input): Entity
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
            function() use ($payout, $workflowAction, $approve, $action, $input)
            {
                $userComment = $input[Workflow\Action\Checker\Entity::USER_COMMENT] ?? null;

                $actionCheckerCreateParams = [
                    Workflow\Action\Checker\Entity::ACTION_ID    => $workflowAction->getId(),
                    Workflow\Action\Checker\Entity::APPROVED     => ($approve === true) ? 1 : 0, // 1 = true
                ];

                if ($userComment !== null)
                {
                    $actionCheckerCreateParams[Workflow\Action\Checker\Entity::USER_COMMENT] = $userComment;
                }

                $actionChecker = (new Workflow\Action\Checker\Core)->create($actionCheckerCreateParams);

                if (empty($actionChecker) === true)
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
                    //setting default queue flag to be true since Queued Payouts is always enabled
                    // alongside Payout Workflows till now
                    $queueFlag = isset($input[Entity::QUEUE_IF_LOW_BALANCE]) ?
                                 $input[Entity::QUEUE_IF_LOW_BALANCE] : true;

                    $payout = $this->processPendingPayout($payout, $queueFlag);
                }
                else if (($approve === false) and
                        ($workflowAction->isRejected() === true))
                {
                    $payout = $this->processRejectPayout($payout);
                }

                return $payout;
            });

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

    protected function dispatchApplicablePayouts(int $totalBalance, Base\PublicCollection $payouts)
    {
        $dispatchedCount = 0;

        // This is only false when there is a queued fee_recovery payout and the merchant doesn't
        // have enough balance for that payout
        $rzpFeesRecoverySucceeded = true;

        foreach ($payouts as $key => $payout)
        {
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
                $totalPayoutAmount = $payoutAmount + $payoutFees;
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

    protected function handlePayoutProcessed(Entity $payout)
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

        $this->repo->transaction(
            function() use ($payout) {
                $payout->setStatus(Status::PROCESSED);

                $this->repo->saveOrFail($payout);

                if ($payout->isBalanceAccountTypeDirect() === true)
                {
                    $this->handlePayoutTransactionForDirectBanking($payout);

                    (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout);
                }
            });

        $this->app->events->fire('api.payout.processed', [$payout]);
    }

    /**
     * TODO: The logic here could change for different banks. The structure needs to be accommodated for that.
     * JIRA: https://razorpay.atlassian.net/browse/RX-698
     *
     * @param Entity $payout
     *
     * @throws Exception\LogicException
     */
    protected function handlePayoutTransactionForDirectBanking(Entity $payout)
    {
        $bas = null;

        try
        {
            // Fetch BAS for this payout
            if (empty($payout->getUtr()) === false)
            {
                $bas = $this->repo->banking_account_statement->fetchByUtrForPayout($payout)->first();
            }
            else
            {
                $bas = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout)->first();
            }
        }
        catch (\Throwable $e)
        {
            // This happens when a payout is be mapped to multiple bas entities
            // we do not want to throw an exception here, as this operation occurs in a db txn
            $this->trace->traceException($e);
        }

        //
        // For direct banking, it's possible we have figured out the transaction via account statement
        // even before the payout is actually marked as processed via FTS recon. In that case, we don't have
        // to try and figure out a transaction from an existing set of transactions.
        //
        if ($payout->hasTransaction() === true)
        {
            // If this payout has a txn linked, a BAS entity should always be present for this payout
            if (empty($bas) === true)
            {
                $this->trace->error(
                    TraceCode::PAYOUT_HAS_TRANSACTION_BUT_NO_MAPPING_TO_BAS,
                    [
                        'payout_id'         => $payout->getId(),
                        'txn_id'            => $payout->getTransactionId(),
                        'channel'           => $payout->getChannel(),
                    ]);
            }

            return;
        }

        // This happens when account statement has not been fetched yet, or we were unable to map the BAS to a payout
        if (empty($bas) === true)
        {
            return;
        }

        $transaction = $bas->transaction;

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

    protected function handleReversalTransactionForDirectBanking(Reversal\Entity $reversal)
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

        $bas = $this->repo->banking_account_statement->fetchByUtrForReversal($reversal)->first() ??
               $this->repo->banking_account_statement->fetchByCmsRefNumForReversal($reversal)->first();

        // This happens when account statement has not been fetched yet, or we were unable to map the BAS to a reversal
        if (empty($bas) === true)
        {
            return;
        }

        $transaction = $bas->transaction;

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

    protected function updateTransactionAndSourceToPayout(Entity $payout, Transaction\Entity $transaction)
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

        // TODO: check if dispatchEventForTransactionUpdated can be used
        // JIRA: https://razorpay.atlassian.net/browse/RX-697
        // skipping this code as there is no templatef for transaction.created and this
        // method will throw an exception in Mail/Transaction/Payout as there is not handling
        // for event transaction.created
        //  (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
    }

    protected function updateTransactionAndSourceToReversal(Reversal\Entity $reversal, Transaction\Entity $transaction)
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

        // skipping this code as there is no templatef for transaction.created and this
        // method will throw an exception in Mail/Transaction/Payout as there is not handling
        // for event transaction.created
        //(new Transaction\Core)->dispatchEventForTransactionCreated($reversal->transaction);
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

                $dummyFeesBreakup = (new Transaction\Processor\Payout($clonedPayout))->getFeeSplitForDirectPayouts(
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
        /** @var Base\PublicCollection $originalFeesBreakup */
        $originalFeesBreakup = $transaction->feesBreakup;

        // Since external entities do not have any fees_breakup, create them now
        if ($originalFeesBreakup->count() === 0)
        {
            (new Transaction\Core)->saveFeeDetails($transaction, $dummyFeesBreakup);
        }
        else
        {
            //
            // Since external entities should not have any fees_breakup, throw an exception
            //
            throw new Exception\LogicException(
                'External entity should not have any fee breakup',
                null,
                [
                    'transaction_id'        => $transaction->getId(),
                    'source_id'             => $transaction->source->getPublicId(),
                    'fee_breakup_count'     => $originalFeesBreakup->count(),
                ]);
        }
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

    protected function handlePayoutReversed(Entity $payout,
                                            string $ftaFailureReason = null,
                                            string $ftaBankStatusCode = null)
    {
        $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

        $this->reversePayout($payout, $ftaFailureReason);

        $this->app->events->fire('api.payout.reversed', [$payout]);
    }

    protected function handlePayoutFailed(Entity $payout,
                                          string $ftaFailureReason = null,
                                          string $ftaBankStatusCode = null)
    {
        $ftaFailureReason = $this->getPublicErrorMessage($payout, $ftaFailureReason, $ftaBankStatusCode);

        $this->verifyPayoutFailedTransaction($payout);

        $currentStatus = $payout->getStatus();

        //
        // Payout can go to failed state from initiated or created state only
        //
        Status::validateStatusUpdate(Status::FAILED, $currentStatus);

        $this->repo->transaction(
            function() use ($payout, $ftaFailureReason) {
                $payout->setStatus(Status::FAILED);

                $payout->setFailureReason($ftaFailureReason);

                $this->repo->saveOrFail($payout);

                if ($payout->isBalanceAccountTypeDirect() === true)
                {
                    (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout);
                }
            });

        $this->app->events->fire('api.payout.failed', [$payout]);
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
            $bas = $this->repo->banking_account_statement->fetchByUtrForPayout($payout)->first();
        }
        else
        {
            $bas = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout)->first();
        }

        if (empty($bas) === false)
        {
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

    public function reversePayout(Entity $payout, string $reverseReason = null)
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
            function () use ($payout, $reverseReason)
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

                $this->repo->transaction(
                    function() use ($payout, $reverseReason) {
                        $reversal = (new Reversal\Core)->reverseForPayout($payout);

                        $payout->setFailureReason($reverseReason);

                        if ($payout->isBalanceAccountTypeDirect() === true)
                        {
                            $this->handleReversalTransactionForDirectBanking($reversal);
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
                            (new FeeRecovery\Core)->handlePayoutStatusUpdate($payout, $reversal);
                        }

                        $this->repo->saveOrFail($payout);
                    });
            },
            self::PAYOUT_REVERSAL_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

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

    protected function getProcessor(string $type): Processor\Base
    {
        $processor = __NAMESPACE__ . '\\' . 'Processor';

        $processor .= '\\' . studly_case($type);

        return new $processor();
    }

    protected function dispatchFtaInitiate(Entity $payout)
    {
        //
        // For payouts with status=(queued, pending), we don't create any transaction or FTA.
        // We do it later when we actually process that payout.
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

                $payout->setStatus(Status::REJECTED);

                $this->repo->saveOrFail($payout);

                $this->app->events->fire('api.payout.rejected', [$payout]);

                return $payout;
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }
}
