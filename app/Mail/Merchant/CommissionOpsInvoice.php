<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Partner\Commission\Invoice;

class CommissionOpsInvoice extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $this->to(Constants::MAIL_ADDRESSES[Constants::PARTNER_PAYMENTS], Constants::HEADERS[Constants::PARTNER_PAYMENTS]);

        $this->cc(Constants::MAIL_ADDRESSES[Constants::PARTNER_OPS], Constants::HEADERS[Constants::PARTNER_OPS]);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addSubject()
    {
        $status = 'Processed';
        $mId = $this->data['merchant']['id'];
        $name = $this->data['merchant']['name'];

        $subject = 'Commission Payout Invoice '. $status. ' for '. $mId. ':'. $name. ':'. $this->data['start_date'].' to '. $this->data['end_date'];

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
        $this->view('emails.mjml.merchant.partner.commission_invoice.ops_processed');
        return $this;
    }
}
