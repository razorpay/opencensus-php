<?php

namespace RZP\Jobs;

use RZP\Constants\Mode;
use RZP\Models\Settlement\SlackNotification;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Lambda;
use Carbon\Carbon;

class MerchantFirsDocuments extends Job
{
    const MODE = 'mode';

    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 300;

    /**
     * @var string
     */
    protected $queueConfigKey = 'firs_document_process';

    /**
     * @var array
     */
    protected $request;

    protected $mode;

    /**
     * Default timeout value for a job is 60s. Changing it to 15 sec
     * @var integer
     */
    public $timeout = 15;

    public function __construct(array $payload)
    {
        $this->setMode($payload);

        parent::__construct($this->mode);

        $this->request = $this->getFirsDocumentData($payload);
    }

    public function handle()
    {
        try {
            parent::handle();

            $this->trace->info(TraceCode::FIRS_DOCUMENT_PROCESSOR_JOB_INIT,[
                'request'  => $this->request,
            ]);

            $response = (new Lambda\Service)->processLambdaFIRS($this->request, $this->mode);

            $this->trace->info(TraceCode::FIRS_DOCUMENT_PROCESSOR_JOB_COMPLETED,[
                'response' => $response,
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FIRS_DOCUMENT_PROCESSOR_JOB_FAILED,[
                    'request' => $this->request,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $this->release($workerRetryDelay);

            $this->trace->info(TraceCode::FIRS_DOCUMENT_PROCESSOR_JOB_RELEASED, [
                'request'               => $this->request,
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::FIRS_DOCUMENT_PROCESSOR_JOB_DELETED, [
                'request'           => $this->request,
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

        }
    }

    /**
     * Set mode for job.
     * @param $payload array
     *
     * @return void
     */
    protected function setMode(array $payload)
    {
        if (array_key_exists(self::MODE, $payload) === true)
        {
            $this->mode = $payload[self::MODE];
        }
        else
        {
            $this->mode = Mode::LIVE;
        }
    }

    protected function getFirsDocumentData(array $payload)
    {
        $key = $payload['Records'][0]['s3']['object']['key'];

        /* Sample Key For Different Gateway
            rbl     - rbl/FIRS/filename.pdf
            icici   - icici/FIRS/filename.pdf
            firstdata - firstdata/FIRS/filename.pdf
        */

        $gateway = explode('/',$key)[0];

        return [
            'source'  => 'lambda',
            'gateway' => $gateway,
            'region' => $payload['Records'][0]['awsRegion'],
            'key'    => $payload['Records'][0]['s3']['object']['key'],
            'bucket' => $payload['Records'][0]['s3']['bucket']['name'],
        ];
    }

}
