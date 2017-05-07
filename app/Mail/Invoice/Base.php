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
    const MAIL_TAG_MAP = [
        Type::ECOD    => MailTags::ECOD,
        Type::INVOICE => MailTags::INVOICE,
    ];

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::INVOICES];

        $fromHeader = $this->data['merchant']['name'];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $customerEmail = $this->data['invoice']['customer']['email'];

        $this->to($customerEmail);

        return $this;
    }

    protected function addSubject()
    {
        $merchantName = $this->data['merchant']['name'];

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
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $invoiceType = $this->data['invoice']['type'];

            $label = self::MAIL_TAG_MAP[$invoiceType] ?? MailTags::INVOICE;

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->data['invoice']['id']);

            $headers->addTextHeader(MailTags::HEADER, $label);
        });

        return $this;
    }
}
