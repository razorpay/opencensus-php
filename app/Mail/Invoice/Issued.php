<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Type;

class Issued extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Requesting payment of Rs. %s (via Razorpay)',
        Type::ECOD    => ' Requesting payment of Rs. %s (via Razorpay)',
        Type::INVOICE => ' Invoice from %s',
    ];

    protected $fileData;

    public function __construct(array $data, array $fileData = null)
    {
        parent::__construct($data);

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

            if ($this->fileData['path'] !== null)
            {
                $this->attach(
                    $this->fileData['path'],
                    ['as' => $pdfDisplayName, 'mime' => 'application/pdf']);
            }

        }

        return $this;
    }
}
