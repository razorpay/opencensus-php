<?php

namespace RZP\Jobs\BankingAccount;

use RZP\Mail\Facade as Mail;
use RZP\Jobs\Job;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Mail\BankingAccount\Reports\LeadMisReport;
use RZP\Models\BankingAccount as BankingAccountModel;

class BankingAccountRblMisReport extends Job
{
    const MAX_RETRY_ATTEMPT = 1; // setting this to 1 to avoid repeated jobs

    const RETRY_INTERVAL = 300;

    // increasing code level timeout
    public $timeout = 600;

    protected $metricsEnabled = true;

    // protected $queueConfigKey = 'banking_account_rbl_mis_report';

    /** @var array $input */
    protected $input;

    /** @var array $maker */
    protected $maker;

    public function __construct(string $mode, array $input, array $maker)
    {
        parent::__construct($mode);

        $this->input = $input;

        $this->maker = $maker;
    }

    /**
     * @throws \Throwable
     */
    public function handle()
    {

        parent::handle();

        $startTime = microtime(true);

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode' => $this->mode,
            'input' => $this->input,
            'start_time' => $startTime,
        ];

        $this->trace->info(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB, $tracePayload);

        // To remove the job if the job gets repeated again
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_DELETE, [
                'job_attempts'      => $this->attempts(),
                'message'           => 'Fail Safe: Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();

            return;
        }

        $bankLmsSerice = new BankingAccountModel\BankLms\Service();

        $bankLmsSerice->setPartnerMerchantBasicAuth();

        try
        {
            /** @var string $filePath */
            [$filePath, $signedUrlResponse] = $bankLmsSerice->sendActivationMisReport($this->input);

            $reportData = [
                'download_report_url' => $signedUrlResponse['signed_url'],
            ];

            $this->trace->info(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_S3_SUCCESS, array_merge($tracePayload, 
            [
                'attempts'              => $this->attempts(),
                'filePath'              => $filePath,
                'signed_url_response'   => $signedUrlResponse,
            ]));

            $leadMisReportMail = new LeadMisReport($reportData, $this->maker);

            Mail::send($leadMisReportMail);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_SUCCESS, array_merge($tracePayload, 
            [
                'attempts'            => $this->attempts(),
                'filePath'            => $filePath,
                'signedUrlResponse'   => $signedUrlResponse,
            ]));

            $this->delete();

            $this->trace->error(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_DELETE, [
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleted the job after successful attempt.'
            ]);
        }
        catch (\Throwable $e) {

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_ERROR,
                array_merge($tracePayload, 
                    [
                        'attempts'          => $this->attempts(),
                    ])
                );

            $this->checkRetry($e);
        }
        finally
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_DURATION, array_merge($tracePayload, [
                'attempts'          => $this->attempts(),
                'filePath'          => $filePath,
                'signedUrlResponse' => $signedUrlResponse,
                'duration'          => (microtime(true) - $startTime) * 1000,
            ]));
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_DELETE, [
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();

            $this->trace->count(BankingAccountModel\Metrics::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_TOTAL);
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
