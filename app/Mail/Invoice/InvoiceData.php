<?php

namespace RZP\Mail\Invoice;

use Config;

use RZP\Models\Invoice\ViewDataSerializer;

trait InvoiceData
{
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

    protected function addMailData()
    {
        $invoiceData = $this->getInvoiceData();

        $this->data['invoice'] = $invoiceData['invoice'];

        $this->data['merchant'] += $invoiceData['merchant'];

        $this->with($this->data);

        return $this;
    }
}
