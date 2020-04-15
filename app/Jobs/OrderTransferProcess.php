<?php

namespace RZP\Jobs;

use RZP\Models\Payment;
use RZP\Models\Transfer;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Illuminate\Support\Facades\App;

class OrderTransferProcess extends Job
{
    const MUTEX_LOCK_TIMEOUT = 600;

    protected $mutex;

    protected $repo;

    protected $payment;

    protected $queueConfigKey = 'order_transfer';

    public function __construct(string $mode, Payment\Entity $payment)
    {
        parent::__construct($mode);

        $this->payment = $payment;
    }

    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();

        $this->mutex = $app['api.mutex'];

        $this->repo = $app['repo'];

        try
        {
            $this->mutex->acquireAndRelease(
                'order_transfer_process_'.$this->payment->getPublicId(),
                function()
                {
                    $this->repo->reload($this->payment);

                    (new Transfer\Core())->processOrderTransfers($this->payment);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ORDER_TRANSFER_PROCESS_IN_PROGRESS);

            $this->trace->info(
                TraceCode::ORDER_TRANSFER_PROCESS_SUCCESS,
                [
                    'payment_id' => $this->payment->getPublicId()
                ]
            );
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ORDER_TRANSFER_PROCESS_FAILURE,
                [
                    'payment_id' => $this->payment->getPublicId()
                ]
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
