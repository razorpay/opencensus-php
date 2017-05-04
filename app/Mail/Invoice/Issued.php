<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Type;

class Issued extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Payment requested by %s',
        Type::ECOD    => ' Payment requested by %s',
        Type::INVOICE => ' Invoice from %s',
    ];

    protected $fileData;

    public function __construct(array $invoice, array $invoiceData, array $fileData = null)
    {
        parent::__construct($invoice, $invoiceData);

        $this->fileData = $fileData;
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.notification');

        return $this;
    }

    protected function addAttachments()
    {
        if ($this->fileData !== null)
        {
            $pdfDisplayName = $this->fileData['name'];

            $this->attach(
                $this->fileData['path'],
                ['as' => $pdfDisplayName, 'mime' => 'application/pdf']);
        }

        return $this;
    }

    protected function getSubjectTemplate()
    {
        $type =  $this->invoice['type'];

        return self::SUBJECT_TEMPLATES[$type];
    }
}
