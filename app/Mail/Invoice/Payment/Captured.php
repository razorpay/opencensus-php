<?php

namespace RZP\Mail\Invoice\Payment;

use Config;

use RZP\Constants\MailTags;
use RZP\Mail\Invoice\InvoiceData;
use RZP\Mail\Payment\Base;
use RZP\Models\Invoice;

/**
 * We are extending Mail\Payment\Base class here instead of Invoice|base
 * as this mailable requires some payment related dara too
 */
class Captured extends Base
{
    use InvoiceData;

    protected $invoice;

    public function setInvoice(Invoice\Entity $invoice)
    {
        $this->invoice = $invoice;
    }

    protected function getAction()
    {
        $typeLabel = $this->invoice->getTypeLabel();

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
}
