<?php

namespace RZP\Mail\BankingAccount\Reports;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class LeadMisReport extends Base
{
    const SUBJECT = "Lead MIS Report from Razorpay";

    public $timeout = 300;

    /** @var array $requestedByUser */
    protected $requestedByUser;

    /**
     * @param array $reportData
     */
    public function __construct(array $reportData, array $requestedByUser)
    {
        parent::__construct($reportData);

        $this->requestedByUser = $requestedByUser;
    }

    protected function addRecipients()
    {
        $user = $this->requestedByUser;

        $this->to($user['email'], $user['name']);

        return $this;
    }

    protected function addMailData()
    {
        $data = $this->reportData;

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
       $this->view('emails.banking_account.lead_mis_report');
    
       return $this;
    }

    protected function addAttachments()
    {
        try {

            if (empty($this->reportData['attachments']))
            {
                return $this;
            }

            foreach ($this->reportData['attachments'] as $attachment)
            {
                $this->attach($attachment['file_path'], [
                    'as' => $attachment['file_name'],
                    'mime-type' => $attachment['mime_type'],
                ]);
            }
        }
        catch (\Throwable $e)
        {
            app('trace')->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_ERROR, [
                    'attachments' => $attachment
                ]);
        }

        return $this;
    }

    protected function addCc()
    {
        // For testing only
        $this->cc('umakant.vashishtha@razorpay.com', 'Umakant Vashishtha');
        // $this->cc('apurva.ankleshwaria@razorpay.com', 'Apurva Ankleshwaria');

        return $this;
    }

}
