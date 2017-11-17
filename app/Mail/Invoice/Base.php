<?php

namespace RZP\Mail\Invoice;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Invoice\Type;

class Base extends Mailable
{
    /**
     * Overridden in child classes(specific mail types), holds templates
     * per type.
     *
     * @var array
     */
    const SUBJECT_TEMPLATES = [
        Type::LINK    => '',
        Type::ECOD    => '',
        Type::INVOICE => '',
    ];

    const MAIL_TAG_MAP = [
        Type::LINK    => MailTags::LINK,
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
        if (isset($this->data['invoice']['customer_details']) === true)
        {
            $customerEmail = $this->data['invoice']['customer_details']['email'];

            $this->to($customerEmail);
        }

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubjectByInvoiceType();

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

    /**
     * Returns subject to use for mails based on invoice's type.
     * The subject templates for mails per type has different placeholders.
     *
     * @return string
     */
    protected function getSubjectByInvoiceType(): string
    {
        $type = $this->data['invoice']['type'];

        $template = static::SUBJECT_TEMPLATES[$type];

        if ($type === Type::INVOICE)
        {
            $args = [
                $this->data['merchant']['name'],
            ];
        }
        else
        {
            $args = [
                $this->data['invoice']['amount_formatted'],
            ];
        }

        return sprintf($template, ...$args);
    }
}
