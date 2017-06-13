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

    protected function getSubjectTemplate()
    {
        $type =  $this->data['invoice']['type'];

        return self::SUBJECT_TEMPLATES[$type];
    }
}
