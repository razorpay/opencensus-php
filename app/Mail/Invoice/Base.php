<?php

namespace RZP\Mail\Invoice;

use Config;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Exception;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Invoice\Type;
use RZP\Models\Invoice\ViewDataSerializer;

class Base extends Mailable
{
    const MAIL_TAG_MAP = [
        Type::ECOD    => MailTags::ECOD,
        Type::INVOICE => MailTags::INVOICE,
    ];

    protected $invoice;

    protected $event;

    public function __construct(InvoiceEntity $invoice)
    {
        $this->invoice = $invoice;

        $this->invoiceData = (new ViewDataSerializer($this->invoice))->get();
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

        if (in_array($this->event, array_keys(Event::MAIL_SUBJECT_TEMPLATES), true) === false)
        {
            throw new Exception\LogicException("No templates found for event: $this->event");
        }

        $type = $this->invoice->getType();

        $subject = sprintf(Event::MAIL_SUBJECT_TEMPLATES[$this->event][$type], $merchantName);

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
        $id = $this->invoice->getPublicId();

        $invoiceDashboardPath = $this->invoice->getDashboardPath();

        $dashboardUrl = Config::get('applications.dashboard.url');

        $extraInvoicePayload = [
            'type_label'    => ucwords($this->invoice->getTypeLabel()),
            'pdf_url'       => url("v1/invoices/$id/pdf"),
            'dashboard_url' => $dashboardUrl . $invoiceDashboardPath,
        ];

        $this->invoiceData['invoice'] += $extraInvoicePayload;

        $this->with($this->invoiceData);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $invoiceType = $this->invoice->getType();

            $label = self::MAIL_TAG_MAP[$invoiceType] ?? MailTags::INVOICE;

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->invoice->getPublicId());

            $headers->addTextHeader(MailTags::HEADER, $label);
        });

        return $this;
    }
}
