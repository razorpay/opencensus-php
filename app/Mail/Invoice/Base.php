<?php

namespace RZP\Mail\Invoice;

use Config;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;
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

    protected $invoiceData;

    protected $mailSubjectTemplates;

    protected $event;

    public function __construct(InvoiceEntity $invoice)
    {
        $this->invoice = $invoice;

        $this->invoiceData = (new ViewDataSerializer($this->invoice))->get();

        $this->setMailSubjectTemplates();
    }

    protected function addSender()
    {
        $fromEmail = Common::MAIL_ADDRESSES[Common::INVOICES];

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

        if (in_array($this->event, array_keys($this->mailSubjectTemplates), true) === false)
        {
            throw new Exception\LogicException("No templates found for event: $this->event");
        }

        $type = $this->invoice->getType();

        $subject = sprintf($this->mailSubjectTemplates[$this->event][$type], $merchantName);

        $this->subject($subject);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Common::MAIL_ADDRESSES[Common::SUPPORT];

        $header = Common::FROM_HEADER[Common::SUPPORT];

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
            $label = self::MAIL_TAG_MAP[$this->invoice->getType()] ?? MailTags::INVOICE;

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->invoice->getPublicId());

            $headers->addTextHeader(MailTags::HEADER, $label);
        });

        return $this;
    }

    protected function setMailSubjectTemplates()
    {
        $this->mailSubjectTemplates = [
            Event::INVOICE_ISSUED => [
                Type::LINK    => ' Payment requested by %s',
                Type::ECOD    => ' Payment requested by %s',
                Type::INVOICE => ' Invoice from %s',
            ],
            Event::INVOICE_EXPIRED => [
                Type::LINK    => ' Payment requested from %s has expired',
                Type::ECOD    => ' Payment requested from %s has expired',
                Type::INVOICE => ' Invoice from %s has expired',
            ],
            Event::INVOICE_EXPIRING => [
                Type::LINK    => ' Payment request from %s is expiring',
                Type::ECOD    => ' Payment request from %s is expiring',
                Type::INVOICE => ' Invoice from %s is expiring',
            ],
        ];
    }
}
