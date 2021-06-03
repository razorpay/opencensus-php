<?php

namespace RZP\Models\Transfer;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Illuminate\Support\Facades\App;
use RZP\Listeners\ApiEventSubscriber;

abstract class AbstractTransfer
{
    protected $payment;

    const MUTEX_LOCK_TIMEOUT = 600;

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

    }

    public function processOrderTransfers(Payment\Entity $payment)
    {

        $this->merchant = $this->repo->merchant->findOrFail($payment->getMerchantId());

        $transferStatus = $this->status;

        $transfers = $this->repo
                          ->transfer
                          ->fetchBySourceTypeAndIdAndMerchant($this->transfermode,  $this->sourceId, $this->merchant , $transferStatus);

        $this->trace->info($this->tracecode,
            [
                'payment_id'   => $payment->getPublicId(),
                'source_id'     => $this->sourceId,
                'transfer_ids' => $transfers->getIds(),
                'transferMode' =>  $this->transfermode,
            ]);

            foreach ($transfers as $transfer)
            {
                try
                {
                  $this->processTransfers($payment, $transfer, $this->merchant);

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
                    $transfer->setFailed();

                    $transfer->setMessage($e->getMessage());

                    $transfer->incrementAttempts();

                    $this->repo->saveOrFail($transfer);

                    $this->fireTransferFailedWebhookIfApplicable($transfer);
                }
                finally
                {
                    (new Core())->trackTransferProcessingTime($transfer, $payment);
                }
            }

        return $transfers;
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

        try
        {
            $transfer = $this->repo->transaction(function () use ($payment, $transfer)
            {
                $oldTransfer = clone $transfer;

                $transfer = (new Core())->createTransactionForTransfer($oldTransfer);

                $to = $this->repo
                           ->account
                           ->findByIdAndMerchant($transfer->getToId(), $this->merchant);

                $input = $this->getTransferData($transfer);

                $transferPayment = (new Payment\Processor\Processor($to))->processTransfer($input, $payment);

                $transferPayment->transfer()->associate($transfer);

                $this->repo->saveOrFail($transferPayment);

                $transfer->setProcessed();

                $transfer->incrementAttempts();

                $totalTransferAmount = $transfer->getAmount();

                $this->updatePaymentAmountTransferred($payment, $totalTransferAmount);

                $this->repo->saveOrFail($transfer);

                return $transfer;
            });

            (new Metric())->pushTransferProcessSuccessMetrics();

            $this->fireTransferProcessedWebhookIfApplicable($transfer);
        }
        catch (\Exception $ex)
        {
            (new Metric())->pushTransferProcessFailedMetrics($ex);

            throw  $ex;
        }
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

    protected function fireTransferProcessedWebhookIfApplicable(Entity $transfer)
    {
        if ($transfer->isProcessed() === true)
        {
            (new Core())->eventTransferProcessed($transfer);
        }
    }

    protected function fireTransferFailedWebhookIfApplicable(Entity $transfer)
    {
        if ($transfer->merchant->hasTransferFailedWebhookFeature() === false)
        {
            return;
        }

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
}
