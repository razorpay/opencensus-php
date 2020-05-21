<?php

namespace RZP\Models\Transfer;

use Illuminate\Support\Facades\App;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;

abstract class AbstractTransfer
{

    /**
     * AbstractTransfer constructor.
     */
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

        $transferStatus = [Status::PENDING,Status::FAILED];

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

        $this->processTransfers($payment,$transfers,$this->merchant);

        return $transfers;
    }

    public function processTransfers($payment ,$transfers,$merchant)
    {
        $this->merchant = $merchant;

        $this->repo->transaction(function() use ($payment, $transfers)
        {
            $totalTransferAmount = 0;

            foreach ($transfers as $transfer)
            {
                if ($transfer->isFailed() === true and
                    $transfer->getAttempts() >= Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS)
                {
                    $this->trace->info($this->invalidCode,
                        [
                            'payment_id'   => $payment->getPublicId(),
                            'transfer'     => $transfer->toArrayPublic(),
                            'transfermode' => $this->transfermode,
                        ]);
                    continue;
                }
                try
                {
                    $oldTransfer = clone $transfer;

                    $core = new Core();

                    $transfer = $core->createTransactionForTransfer($oldTransfer);

                    $to = $this->repo
                               ->account
                               ->findByIdAndMerchant($transfer->getToId(), $this->merchant);

                    $input = $this->getTransferData($transfer);

                    $transferPayment = (new Payment\Processor\Processor($to))->processTransfer($input, $payment);

                    $transferPayment->transfer()->associate($transfer);

                    $this->repo->saveOrFail($transferPayment);

                    $transfer->setProcessed();

                    $totalTransferAmount += $transfer->getAmount();

                    (new Metric())->pushTransferProcessSuccessMetrics();
                }
                catch (\Exception $e)
                {
                    $transfer->setFailed();

                    $transfer->setMessage($e->getMessage());

                    $this->trace->traceException(
                        $e,
                        null,
                        $this->failurecode,
                        [
                            'payment_id'   => $payment->getPublicId(),
                            'transfer'     => $transfer->toArrayPublic(),
                            'transfermode' => $this->transfermode,
                        ]
                    );

                    (new Metric())->pushTransferProcessFailedMetrics($e);
                }
                finally
                {
                    $transfer->incrementAttempts();

                    $this->repo->saveOrFail($transfer);

                    if ($transfer->isProcessed())
                    {
                        $this->repo->reload($transfer);

                        $this->eventOrderTransferProcessed($transfer);
                    }
                }
            }

            $this->updatePaymentAmountTransferred($payment, $totalTransferAmount);
        });
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

    private function eventOrderTransferProcessed(Entity $transfer)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $transfer
        ];

        $this->app['events']->fire('api.transfer.processed', $eventPayload);
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


}
