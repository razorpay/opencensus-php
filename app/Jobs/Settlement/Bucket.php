<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use Jitendra\Lqext\TransactionAware;
use RZP\Models\Settlement\Bucket\Core;

class Bucket extends Job
{
    use TransactionAware;

    const MAX_ATTEMPTS = 5;

    const JOB_RETRY_INTERVAL = 100;

    const TRANSACTION_SETTLED_AT_ERROR_MESSAGE = 'transactions without settled_at value can not be consumed by new settlement service';

    /**
     * @var string
     */
     protected $queueConfigKey = 'settlement_bucket';

    /**
     * @var mixed|null
     */
     protected $settledAt;

    /**
     * @var string
     */
     protected $transactionId;

    /**
     * @var string
     */
    protected $merchantId;

    protected $allowedTypeForBucketing = [
         Transaction\Type::PAYMENT,
         Transaction\Type::ADJUSTMENT,
         Transaction\Type::COMMISSION,
         Transaction\Type::SETTLEMENT_TRANSFER,
     ];

    /**
     * @param string $mode
     * @param string $txnId
     * @param string|null $merchantId
     * @param null $settledAt
     */
    public function __construct(string $mode, string $txnId, string $merchantId = null, $settledAt = null)
    {
        parent::__construct($mode);

        $this->transactionId = $txnId;

        $this->merchantId = $merchantId;

        $this->settledAt = $settledAt;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        $this->trace->debug(TraceCode::SETTLEMENT_TXN_MIGRATION_NSS_DEBUG_LOG, [
            'merchant_id'       => $this->merchantId,
            'transaction_id'    => $this->transactionId,
            'message'           => 'transaction picked from bucket worker'
        ]);

        try
        {
            $txn = $this->repoManager->transaction->findOrFail($this->transactionId);

            // this is added to ensure that if the authorised transactions are created earlier
            // then after capture dirty reads should not happen and we always get the updated transaction
            if ($txn->getSettledAt() == null)
            {
                throw new LogicException(self::TRANSACTION_SETTLED_AT_ERROR_MESSAGE);
            }

            $core = new Core;

            $balance = $txn->accountBalance;

            $status = $core->shouldProcessViaNewService($txn->getMerchantId(), $balance);

            if ($status === true)
            {
                 $core->publishForSettlement($txn, $balance);
            }
            else if (in_array($txn->getType(), $this->allowedTypeForBucketing) === true)
            {
                if ($txn->isTypePayment() === true)
                {
                    $payment = $txn->source;

                    // Only transactions settlable by razorpay are considered
                    if ($payment->getSettledBy() !== 'Razorpay')
                    {
                        return;
                    }
                }

                $core->addMerchantToSettlementBucket($txn->getId(), $txn->getMerchantId(), $txn->getSettledAt());
            }
        }
        catch (\Throwable $e)
        {
            // if the max attempt is not exhausted then release the job for retry
            if ($this->attempts() <= self::MAX_ATTEMPTS)
            {
                $this->release(self::JOB_RETRY_INTERVAL);
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_ADD_MERCHANT_TO_SETTLEMENT_BUCKET,
                [
                    'transaction_id' => $this->transactionId,
                    'merchant_id'    => $this->merchantId,
                    'settled_at'     => $this->settledAt,
                    'attempt'        => $this->attempts(),
                ]
            );
        }
    }
}
