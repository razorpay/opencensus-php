<?php

namespace RZP\Mail\Invoice;

use Config;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Exception;
use RZP\Models\Invoice\Type;

class Base extends Mailable
{
    use InvoiceData;

    const MAIL_TAG_MAP = [
        Type::ECOD    => MailTags::ECOD,
        Type::INVOICE => MailTags::INVOICE,
    ];

    protected $invoice;

    protected $invoiceData;

    public function __construct(array $invoice, array $invoiceData)
    {
        parent::__construct();

        $this->invoice = $invoice;

        $this->invoiceData = $invoiceData;

        $this->addExtraInvoicePayLoad();
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::INVOICES];

        $fromHeader = $this->invoiceData['merchant']['name'];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $customerEmail = $this->invoiceData['invoice']['customer']['email'];

        $this->to($customerEmail);

        return $this;
    }

    protected function addSubject()
    {
        $merchantName = $this->invoiceData['merchant']['name'];

        $type = $this->invoice['type'];

        $subjectTemplate = $this->getSubjectTemplate();

        $subject = sprintf($subjectTemplate, $merchantName);

        $this->subject($subject);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $header = Constants::HEADERS[Constants::SUPPORT];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->invoiceData);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $invoiceType = $this->invoice['type'];

            $label = self::MAIL_TAG_MAP[$invoiceType] ?? MailTags::INVOICE;

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->invoice['id']);

            $headers->addTextHeader(MailTags::HEADER, $label);
        });

        return $this;
    }
}
