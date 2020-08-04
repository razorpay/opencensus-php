<?php

namespace RZP\Jobs;

use Mail;
use RZP\Trace\TraceCode;
use RZP\Models\D2cBureauReport;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\D2CReport\D2cReportGenerated;
use RZP\Models\D2cBureauReport\Processor\CreateCsvReport;

class D2cCsvReportCreate extends Job
{
    protected $mode;

    /** @var D2cBureauReport\Entity $report */
    protected $report;

    const MAX_ATTEMPTS = 3;

    public function __construct($mode, $report)
    {
        parent::__construct($mode);

        $this->mode = $mode;

        $this->report = $report;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::D2C_BUREAU_CREATE_CSV_REPORT, [
            'merchant_id'   => $this->report['merchant_id'],
        ]);

        try
        {
            if (empty($this->report->getUfhFileId()) === false)
            {
                $processor = new CreateCsvReport($this->report);

                $processor->createCsvReport();

                $this->notify();
            }
            else
            {
                $this->trace->info(TraceCode::NO_D2C_REPORT_FOUND_TO_CREATE_CSV_REPORT, [
                    'merchant_id'   => $this->report['merchant_id'],
                ]);
            }

            $this->delete();
        }
        catch(\Exception $e)
        {
            if ($this->attempts() <= self::MAX_ATTEMPTS)
            {
                $this->release(1);
            }
            else
            {
                $this->delete();
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::D2C_BUREAU_REPORT_CONVERSION_ERROR,
                [
                    'merchant_id'    => $this->report['merchant_id'],
                ]
            );
        }
    }

    public function notify()
    {
        $mailData = [
            'merchant_id'      => $this->report['merchant_id'],
            'report_id'        => $this->report->getId(),
        ];

        $mailObj = new D2cReportGenerated($mailData);

        Mail::queue($mailObj);
    }
}
