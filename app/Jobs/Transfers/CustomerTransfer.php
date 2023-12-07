<?php
namespace RZP\Jobs\Transfers;

use App;
use RZP\Error\ErrorCode;
use RZP\Jobs\Job;
use RZP\Models\Transfer\Metric;
use RZP\Models\Transfer\Utility;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Core;
use RZP\Models\Transfer\Status;
use RZP\Models\Transfer\Entity;
//use Razorpay\Trace\Logger as Trace;
use Jitendra\Lqext\TransactionAware;
use RZP\Models\Customer\Balance\Core as BalanceCore;
use RZP\Models\Customer\Transaction\Core as TransactionCore;

class CustomerTransfer extends Job
{
    protected Entity $transfer;
    protected $source;
    protected $merchantId;
    protected $customerId;
    protected $repo;

    use TransactionAware;


    protected $queueConfigKey = 'customer_openwallet_transfer';

    /**
     * if the job takes more time then it'll be terminated
     *
     * @var int
     */
    public $timeout = 900; // 15 minutes

    const RETRY_INTERVAL = 120; // 2 minutes

    const MAX_RETRY_ATTEMPT = 10;

    public function __construct(string $mode, array $params)
    {
        parent::__construct($mode);

        $this->transfer = $params['transfer'];
        $this->source = $this->transfer->source;
        $this->merchantId = $params['merchant_id'];
        $this->customerId = $params['customer_id'];
    }

    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();
        $this->repo = $app['repo'];

        try
        {
            $this->trace->info(TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER_QUEUE, $this->getContext());

            $merchant = $this->getMerchantById($this->merchantId);
            $this->source = $this->getPaymentEntity($this->source->id);
            $to = $this->getCustomerEntity($this->customerId, $merchant);
            $transfer = $this->transfer;

            // check if transaction is created against payment
            // if transaction is not present then push it into the queue again
            // if the balance is not update we would further delay the transfer processing
            // this happens for merchants who are on async balance update flow
            $delay = $this->checkProcessingDelay($this->source);
            if ($delay === true)
            {
                // Dispatch with delay of 120s (2 min)
                $this->trace->info(TraceCode::CUSTOMER_TRANSFER_JOB_RETRY_DISPATCH, $this->getContext());
                $this->release(self::RETRY_INTERVAL);

                return;
            }

            $startTime = microtime(true);

            // starting a transaction so that at any point of failure, transaction can be reverted
            $this->transfer = $this->repo->transaction(function () use ($transfer, $to, $merchant)
            {
                $transfer = (new Core())->createTransactionForTransfer($transfer);

                // Create customer balance if it doesn't exist.
                (new BalanceCore())->fetchOrCreate($to, $merchant);

                $txn = $transfer->transaction;

                (new TransactionCore())->createForCustomerCredit($transfer,
                    $txn->getAmount(),
                    $to->getId(),
                    $merchant);

                (new Core())->createLedgerEntriesForCustomerTransfer($transfer, $merchant);

                $transfer->setProcessed();
                $this->repo->transfer->saveOrFail($transfer);

                return $transfer;
            });

            $endTime = microtime(true);

            // if the transfer failed due to other reasons then fire the failure webhook
            if ($transfer->isProcessed() === true)
            {
                $this->trace->info(TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER_QUEUE_COMPLETED,
                    [
                        'time_taken'                   => $endTime - $startTime,
                        'merchant'                     => $this->merchantId,
                        'mode'                         => $this->mode,
                        'transfer'                     => $this->transfer->id
                    ]);
                $this->fireTransferProcessedWebhookIfApplicable($this->transfer);
                (new Metric())->pushCustomerTransferSuccessMetrics();
            }

            $this->delete();
        }
        catch( \Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER_QUEUE, $this->getContext());

            (new Metric())->pushCustomerTransferFailedMetrics($e);

            if ((new Utility())->isRetryableError($e) === true)
            {
                $retryTime = (new Utility())->getDelay($e);

                $this->checkRetry($retryTime, $e);
            }
            $this->setTransferFailed($e);
        }
    }

    protected function checkRetry($retryTime, \Exception $e): void
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::CUSTOMER_TRANSFER_JOB_RETRY_EXHAUSTED, $this->getContext());
            $this->delete();
            // if retries are exhausted then we mark the transfer as failed
            if ($this->transfer->getStatus() !== Status::PROCESSED)
            {
                $this->setTransferFailed($e);
            }
        }
        else
        {
            $this->trace->info(TraceCode::CUSTOMER_TRANSFER_JOB_RETRY_DISPATCH, $this->getContext());
            $this->release($retryTime);
        }
    }

    // if transaction doesn't exist for the payment
    // or balance is not updated, return false so that it can be retried
    // after some time
    private function checkProcessingDelay($source): bool
    {
        $transaction =  $source->transaction;

        if ((empty($transaction) === true) or
            ($transaction->isBalanceUpdated() === false))
        {
            return true;
        }

        return false;
    }

    private function getPaymentEntity($paymentId)
    {
        try
        {
            return $this->repo->payment->findOrFailPublic($paymentId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::CUSTOMER_TRANSFER_PAYMENT_ID_NOT_FOUND,
                [
                    'message'      => 'paymentId not found',
                    'payment_id'   => $paymentId
                ]
            );

            throw $ex;
        }
    }

    private function getCustomerEntity($toId, $merchant)
    {
        try
        {
            return $this->repo->customer->findByPublicIdAndMerchant($toId, $merchant);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::CUSTOMER_NOT_FOUND,
                [
                    'message'       => 'customer not found associated with merchant',
                    'customer_id'   => $toId,
                    'merchant_id'   => $merchant->getId()
                ]
            );

            throw $ex;
        }
    }

    private function getMerchantById($merchantId)
    {
        try
        {
            return $this->repo->merchant->findOrFail($merchantId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::MERCHANT_NOT_FOUND,
                [
                    'message'       => 'merchant not found',
                    'merchant_id'   => $merchantId
                ]
            );

            throw $ex;
        }
    }

    private function fireTransferProcessedWebhookIfApplicable($transfer): void
    {
        if ($transfer->isProcessed() === true)
        {
            (new Core())->eventTransferProcessed($transfer);
        }
    }

    private function fireTransferFailedWebhookIfApplicable($transfer): void
    {
        if ($transfer->isFailed() === true)
        {
            (new Core())->eventTransferFailed($transfer);
        }
    }

    private function getContext(): array
    {
        return [
            'transfer'      => $this->transfer->id,
            'source'        => $this->source->id,
            'customer_id'   => $this->customerId,
            'merchant_id'   => $this->merchantId,
            'mode'          => $this->mode
        ];
    }

    private function setTransferFailed(\Exception $e): void {
        $this->transfer->setFailed();
        $this->transfer->setMessage($e->getMessage());
        $this->transfer->setErrorCode(ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_FAILED);
        $this->repo->transfer->saveOrFail($this->transfer);
        $this->fireTransferFailedWebhookIfApplicable($this->transfer);
    }
}
