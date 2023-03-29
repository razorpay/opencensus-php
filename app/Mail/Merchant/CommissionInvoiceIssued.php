<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Models\Partner\Commission;

class CommissionInvoiceIssued extends Mailable
{
    protected $data;
    protected $countryCode;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;

        $this->countryCode = $data['country_code'];
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES_GLOBAL[$this->countryCode][Constants::PARTNER_COMMISSIONS],Constants::HEADERS_GLOBAL[$this->countryCode][Constants::PARTNER_COMMISSIONS]);

        return $this;
    }

    protected function addRecipients()
    {
        $email = $this->data['merchant']['email'];

        $name = $this->data['merchant']['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addSubject()
    {
        $subject = '<Important> Commission generated for the date range: '. $this->data['start_date'].' to '. $this->data['end_date'];

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::COMMISSION_INVOICE);
        });

        return $this;
    }

    protected function addAttachments()
    {
        $outputFileLocalPath = $this->data['file_path'];

        if ($outputFileLocalPath !== null)
        {
            $this->attach($outputFileLocalPath);
        }

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->data['view']);

        return $this;
    }
}
