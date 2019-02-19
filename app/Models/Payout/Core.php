<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Services\Mutex;
use RZP\Models\Customer;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Jobs\FundTransfer;
use RZP\Models\Settlement;
use RZP\Models\FundAccount;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;

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
    const PAYOUT_RETRY                      = 'payout_retry_%s';

    const MUTEX_RESOURCE                    = 'PAYOUT_PROCESSING_%s_%s';

    const CUSTOMER_WALLET_MUTEX_RESOURCE    = 'CUSTOMER_WALLET_PAYOUT_%s_%s_%s';

    const MAX_PAYOUT_AMOUNT                 = 800000000; // 80 Lakhs

    const MUTEX_LOCK_TIMEOUT                = 300;

    const PAYOUT_MUTEX_LOCK_TIMEOUT         = 180;

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
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return mixed|null
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

    /**
     * Payouts to a fund account
     *
     * SOURCE: Merchant Balance (PG/Banking)
     * TO: Fund Account (BankAccount/VPA/Card etc) (fund_account_id)
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function createPayoutToFundAccount(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_TO_FUND_ACCOUNT_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $merchant->getId(), $this->mode);

        $payout = $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $merchant)
            {
                return $this->getProcessor('fund_account_payout')
                            ->setMerchant($merchant)
                            ->createPayout($input);
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);

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

        (new Validator)->validateRetryPayout($payout);

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

                return $this->createPayoutToFundAccount($payoutInput, $payout->merchant);
            }

        }
        else
        {
            $payoutInput = $this->getRetryPayoutInputForMerchant($payout);

            return $this->createPayoutToMerchant($payoutInput, $payout->merchant);
        }
    }

    public function updateStatusAfterFtaRecon(Entity $payout, array $ftaData)
    {
        switch ($ftaData[Attempt\Constants::FTA_STATUS])
        {
            case Attempt\Status::PROCESSED:
                $this->handleFtaProcessed($payout);
                break;

            case Attempt\Status::FAILED:
                $this->handleFtaFailed($payout, $ftaData[Attempt\Constants::FAILURE_REASON]);
                break;

            case Attempt\Status::CREATED:
            case Attempt\Status::INITIATED:
                $this->handleFtaProcessing($payout);
                break;

            default:
                $this->trace->warning(
                    TraceCode::UNKNOWN_FTA_STATUS_SENT_TO_PAYOUT,
                    $ftaData);
        }
    }

    public function updateStatusAfterFtaInitiated(Entity $entity, Attempt\Entity $fta)
    {
        $entity->batchFundTransfer()->associate($fta->batchFundTransfer);

        $entity->setStatus(Status::INITIATED);

        $this->repo->saveOrFail($entity);
    }

    public function updateWithDetailsBeforeFtaRecon(Entity $payout, array $ftaData = [])
    {
        // For non-Yesbank, we will not get public_failure_reason
        $failureReason = $responseData[Attempt\Constants::FAILURE_REASON] ?? null;

        $payout->setUtr($ftaData[Attempt\Constants::UTR]);

        $payout->setRemarks($ftaData[Attempt\Constants::REMARKS]);

        // For VPA type, we always set it to UPI only
        // at build and we don't take the mode from FTA.
        if (empty($ftaData[Attempt\Constants::VPA_ID]) === true)
        {
            $payout->setMode($ftaData[Attempt\Constants::MODE]);
        }

        $payout->setFailureReason($failureReason);

        $this->repo->saveOrFail($payout);
    }

    protected function getRetryPayoutInputForMerchant(Entity $payout): array
    {
        return [
            Entity::AMOUNT      => $payout->getAmount(),
            Entity::CURRENCY    => $payout->getCurrency(),
            Entity::TYPE        => $payout->getPayoutType()
        ];
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

    protected function handleFtaProcessed(Entity $payout)
    {
        $payout->setStatus(Status::PROCESSED);

        $this->repo->saveOrFail($payout);

        $this->app->events->fire('api.payout.processed', [$payout]);
    }

    protected function handleFtaProcessing(Entity $payout)
    {
        $payout->setStatus(Status::PROCESSING);

        $this->repo->saveOrFail($payout);
    }

    protected function handleFtaFailed(Entity $payout, string $ftaFailureReason = null)
    {
        $this->reversePayout($payout, $ftaFailureReason);

        $this->app->events->fire('api.payout.reversed', [$payout]);
    }

    protected function reversePayout(Entity $payout, string $reverseReason = null): Reversal\Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_INITIATED,
            [
                'payout_id' => $payout->getId(),
            ]);

        if ($payout->isStatusReversed() === true)
        {
            throw new Exception\LogicException(
                'Attempted to reverse an already reversed payout',
                [
                    'payout_id'         => $payout->getId(),
                    'status'            => $payout->getStatus(),
                    'reverse_reason'    => $reverseReason,
                ]);
        }

        $reversal = $this->repo->transaction(
            function() use ($payout, $reverseReason) {
                $reversal = (new Reversal\Core)->reverseForPayout($payout);

                $payout->setStatus(Status::REVERSED);

                $payout->setFailureReason($reverseReason);

                $this->repo->saveOrFail($payout);

                return $reversal;
            });

        return $reversal;
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
                    "merchant balance is less than buffer amount",
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
                "amount is less than min amount",
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
}
