<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Services\Mutex;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Merchant as SettlementMerchant;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class Core extends Base\Core
{
    const PAYOUT_RETRY          = 'payout_retry_%s';

    const MUTEX_RESOURCE        = 'PAYOUT_PROCESSING_%s_%s';

    const MAX_PAYOUT_AMOUNT     = 800000000; // 80 Lakhs

    const MUTEX_LOCK_TIMEOUT    = 300;

    const PAYOUT_MUTEX_LOCK_TIMEOUT = 180;

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

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function () use ($input, $merchant)
            {
                $amount = $this->getMerchantPayoutAmount($input, $merchant);

                $currency = $this->getCurrency($input);

                $onDemand = $this->getOnDemandStatus($input);

                $payoutInput = [
                    Entity::PURPOSE   => FundTransferAttempt\Purpose::SETTLEMENT,
                    Entity::AMOUNT    => $amount,
                    Entity::CURRENCY  => $currency,
                    Entity::METHOD    => Method::FUND_TRANSFER,
                    Entity::TYPE      => $onDemand,
                ];

                return $this->getProcessor('merchant_payout', $merchant)->createPayout($payoutInput);
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);
    }

    public function createPayoutToContact(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_CONTACT_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $merchant->getId(), $this->mode);

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $merchant)
            {
                return $this->getProcessor('contact_payout', $merchant)->createPayout($input);
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);

    }

    public function createPayoutToCustomerWallet(string $customerId, array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_CUSTOMER_WALLET_CREATE_REQUEST,
            [
                'input' => $input,
                'customer_id' => $customerId
            ]);

        // We are doing this so that validations do not fail in createPayout.
        // We don't want to remove it from the input validation to ensure that
        // customer wallet payout always has a customer_id.
        // (instead of relying on function params)
        $input[Entity::CUSTOMER_ID] = $customerId;

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $merchant->getId(), $this->mode);

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $customerId, $merchant)
            {
                return $this->getProcessor('customer_wallet_payout', $merchant, $customerId)->createPayout($input);
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);
    }

    public function createPayoutFromPayment(Payment\Entity $payment, array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_FOR_PAYMENT_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        //
        // The mutex for this is handled in `createPayoutToContact`.
        //

        (new Validator)->validatePaymentForPayout($input, $payment);

        $payout = $this->createPayoutToContact($input, $merchant);

        $payout->payment()->associate($payment);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    public function retryFailedPayouts(array $input): array
    {
        $this->trace->info(
            TraceCode::MERCHANT_PAYOUT_RETRY_REQUEST,
            $input);

        (new Validator)->validateInput('payout_retry', $input);

        $ids = Entity::verifyIdAndStripSignMultiple($input['ids']);

        $payouts = $this->repo->payout->fetchFailedPayouts($ids);

        $mutexResource = sprintf(self::PAYOUT_RETRY, $this->mode);

        $result = $this->mutex->acquireAndRelease(
            $mutexResource,
            function () use ($payouts)
            {
                return $this->attemptRetryForFailedPayouts($payouts);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ANOTHER_OPERATION_IN_PROGRESS);

        return $result + [
            'not_attempted' => array_diff($ids, $payouts->getIds()),
        ];
    }

    protected function attemptRetryForFailedPayouts(Base\PublicCollection $payouts): array
    {
        $payoutsRetried = [];

        $retryFailed = [];

        foreach ($payouts as $payout)
        {
            $channel = $payout->getChannel();

            $merchantSettler = new SettlementMerchant($payout->merchant, $channel, $this->repo);

            try
            {
                $payout = $this->repo->transaction(
                    function () use ($merchantSettler, $payout)
                    {
                        return $merchantSettler->retryFailedPayout($payout);
                    });

                $payoutsRetried[] = $payout->getId();
            }
            catch (\Throwable $e)
            {
                $retryFailed[] = $payout->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::MERCHANT_PAYOUT_RETRY_FAILED,
                    [
                        'id'      => $payout->getId(),
                        'message' => $e->getMessage()
                    ]
                );

                continue;
            }

            return [
                'payouts_retried'       => $payoutsRetried,
                'failed_retries'        => $retryFailed,
            ];
        }

        $this->trace->info(
            TraceCode::MERCHANT_PAYOUT_RETRIED_IDS,
            $payoutsRetried);

        return $payoutsRetried;
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

    protected function getProcessor(string $type, Merchant\Entity $merchant, ...$args): Processor\Base
    {
        $processor = __NAMESPACE__ . '\\' . 'Processor';

        $processor .= '\\' . studly_case($type);

        return new $processor($merchant, ...$args);
    }
}
