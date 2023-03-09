<?php

namespace RZP\Jobs\UpsRecon;

use App;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Validator;
use Razorpay\Trace\Logger as Trace;

// UpsGatewayEntityUpdate job calls payment-upi
// for post recon gateway entity updates
class UpsGatewayEntityUpdate extends Job
{
    protected $mode;

    protected $data;

    // $queueConfigKey is resolved in the job for queue name to push the messages
    protected $queueConfigKey  = 'payments_upi_recon_entity_update';

    const MAX_RETRY_ATTEMPT    = 5;

    const JOB_RELEASE_WAIT     = 120;

    const GATEWAY              = 'gateway';

    const PAYMENT_ID           = 'payment_id';

    const GATEWAY_DATA         = 'gateway_data';

    const JOB_DELETED          = 'job_deleted';

    const JOB_RELEASED         = 'job_released';

    public function __construct(string $mode,
                                array $data)
    {
        parent::__construct($mode);

        $this->mode = $mode;

        $this->data = $data;
    }

    /**
     * handle() function is fired
     * when job is dispatched to queue
     */
    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();

        try
        {
            // Validates the message body before calling ups
            (new Validator)->validateUpsGatewayEntityUpdate($this->data);

            $this->trace->info(
                TraceCode::UPI_PAYMENT_RECON_ENTITY_UPDATE_STARTED,
                [
                    'gatewayData' => $this->data[self::GATEWAY_DATA],
                    'paymentId'   => $this->data[self::PAYMENT_ID],
                    'gateway'     => $this->data[self::GATEWAY],
                ]
            );

            $app['upi.payments']->updateReconGatewayData($this->data);

            $this->trace->info(
                TraceCode::UPI_PAYMENT_RECON_ENTITY_UPDATE_SUCCESS,
                [
                    'paymentId' => $this->data[self::PAYMENT_ID],
                    'gateway'   => $this->data[self::GATEWAY],
                ]
            );

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::UPI_PAYMENT_RECON_ENTITY_UPDATE_FAILED,
                [
                    'paymentId' => $this->data[self::PAYMENT_ID],
                    'gateway'   => $this->data[self::GATEWAY],
                ]
            );

            $this->handleRefundJobRelease($this->data[self::PAYMENT_ID]);
        }
    }

    /** Retry the failed messages and delete the messages
     * @param string $paymentId
     */
    protected function handleRefundJobRelease(string $paymentId)
    {
        try
        {
            $this->trace->info(
                TraceCode::UPI_PAYMENT_RECON_UPDATE_RETRY,
                [
                    'paymentId' => $paymentId,
                    'attempts'  => $this->attempts(),
                ]
            );

            if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
            {
                $this->trace->error(
                    TraceCode::UPI_PAYMENT_RECON_UPDATE_FAILED_AFTER_RETRIES,
                    [
                        'job_attempts' => $this->attempts(),
                        'paymentId'    => $paymentId,
                        'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                    ]);

                $this->delete();
            }
            else
            {
                $this->release(self::JOB_RELEASE_WAIT);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPI_PAYMENT_RECON_UPDATE_RETRY_FAILED);

            $this->delete();
        }
    }
}
