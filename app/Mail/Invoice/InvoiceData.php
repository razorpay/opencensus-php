<?php

namespace RZP\Mail\Invoice;

use Config;
use RZP\Models\Invoice\Type;
use RZP\Models\Invoice\ViewDataSerializer;

trait InvoiceData
{
    protected function addExtraInvoicePayLoad()
    {
        // $invoiceData = (new ViewDataSerializer($this->invoice))->get();

        $id = $this->invoice['id'];

        $invoiceDashboardPath = $this->getDashboardPath();

        $dashboardUrl = Config::get('applications.dashboard.url');

        $extraInvoicePayload = [
            'type_label'    => ucwords(Type::getLabel($this->invoice['type'])),
            'pdf_url'       => url("v1/invoices/$id/pdf"),
            'dashboard_url' => $dashboardUrl . $invoiceDashboardPath,
        ];

        $this->invoiceData['invoice'] += $extraInvoicePayload;
    }

    protected function addMailData()
    {
        $this->addExtraInvoicePayLoad();

        $this->data['invoice'] = $this->invoiceData['invoice'];

        $this->data['merchant'] += $this->invoiceData['merchant'];

        $this->with($this->data);

        return $this;
    }

    /**
     * Returns the path component of Dashboard view url.
     *
     * For invoices (New):   #/app/invoices/{public-id}
     * Otherwise (Existing): #/app/invoices/{public-id}/details
     *
     * @return string
     */
    protected function getDashboardPath()
    {
        $path = '#/app/invoices/' . $this->invoice['id'];

        if ($this->invoice['type'] === Type::INVOICE)
        {
            $path .= '/details';
        }

        return $path;
    }
}
