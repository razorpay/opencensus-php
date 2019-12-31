<?php

namespace RZP\Jobs\NbPlusRecon;

use App;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\InfoCode;

class NetbankingRecon extends Job
{
    const MAX_JOB_ATTEMPTS = 5;
    const JOB_RELEASE_WAIT = 300;

    protected $data;

    protected $queueConfigKey = 'reconciliation_batch';

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        $request = [
            'fields'        => $this->data['gateway_params'],
            'payment_ids'   => [$this->data['payment_id']],
        ];

        $this->trace->info(
            TraceCode::PAYMENT_RECON_QUEUE_NBPLUS_REQUEST,
            $request
        );

        $batchId = $this->data['batch_id'] ?? null;

        try
        {
            $response = App::getFacadeRoot()['nbplus.payments']->fetchNetbankingData($request);

            // TODO: look at redaction?
            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'     => InfoCode::NBPLUS_RESPONSE_DATA,
                    'response'      => $response,
                ]);

            if (empty($response) === false)
            {
                (new Service)->persistGatewayDataAfterNbPlusReconResponse($response, $this->data);
            }

            $this->trace->info(
                TraceCode::PAYMENT_RECON_QUEUE_NBPLUS_SUCCESS,
                $this->data
            );

            $this->delete();
        }
        catch (\Exception $ex)
        {
            $this->data['job_attempts'] = $this->attempts();

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code' => InfoCode::PAYMENT_RECON_NBPLUS_JOB_FAILURE_EXCEPTION,
                    'request'   => $request,
                    'input'     => $this->data,
                ]);

            $this->handleReconJobRelease($batchId);
        }
    }

    protected function handleReconJobRelease(string $batchId = null)
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::PAYMENT_RECON_NBPLUS_QUEUE_DELETE,
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
