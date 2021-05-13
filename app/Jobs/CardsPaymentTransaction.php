<?php

namespace RZP\Jobs;

use App;
use Queue;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\InfoCode;

class CardsPaymentTransaction extends Job
{
    const MAX_JOB_ATTEMPTS = 5;
    const JOB_RELEASE_WAIT = 300;

    protected $data;

    protected $queueConfigKey = 'recon_method_batch';

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        $request = [
            'payment_ids'   => [$this->data['payment_id']],
        ];

        $this->trace->info(
            TraceCode::PAYMENT_TRANSACTION_QUEUE_CPS_REQUEST,
            $request
        );

        $batchId = $this->data['batch_id'] ?? null;

        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        try
        {
            if ($this->validateData($this->data) === true)
            {
                // Using the same recon queue for pushing transaction data.
                $queueName = $this->app['config']->get('queue.payment_card_api_reconciliation.' . $this->mode);

                $this->app['queue']->connection('sqs')->pushRaw(json_encode($this->data), $queueName);

                $this->trace->info(
                    TraceCode::TRANSACTION_INFO,
                    [
                        'info_code'     => InfoCode::TRANSACTION_CPS_QUEUE_DISPATCH,
                        'queue'         => $queueName,
                        'payload'       => $this->data,
                        'payment_id'    => $this->data['payment_id'],
                        'batch_id'      => $batchId,
                    ]
                );

                $this->trace->info(
                    TraceCode::PAYMENT_TRANSACTION_QUEUE_CPS_SUCCESS,
                    $this->data
                );
            }
            else
            {
                $this->trace->info(
                    TraceCode::TRANSACTION_INFO,
                    [
                        'info_code' => InfoCode::TRANSACTION_CPS_QUEUE_DISPATCH_ERROR,
                        'payload'   => $this->data
                    ]
                );
            }

            $this->delete();
        }
        catch (\Exception $ex)
        {
            $this->data['job_attempts'] = $this->attempts();

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TRANSACTION_INFO_ALERT,
                [
                    'info_code' => InfoCode::PAYMENT_TRANSACTION_CPS_JOB_FAILURE_EXCEPTION,
                    'request'   => $request,
                    'input'     => $this->data,
                ]);

            $this->handleJobRelease($batchId);
        }
    }

    protected function validateData($data): bool
    {
        if ((isset($data['payment_id']) === false) or
            (isset($data['transaction']) === false) or
            (isset($data['entity_type']) === false or ($data['entity_type'] !== 'transaction')))
        {
            return false;
        }

        return true;
    }

    protected function handleJobRelease(string $batchId = null)
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::PAYMENT_TRANSACTION_CPS_QUEUE_DELETE,
                [
                    'data'         => $this->data,
                    'batch_id'     => $batchId,
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->delete();
        }
        else
        {
            //
            // When queue_driver is sync, there's no release
            // and hence it's as good as deleting the job.
            //
            $this->release(self::JOB_RELEASE_WAIT);
        }
    }
}
