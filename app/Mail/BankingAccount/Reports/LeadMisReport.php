<?php

namespace RZP\Mail\BankingAccount\Reports;

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

        $this->with(array_merge($data, [
            'download_report_url' => ''
        ]));

        return parent::addMailData();

        return $this;
    }

    protected function addHtmlView()
    {
       $this->view('emails.banking_account.lead_mis_report');
    
       return $this;
    }

    protected function addAttachments()
    {
        foreach ($this->reportData['attachments'] as $attachment)
        {
            $this->attach($attachment['file_path'], [
                'as' => $attachment['file_name'],
                'mime-type' => $attachment['mime_type'],
            ]);
        }

        return $this;
    }

    protected function addCc()
    {
        // For testing only
        $this->cc('umakant.vashishtha@razorpay.com', 'Umakant Vashishtha');
        $this->cc('apurva.ankleshwaria@razorpay.com', 'Apurva Ankleshwaria');

        return $this;
    }

}
