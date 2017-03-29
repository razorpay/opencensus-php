<?php

namespace RZP\Mail\Invoice;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;
use RZP\Exception;
use RZP\Models\Invoice\Type;
use RZP\Models\Invoice\ViewDataSerializer;

class Base extends Mailable
{
    use Queueable, SerializesModels;

    const MAIL_TAG_MAP = [
        Type::ECOD    => MailTags::ECOD,
        Type::INVOICE => MailTags::INVOICE,
    ];

    protected $invoice;

    protected $mailSubjectTemplates;

    protected $event;

    public function __construct(Invoice\Entity $invoice)
    {
        $this->invoice = $invoice;

        $this->setMailSubjectTemplates();
    }

    public function build()
    {
        $data = $this->getData();

        $view = $this->getView();

        return $this->view($view)
                    ->with($data)
                    ->from('invoices@razorpay.com', $data['merchant']['name'])
                    ->replyTo('support@razorpay.com', 'Razorpay Support')
                    ->subject($data['subject'])
                    ->to($data['invoice']['customer']['email'])
                    ->attachFile()
                    ->withSwiftMessage(function ($message) use ($data)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, $data['invoice']['id']);

                        $headers->addTextHeader(MailTags::HEADER, $data['label']);
                    });
    }

    protected function attachFile()
    {
        return $this;
    }

    protected function getView()
    {
        ;
    }

    protected function getData()
    {
        $id = $this->invoice->getPublicId();

        $data = (new ViewDataSerializer($this->invoice))->get();

        $invoiceDashboardPath = $this->invoice->getDashboardPath();

        $extraInvoicePayload = [
            'type_label'    => ucwords($this->invoice->getTypeLabel()),
            'pdf_url'       => url("v1/invoices/$id/pdf"),
            'dashboard_url' => $this->dashboardUrl . $invoiceDashboardPath,
        ];

        $data['invoice'] += $extraInvoicePayload;

        $label = $this->getLabel($this->invoice->getType());
        $data['label'] = $label;

        //
        // In one of the case callee is null - getInvoicePaidMailPayload.
        // That method is used from Notify.php's flow. And subject construction
        // is done there in this particular flow. We might(later) consider
        // moving invoice's payment notifications here too.
        //
        // if ($callee !== null)
        // {
        //     $subject = $this->getInvoiceMailSubject($callee, $data['merchant']['name']);

        //     $data['subject'] = $subject;
        // }

        $subject = $this->getSubject($this->event, $data['merchant']['name']);

        return $data;
    }

    protected function getSubject(string $event, string $merchantName)
    {
        if (in_array($event, array_keys($this->mailSubjectTemplates), true) === false)
        {
            throw new Exception\LogicException("No templates found for event: $event");
        }

        $type = $this->invoice->getType();

        return sprintf($this->mailSubjectTemplates[$event][$type], $merchantName);
    }

    protected function getLabel($type)
    {
        return self::MAIL_TAG_MAP[$type] ?? MailTags::INVOICE;
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
