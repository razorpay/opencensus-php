<?php

namespace RZP\Mail\Invoice\Payment;

use RZP\Mail\Payment\Base;
use RZP\Mail\Base\Common;
use RZP\Models\Invoice;

class Captured extends Base
{
    protected $invoice;

    public function setInvoice(Invoice\Entity $invoice)
    {
        $this->invoice = $invoice;
    }

    protected function getAction()
    {
        $typeLabel = $this->invoice->getTypeLabel();

        $action = ucwords($typeLabel) .'\'s Payment';

        return $action;
    }

    protected function addMailData()
    {
        $invoiceData = $this->getInvoiceData();

        $this->data['invoice'] = $invoiceData['invoice'];

        $this->data['merchant'] += $invoiceData['merchant'];

        $this->with($this->data);

        return $this;
    }

    protected function getInvoiceData()
    {
        $invoiceData = (new ViewDataSerializer($this->invoice))->get();

        $id = $this->invoice->getPublicId();

        $invoiceDashboardPath = $this->invoice->getDashboardPath();

        $dashboardUrl = Config::get('applications.dashboard.url');

        $extraInvoicePayload = [
            'type_label'    => ucwords($this->invoice->getTypeLabel()),
            'pdf_url'       => url("v1/invoices/$id/pdf"),
            'dashboard_url' => $dashboardUrl . $invoiceDashboardPath,
        ];

        $invoiceData['invoice'] += $extraInvoicePayload;

        return $invoiceData;
    }

    protected function getMailTag()
    {
        return MailTags::INVOICE;
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.merchant.captured');

        return $this;
    }
}
