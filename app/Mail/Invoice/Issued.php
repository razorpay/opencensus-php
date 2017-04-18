<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Invoice\Type;

class Issued extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Payment requested by %s',
        Type::ECOD    => ' Payment requested by %s',
        Type::INVOICE => ' Invoice from %s',
    ];

    protected $issuedPdfPath;

    public function __construct(InvoiceEntity $invoice, $issuedPdfPath)
    {
        parent::__construct($invoice);

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

    protected function getSubjectTemplate()
    {
        $type =  $this->invoice->getType();

        return self::SUBJECT_TEMPLATES[$type];
    }
}
