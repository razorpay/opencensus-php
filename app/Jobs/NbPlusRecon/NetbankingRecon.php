<?php

namespace RZP\Jobs\NbPlusRecon;

use App;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Service;
use RZP\Services\NbPlus\Netbanking;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator\NbPlus\NbPlusServiceRecon;

class NetbankingRecon extends Job
{
    // Deprecated
    // we now compare and update within the nbplus service itself

    const MAX_JOB_ATTEMPTS = 2;
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
            'fields'      => $this->data['attributes'],
            'payment_ids' => [$this->data['payment_id']],
        ];

        $this->trace->info(
            TraceCode::PAYMENT_RECON_QUEUE_NBPLUS_REQUEST,
            $request
        );

        $batchId = $this->data['batch_id'] ?? null;

        try
        {
            $response = App::getFacadeRoot()['nbplus.payments']->fetchNbplusData($request, $this->data['entity']);

            if (empty($response) === false)
            {
                $response = $this->extractAdditionalDataIfApplicable($response);

                $redactedResponse = $this->redactResponse($response);

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'info_code' => InfoCode::NBPLUS_RESPONSE_DATA,
                        'response'  => $redactedResponse,
                    ]);

                (new Service)->persistGatewayDataAfterNbPlusReconResponse($response, $this->data, $this->data['entity'], $this->data['attributes'], NbPlusServiceRecon::RECON_PARAMS);
            }

            $this->trace->info(
                TraceCode::PAYMENT_RECON_QUEUE_NBPLUS_SUCCESS,
                [
                    'payment_id' => $this->data['payment_id'],
                    'mode'       => $this->data['mode'],
                    'gateway'    => $this->data['gateway'],
                    'batch_id'   => $this->data['batch_id']
                ]
            );

            $this->delete();
        }
        catch (\Exception $ex)
        {
            $this->data['job_attempts'] = $this->attempts();

            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::RECON_CRITICAL_ALERT,
                [
                    'info_code' => InfoCode::PAYMENT_RECON_NBPLUS_JOB_FAILURE_EXCEPTION,
                    'request'   => $request,
                    'mode'      => $this->data['mode'],
                    'gateway'   => $this->data['gateway'],
                    'batch_id'  => $this->data['batch_id']
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

    protected function extractAdditionalDataIfApplicable($response)
    {
        $paymentId = $this->data['payment_id'];

        if ((empty($response['items'][$paymentId]) === false) and
            (isset($response['items'][$paymentId][Netbanking::ADDITIONAL_DATA]) === true))
        {
            $additionalData = json_decode($response['items'][$paymentId][Netbanking::ADDITIONAL_DATA], true);

            foreach ($additionalData as $key => $value)
            {
                $response['items'][$paymentId][$key] = $value;
            }

            unset($response['items'][$paymentId][Netbanking::ADDITIONAL_DATA]);
        }

        return $response;
    }

    protected function redactResponse($response)
    {
        unset($response['items'][$this->data['payment_id']][Netbanking::CREDIT_ACCOUNT_NUMBER]);

        unset($response['items'][$this->data['payment_id']][Netbanking::BANK_ACCOUNT_NUMBER]);

        return $response;
    }
}
