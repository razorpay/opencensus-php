<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class MerchantInvoiceExecutionReport extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSubject()
    {
        $this->subject('Merchant Invoice Execution Summary');

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::MERCHANT_INVOICE_EXECUTION_SUMMARY;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to(Constants::MAIL_ADDRESSES[Constants::FINANCE])
             ->cc(Constants::MAIL_ADDRESSES[Constants::TECH_SETTLEMENTS]);

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['attachment']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.merchant_invoice_execution_summary');

        return $this;
    }
}