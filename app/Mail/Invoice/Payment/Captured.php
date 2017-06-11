<?php

namespace RZP\Mail\Invoice\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Payment\Base;
use RZP\Models\Invoice;
use RZP\Models\Invoice\Type;

/**
 * We are extending Mail\Payment\Base class here instead of Invoice|base
 * as this mailable requires some payment related data
 */
class Captured extends Base
{
    protected $invoiceData;

    public function setInvoiceDetails(array $invoiceData)
    {
        $this->invoiceData = $invoiceData;
    }

    protected function getAction()
    {
        $typeLabel = $this->invoiceData['invoice']['type_label'];

        $action = ucwords($typeLabel) .'\'s Payment';

        return $action;
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

    protected function addMailData()
    {
        $this->data['invoice'] = $this->invoiceData['invoice'];

        $this->data['merchant'] += $this->invoiceData['merchant'];

        $this->with($this->data);

        return $this;
    }
}
