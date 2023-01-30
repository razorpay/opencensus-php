<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;

class CommissionInvoiceAutoApproved extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::PARTNER_COMMISSIONS], Constants::HEADERS[Constants::PARTNER_COMMISSIONS]);

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
        $subject = 'Invoice for your commissions from ' . $this->data['start_date'] . ' to ' . $this->data['end_date'];

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::COMMISSION_INVOICE);
        });

        return $this;
    }

    protected function addAttachments()
    {
        $outputFileLocalPath = $this->data['file_path'];

        if ($outputFileLocalPath !== null) {
            $this->attach($outputFileLocalPath);
        }

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.mjml.merchant.partner.commission_invoice.commission_invoice_auto_approved');

        return $this;
    }
}
