<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\Constants;

class CardsPaymentRecon extends Job
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
            'fields'        => Constants::CPS_PARAMS,
            'payment_ids'   => [$this->data['payment_id']],
        ];

        $this->trace->info(
            TraceCode::PAYMENT_RECON_QUEUE_CPS_REQUEST,
            $request
        );

        $batchId = $this->data['batch_id'] ?? null;

        try
        {
            $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'     => InfoCode::CPS_RESPONSE_AUTHORIZATION_DATA,
                    'response'      => $response,
                ]);

            if (empty($response) === false)
            {
                (new Service)->persistGatewayDataAfterCpsReconResponse($response, $this->data);
            }

            $this->trace->info(
                TraceCode::PAYMENT_RECON_QUEUE_CPS_SUCCESS,
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
                    'info_code' => InfoCode::PAYMENT_RECON_CPS_JOB_FAILURE_EXCEPTION,
                    'request'   => $request,
                    'input'     => $this->data,
                ]);

            $this->handleRefundJobRelease($batchId);
        }
    }

    protected function handleRefundJobRelease(string $batchId = null)
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::PAYMENT_RECON_CPS_QUEUE_DELETE,
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
