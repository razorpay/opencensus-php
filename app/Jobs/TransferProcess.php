<?php

namespace RZP\Jobs;

use App;
use Exception;
use RZP\Constants\Entity;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Core;
use RZP\Models\Transfer\Metric;
use RZP\Models\Transfer\Utility;
use RZP\Models\Transfer\Constant;
use RZP\Models\Ledger\ReverseShadow;
use RZP\Exception\LogicException;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Exception\BadRequestException;
use Throwable;

class TransferProcess extends Job
{
    const MUTEX_LOCK_TIMEOUT = 600;

    protected $mutex;

    protected $repo;

    protected $payment;

    protected $transferMode;

    protected $isReverseShadowTxnCreate;

    protected $transferInput;

    public $timeout = 900;

    protected $queueConfigKey = 'transfer_process';

    protected int $attemptLimit = 0;

    public function __construct(string $mode, $payment, $transfermode = Transfer\Constant::ORDER, $isReverseShadowTxnCreate = false, $transferInput = [])
    {
        parent::__construct($mode);

        $this->payment = $payment;

        $this->transferMode = $transfermode;

        $this->isReverseShadowTxnCreate = $isReverseShadowTxnCreate;

        $this->transferInput = $transferInput;

        $totalRetryAttempts = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RETRY_TRANSFER_FAILURE_TOTAL_ATTEMPTS]);

        if (empty($totalRetryAttempts) === false)
        {
            $this->attemptLimit = $totalRetryAttempts;
        }
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::TRANSFER_PROCESS_QUEUE_BEGIN,
                [
                    'payment_id'   => $this->payment,
                    'transfermode' => $this->transferMode
                ]
            );

            $this->payment = $this->getPaymentEntity($this->payment);

            // if the balance is not update we would further delay the transfer processing
            // this happens for merchants who are on async balance update flow
            $delay = $this->checkProcessingDelay($this->payment);

            $this->trace->info(
                TraceCode::TRANSFER_PROCESS_QUEUE_DELAY,
                [
                    'payment_id'   => $this->payment->getId(),
                    'transfermode' => $this->transferMode,
                    'delay'        => $delay
                ]
            );

            if ($this->isReverseShadowTxnCreate ===  true)
            {
                // In pg ledger reverse Shadow phase,we create journals in CLS followed by transaction creation in async.
                // For transfers use case, we push payload to outbox table to create journals in CLS.
                // Upon receiving successful acknowledgement of journal creation, we mark transfers as processed and
                // push them to queues again to create credit and debit txns for transfer and transfer payment in API ledger as well.
                (new Transfer\Core)->createTransferTransactionsInReverseShadow($this->payment, $this->transferInput);

                $this->delete();

                return;
            }

            if ($delay === true)
            {
                $delaySecs = $this->getProcessingRetryDelay($this->payment->merchant);

                (new Transfer\Core)->dispatchForTransferProcessing($this->transferMode, $this->payment, $delaySecs);

                $this->delete();

                return;
            }

            $this->trace->info(
                TraceCode::TRANSFER_PROCESS_QUEUE,
                [
                    'payment_id'   => $this->payment->getId(),
                    'transfermode' => $this->transferMode
                ]
            );

            $transfer = null;

            if ($this->transferMode === Transfer\Constant::ORDER)
            {
                $transfer = new Transfer\OrderTransfer($this->payment);
            }
            else
            {
                $transfer = new Transfer\PaymentTransfer($this->payment);
            }

            [, $failedTransfersToRetry] = $transfer->process();

            if(empty($failedTransfersToRetry) === false)
            {
                $this->checkRetry(Utility::INSUFFICIENT_BALANCE_RETRY_INTERVAL, $failedTransfersToRetry);

                return null;
            }

            $this->delete();

        }
        catch (\Exception $ex)
        {
            if ($this->isReverseShadowTxnCreate === true)
            {
                (new Metric())->pushMetricForTransferTransactionsCreate($ex);

                $this->trace->info(
                    TraceCode::RETRY_TRANSFER_TXN_IN_REVERSE_SHADOW,
                    [
                        'payment_id'    => $this->payment,
                        'attempt_count' => $this->attempts(),
                    ]
                );

                $this->release(600);

                return;
            }
            else
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::TRANSFER_FAILURE,
                    [
                        'message'     => 'transfer failed',
                        'payment_id'   => $this->payment,
                        'transfermode' => $this->transferMode,
                    ]
                );

                (new Metric())->pushTransferProcessFailedMetrics($ex);

                if ((new Utility)->isRetryableError($ex) === true)
                {
                    $retryTime = (new Utility)->getDelay($ex);

                    $this->checkRetry($retryTime);
                }
                else
                {
                    $this->delete();
                }
            }
        }
    }

    private function checkProcessingDelay($payment)
    {
        $isTransferHoldFlagEnabled =  (new Transfer\Core)->isTransferOnHoldFlagEnabled($payment->merchant);

        if ($isTransferHoldFlagEnabled === true)
        {
            $this->trace->info(
                TraceCode::TRANSFER_PROCESS_HOLD_FLAG_ENABLED,
                [
                    'payment_id'                  => $this->payment->getId(),
                    'is_hold_flag_enabled'        => true,
                ]
            );

            return true;
        }

        $merchant = $this->repo->merchant->findOrFailPublic($payment->getMerchantId());
        if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW))
        {
            $journal = (new ReverseShadow\Payments\Core())->fetchLedgerJournalForPaymentMerchantCapture($payment);

            $this->trace->info(
                TraceCode::TRANSFER_PROCESS_PAYMENT_JOURNAL_INFO,
                [
                    'payment_id'                  => $this->payment->getId(),
                    'is_journal_created'          => (empty($journal) === false),
                ]
            );

            $isApiTxnBalanceUpdated = false;

            if ($journal === null)
            {
                // fallback to fetch from payment fetch replica, this handles cases where transfer is created
                // for a payment which was created before the merchant was onboarded to reverse shadow
                $app = App::getFacadeRoot();

                $repo = $app['repo'];

                $txn = $repo->transaction->fetchPaymentTransactionFromPaymentFetchReplica($payment);

                if ((empty($txn) === false) && ($txn->isBalanceUpdated() === true))
                {
                    $isApiTxnBalanceUpdated = true;
                }

                $this->trace->info(
                    TraceCode::TRANSFER_PROCESS_PAYMENT_JOURNAL_INFO,
                    [
                        'payment_id'             => $this->payment->getId(),
                        'txn_created'            => (empty($txn) === false),
                        'balance_updated'        => $isApiTxnBalanceUpdated,
                    ]
                );
            }

            if ((empty($journal) === false) || ($isApiTxnBalanceUpdated === true))
            {
                // CLS journal is created or the API txn balance is updated.
                // In either case, do not re-push to the queue
                return false;
            }

            return true;
        }

        $transaction = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

        if ((empty($transaction) === true) or
            ($transaction->isBalanceUpdated() === false))
        {
            return true;
        }

        return false;
    }

    private function getPaymentEntity($paymentId)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        try
        {
            return $this->repo->payment->findOrFailPublic($paymentId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::TRANSFER_PROCESS_PAYMENT_ID_NOT_FOUND,
                [
                    'message'      => 'paymentId not found',
                    'payment_id'   => $this->payment,
                    'transfermode' => $this->transferMode,
                ]
            );

            throw $ex;
        }
    }

    protected function checkRetry($delay, $failedTransfersToRetry = [])
    {
        if ($this->attempts() > $this->attemptLimit)
        {
            $this->trace->error(
                TraceCode::TRANSFER_FAILED_POST_ALL_RETRIES,
                [
                    'payment_id' => $this->payment,
                ]
            );

            if (empty($failedTransfersToRetry) === false)
            {
                foreach ($failedTransfersToRetry as $transfer)
                {
                    $transfer->setFailed();

                    $transfer->incrementAttempts();

                    $this->repo->saveOrFail($transfer);

                    $this->fireTransferFailedWebhookIfApplicable($transfer);
                }
            }

            $this->delete();
        }
        else
        {
            $this->trace->info(
                TraceCode::TRANSFER_FAILURE_RETRY_DISPATCH,
                [
                    'payment_id' => $this->payment,
                ]
            );

            $this->release($delay);
        }
    }

    protected function fireTransferFailedWebhookIfApplicable(Transfer\Entity $transfer)
    {
        $source = $transfer->getSourceType();

        //
        // Payment transfers are not retried on failure whereas order transfers are
        // retried thrice. The webhook is being triggered below based on this.
        //
        if (($source === Constant::PAYMENT) or
            (($source === Constant::ORDER) and ($transfer->getAttempts() === Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS)))
        {
            (new Core())->eventTransferFailed($transfer);
        }
    }

    protected function getProcessingRetryDelay($merchant)
    {
        if (in_array($merchant->getCategory(), Transfer\Constant::CATEGORY_1_MCC))
        {
            return 3 * 60;  // 3 minutes
        }
        else if (in_array($merchant->getCategory(), Transfer\Constant::CATEGORY_2_MCC))
        {
            return 10 * 60;  // 10 minutes
        }

        return 15 * 60;  // 15 minutes
    }
}
