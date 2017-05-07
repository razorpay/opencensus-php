<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Type;

class Expired extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Payment requested from %s has expired',
        Type::ECOD    => ' Payment requested from %s has expired',
        Type::INVOICE => ' Invoice from %s has expired',
    ];

    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.notification');

        return $this;
    }

    protected function getSubjectTemplate()
    {
        $type = $this->data['invoice']['type'];

        return self::SUBJECT_TEMPLATES[$type];
    }
}
