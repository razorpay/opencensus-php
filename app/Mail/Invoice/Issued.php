<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice;

class Issued extends Base
{
    protected $issuedPdfPath;

    public function __construct(Invoice\Entity $invoice, $issuedPdfPath)
    {
        parent::__construct($invoice);

        $this->event = Event::INVOICE_ISSUED;

        $this->issuedPdfPath = $issuedPdfPath;
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.notification');

        return $this;
    }

    protected function addAttachments()
    {
        if ($this->issuedPdfPath !== null)
        {
            $pdfDisplayName = $this->invoice->getPdfDisplayName();

            $this->attach(
                $this->issuedPdfPath,
                ['as' => $pdfDisplayName, 'mime' => 'application/pdf']);
        }

        return $this;
    }
}
