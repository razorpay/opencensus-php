<?php

namespace RZP\Models\Transfer;

use Exception;
use Throwable;
use RZP\Constants;
use RZP\Models\Admin;
use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use Illuminate\Support\Str;
use RZP\Exception\LogicException;
use Illuminate\Support\Facades\App;
use RZP\Exception\BadRequestException;
use RZP\Base\Database\DetectsLostConnections;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Models\Partner\Service as PartnerService;

abstract class AbstractTransfer
{
    protected $payment;

    const MUTEX_LOCK_TIMEOUT = 600;
    const MUTEX_NUM_RETRIES = 0;
    const MUTEX_MIN_RETRY_DELAY_MS = 100;
    const MUTEX_MAX_RETRY_DELAY_MS = 200;

    protected $mutex;

    protected $app;

    protected $repo;

    protected $trace;

    protected $mode;

    protected $merchant;

    protected $tracecode;

    protected $sourceId;

    protected $transfermode;

    protected $invalidCode;

    protected $failurecode;

    protected  $status;

    protected $partner;

    use DetectsLostConnections;

    /**
     * AbstractTransfer constructor.
     */
    public function __construct($payment)
    {
        $this->payment = $payment;

        $this->app = App::getFacadeRoot();

        $this->mutex = $this->app['api.mutex'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->env = $this->app['env'];

        $this->trace = $this->app['trace'];

        $this->config = $this->app['config'];

        $this->repo = $this->app['repo'];

        $this->partner = $this->app['basicauth']->getPartnerMerchant();
    }

    public function processOrderTransfers(Payment\Entity $payment)
    {
        $startTime = microtime(true);

        $this->merchant = $this->repo->merchant->findOrFail($payment->getMerchantId());

        $transferStatus = $this->status;

        $transfers = Tracer::inSpan(['name' => 'transfer.process.fetch_by_source'], function() use ($transferStatus)
        {
            return $this->repo
                        ->transfer
                        ->fetchBySourceTypeAndIdAndMerchant($this->transfermode,  $this->sourceId, $this->merchant , $transferStatus);
        });

        $this->trace->info($this->tracecode,
            [
                'payment_id'   => $payment->getPublicId(),
                'source_id'     => $this->sourceId,
                'transfer_ids' => $transfers->getIds(),
                'transferMode' =>  $this->transfermode,
            ]);

        $failedTransfersToRetry = [];

            foreach ($transfers as $transfer)
            {
                try
                {
                    $transferProcessStartTime = microtime(true);

                    $this->processTransferWithRetry($payment, $transfer);
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException(
                        $e,
                        null,
                        $this->failurecode,
                        [
                            'payment_id' => $payment->getPublicId(),
                            'transfer' => $transfer->toArrayPublic(),
                            'transfermode' => $this->transfermode,
                        ]
                    );

                    if ((new Utility)->isRetryableError($e) === true)
                    {
                        $failedTransfersToRetry[] = $transfer;
                        continue;
                    }

                    $transfer->setFailed();

                    $transfer->setMessage($e->getMessage());

                    $this->verifyAndSetErrorCode($transfer, $e->getCode());

                    $transfer->incrementAttempts();

                    $this->repo->saveOrFail($transfer);

                    $this->fireTransferFailedWebhookIfApplicable($transfer);
                }
                finally
                {
                    $transferProcessEndTime = microtime(true);

                    (new Metric())->pushTransferProcessingTimeInWorkerMetrics(
                        $transfer->getSourceType(),
                        ($transferProcessEndTime - $transferProcessStartTime)
                    );

                    (new Core())->trackTransferProcessingTime($transfer, $payment);
                }
            }

        $endTime = microtime(true);

        (new Metric())->pushSourceIdProcessingTimeInWorkerMetrics($this->transfermode, ($endTime - $startTime));

        return [$transfers, $failedTransfersToRetry];
    }

    public function processTransferWithRetry($payment, $transfer)
    {
        for ($i = 0; $i <= Constant::TRANSFER_PROCESS_RETRIES; $i++)
        {
            try
            {
                $this->processTransfers($payment, $transfer, $this->merchant);

                break;
            }
            catch (\Illuminate\Database\QueryException | \PDOException $ex)
            {
                if ($this->isErrorDueToDBLostConnection($ex) === false)
                {
                    throw $ex;
                }

                $this->retryProcessTransferIfWithinRetryLimit($transfer, $i, $ex);
            }
            catch (DbQueryException | LogicException $ex)
            {
                if (($transfer->isBalanceTransfer() === true) or
                    (($ex instanceof LogicException) and ($ex->getMessage() !== Constant::BALANCE_UPDATE_WITH_OLD_BALANCE_CHECK_FAILED)))
                {
                    throw $ex;

                    break;
                }

                $this->retryProcessTransferIfWithinRetryLimit($transfer, $i, $ex);
            }
            catch (\Throwable $ex)
            {
                throw $ex;

                break;
            }
        }
    }

    protected function retryProcessTransferIfWithinRetryLimit($transfer, $attempt, $ex)
    {
        $transfer->reload();

        if ($attempt === Constant::TRANSFER_PROCESS_RETRIES)
        {
            throw $ex;
        }
        else
        {
            $this->trace->traceException($ex);
        }
    }

    public function processTransfers($payment, $transfer, $merchant)
    {
        $this->merchant = $merchant;

        if (($transfer->isFailed() === true) and
            ($transfer->getAttempts() >= Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS))
        {
            $this->trace->info(
                $this->invalidCode,
                [
                    'payment_id'    => $payment->getPublicId(),
                    'transfer'      => $transfer->toArrayPublic(),
                    'transfermode'  => $this->transfermode,
                ]
            );

            return;
        }

        $isTransferFailed = $this->failTransferIfLinkedAccountIsSuspended($transfer, $merchant);

        if ($isTransferFailed === true)
        {
            return;
        }

        $deadlockRetryAttempts = 3;

        try
        {
            $transfer = $this->repo->transaction(function () use ($payment, $transfer)
            {
                $transfer = $this->updateTransferAmount($transfer, $payment);

                $oldTransfer = clone $transfer;

                $transfer = Tracer::inSpan(['name' => 'transfer.process.create_transfer_transaction'], function() use ($oldTransfer)
                {
                    return (new Core())->createTransactionForTransfer($oldTransfer);
                });

                $transferPayment = $this->createTransferredEntity($transfer, $payment);

                $transfer->setProcessed();

                $transfer->setErrorCode(null);

                $this->setSettlementStatus($transfer);

                $transfer->incrementAttempts();

                $totalTransferAmount = $transfer->getAmount();

                $this->updatePaymentAmountTransferred($payment, $totalTransferAmount);

                $this->repo->saveOrFail($transfer);

                (new Core())->createLedgerEntriesForTransfer($transferPayment, $transfer->merchant);

                return $transfer;
            }, $deadlockRetryAttempts);

            (new Metric())->pushTransferProcessSuccessMetrics();

            $this->fireTransferProcessedWebhookIfApplicable($transfer);
        }
        catch (\Exception $ex)
        {
            (new Metric())->pushTransferProcessFailedMetrics($ex);

            throw  $ex;
        }
    }

    protected function updateTransferAmount($transfer, $payment)
    {
        if ($transfer->isBalanceTransfer() === true)
        {
            $transfer->setAmount($payment->getAmount() - $payment->getFee());

            $transfer->saveOrFail();
        }

        return $transfer;
    }

    protected function createTransferredEntity($transfer, $payment)
    {
        if ($transfer->isBalanceTransfer() === true)
        {
            $type = $transfer->getNotes()[Merchant\Credits\Entity::TYPE];

            if($type === Merchant\BALANCE\Type::RESERVE_BALANCE)
            {
                $this->createTransferredBalance($transfer);
            }
            else
            {
                $this->createTransferredCredits($transfer, $type);
            }

            return null;
        }
        else
        {
            return Tracer::inSpan(['name' => 'transfer.process.create_transfer_payment'], function() use ($transfer, $payment)
            {
                return $this->createTransferredPayment($transfer, $payment);
            });
        }
    }

    /**
     * @throws BadRequestException
     */
    protected function createTransferredPayment($transfer, $payment): Payment\Entity
    {
        $parentMerchant = (new Core())->fetchAccountParentMerchant($this->merchant, $payment->getPublicKey() ?? null, $payment);

        $to = $this->repo->account->findByIdAndMerchant($transfer->getToId(), $parentMerchant);

        $input = $this->getTransferData($transfer);

        $transferPayment = (new Payment\Processor\Processor($to))->processTransfer($input, $payment);

        $transferPayment->transfer()->associate($transfer);

        $this->repo->saveOrFail($transferPayment);

        return $transferPayment;
    }

    protected function createTransferredCredits($transfer, $type)
    {
        $transferType = NULL;
        if ($type === Merchant\BALANCE\Type::FEE_CREDIT)
        {
            $transferType = Merchant\Credits\Type::FEE;
        }
        else if ($type === Merchant\BALANCE\Type::REFUND_CREDIT)
        {
            $transferType = Merchant\Credits\Type::REFUND;
            if ($this->merchant->refund_source !== CONSTANTS\Entity::CREDITS)
            {
                $merchant_core = new Merchant\Core();
                $merchant_core->edit($this->merchant, ['refund_source' => 'credits']);
            }
        }
        $input =
            [
                Merchant\Credits\Entity::VALUE    => $transfer->amount,
                Merchant\Credits\Entity::TYPE     => $transferType,
                Merchant\Credits\Entity::CAMPAIGN => $transfer->notes['description'],
            ];

        (new Merchant\Credits\Core)->create($this->merchant, $input);
    }

    protected function createTransferredBalance($transfer)
    {
        $input =
            [
                'amount'      => $transfer->amount,
                'type'        => Merchant\BALANCE\TYPE::RESERVE_PRIMARY,
                'currency'    => $transfer->getCurrency(),
                'description' => $transfer->notes['description']
            ];

        (new Adjustment\Core)->createAdjustment($input, $this->merchant);
    }

    private function getTransferData(Entity $transfer)
    {
        $notes = [];

        if (empty($transfer->getNotes()) === false)
        {
            $notes = $transfer->getNotes()->toArray();
        }

        $input = [
            ToType::ACCOUNT              => $transfer->getToId(),
            Entity::AMOUNT               => $transfer->getAmount(),
            Entity::CURRENCY             => $transfer->getCurrency(),
            Entity::ON_HOLD              => $transfer->getOnHold(),
            Entity::ON_HOLD_UNTIL        => $transfer->getOnHoldUntil(),
            Entity::NOTES                => $notes,
            Entity::LINKED_ACCOUNT_NOTES => $transfer->getLinkedAccountNotes(),
        ];

        $laNotes = $this->getLinkedAccountNotes($input);

        $input[Entity::NOTES] = $laNotes;

        return $input;
    }

    private function getLinkedAccountNotes(array $input): array
    {
        $transferNotes = $input[Entity::NOTES] ?? [];

        $laNotesKeys = $input[Entity::LINKED_ACCOUNT_NOTES] ?? [];

        $laNotes = [];

        if ((empty($laNotesKeys) === false) and (is_array($laNotesKeys) === true))
        {
            $laNotes = array_only($transferNotes, $laNotesKeys);

            (new Validator)->validateLinkedAccountNotes($laNotes, $laNotesKeys);
        }

        return $laNotes;
    }

    private function updatePaymentAmountTransferred(Payment\Entity $payment, int $amount)
    {
        $this->repo->payment->lockForUpdateAndReload($payment);

        $this->trace->info(
            TraceCode::PAYMENT_UPDATE_AMOUNT_TRANSFERRED,
            [
                'payment_id'    => $payment->getId(),
                'amount'        => $amount,
            ]);

        $payment->transferAmount($amount);

        $this->repo->saveOrFail($payment);
    }

    protected function verifyAndSetErrorCode(Entity $transfer, string $errorCode)
    {
        if (ErrorCodeMapping::isErrorCodePublic($errorCode) === true)
        {
            $transfer->setErrorCode($errorCode);

            return;
        }

        $transfer->setErrorCode(ErrorCode::BAD_REQUEST_ERROR);
    }

    protected function setSettlementStatus(Entity $transfer)
    {
        $transfer->setSettlementStatus(SettlementStatus::PENDING);

        if ($transfer->getOnHold() === true)
        {
            $transfer->setSettlementStatus(SettlementStatus::ON_HOLD);
        }
    }

    protected function fireTransferProcessedWebhookIfApplicable(Entity $transfer)
    {
        if ($transfer->isProcessed() === true)
        {
            (new Core())->eventTransferProcessed($transfer);
        }
    }

    protected function fireTransferFailedWebhookIfApplicable(Entity $transfer)
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

    protected function isErrorDueToDBLostConnection(Throwable $e)
    {
        // This method checks for 'Lock wait timeout exceeded' error in addition to the different
        // errors that are checked by the causedByLostConnection method.
        $message = $e->getMessage();

        if (Str::contains($message, ['Lock wait timeout exceeded',
                                     'Deadlock found when trying to get lock']) === true)
        {
            return true;
        }

        return $this->causedByLostConnection($e);
    }

    protected function failTransferIfLinkedAccountIsSuspended(Entity $transfer, Merchant\Entity $merchant): bool
    {
        try
        {
            (new Core())->validateLinkedAccountActivationStatusAndBankVerificationStatus($transfer, $merchant);
        }
        catch (BadRequestException $ex)
        {
            if ($ex->getError()->getInternalErrorCode() === ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_SUSPENDED)
            {
                $transfer->setFailed();

                $transfer->setMessage($ex->getMessage());

                $this->verifyAndSetErrorCode($transfer, $ex->getCode());

                $this->repo->saveOrFail($transfer);

                $this->fireTransferFailedWebhookIfApplicable($transfer);

                $this->trace->info(
                    TraceCode::TRANSFER_FAILED_AS_LINKED_ACCOUNT_IS_SUSPENDED,
                    [
                        'transfer_id'         => $transfer->getId(),
                        'linked_account_id'   => $transfer->getToId(),
                    ]);

                return true;
            }
        }

        return false;
    }

    protected function fetchTransferProcessMutexConfig()
    {
        $config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::TRANSFER_PROCESSING_MUTEX_CONFIG]);

        return $config;
    }
}
