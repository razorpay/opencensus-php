<?php

namespace RZP\Mail\D2CReport;

use Symfony\Component\Mime\Email;

use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;

class D2cReportGenerated extends Mailable
{
    protected $reportId;

    protected $merchantId;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->reportId = $data['report_id'];

        $this->merchantId = $data['merchant_id'];
    }

    protected function addRecipients()
    {
        $toEmail = [Constants::MAIL_ADDRESSES[Constants::CAPITAL_CREDIT]];

        $this->to($toEmail);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::CAPITAL_CREDIT];

        $fromHeader =  'Experian CSV File';

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'body' => 'Merchant_id:' . $this->merchantId . ', Report_id:' . $this->reportId
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Merchant Experian report';

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::EXPERIAN_REPORT);
        });

        return $this;
    }
}
