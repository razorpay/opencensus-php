<?php

namespace RZP\Mail\Invoice\Payment;

use Config;
use RZP\Constants\MailTags;
use RZP\Mail\Invoice\InvoiceData;
use RZP\Mail\Payment\Base;
use RZP\Models\Invoice\Type;

/**
 * We are extending Mail\Payment\Base class here instead of Invoice|base
 * as this mailable requires some payment related data too
 */
class Authorized extends Base
{
    use InvoiceData;

    protected $invoice;

    protected $invoiceData;

    public function setInvoiceDetails(array $invoice, array $invoiceData)
    {
        $this->invoice = $invoice;

        $this->invoiceData = $invoiceData;
    }

    protected function getAction()
    {
        $typeLabel = Type::getLabel($this->invoice['type']);

        $action = ucwords($typeLabel) .'\'s Payment';

        return $action;
    }

    protected function getMailTag()
    {
        return MailTags::INVOICE;
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.notification');

        return $this;
    }
}
