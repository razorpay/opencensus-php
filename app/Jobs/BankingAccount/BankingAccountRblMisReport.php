<?php

namespace RZP\Jobs\BankingAccount;

use RZP\Mail\Facade as Mail;
use RZP\Jobs\Job;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\BankingAccount\Reports\LeadMisReport;
use RZP\Models\BankingAccount as BankingAccountModel;
use RZP\Trace\TraceCode;

class BankingAccountRblMisReport extends Job
{
    const MAX_RETRY_ATTEMPT = 2;

    const RETRY_INTERVAL = 300;

    public $timeout = 300;

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

        $bankLmsSerice = new BankingAccountModel\BankLms\Service();

        try
        {
            /** @var string $filePath */
            $filePath = $bankLmsSerice->sendActivationMisReport($this->input);

            $reportData = [
                'download_report_url' => '',
                'attachments' => [
                    [
                        'file_path' => $filePath,
                        'file_name' => last(explode('/', $filePath)),
                        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]
                ]
            ];

            $leadMisReportMail = new LeadMisReport($reportData, $this->maker);

            Mail::queue($leadMisReportMail);
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

            $this->checkRetry();
        }
        finally
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB, array_merge($tracePayload, [
                'duration'    => (microtime(true) - $startTime) * 1000,
                'filePath'    => $filePath,
            ]));
        }
    }

    protected function checkRetry()
    {
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
