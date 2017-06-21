<?php

namespace RZP\Mail\Payment;

class Captured extends Base
{
    protected function addHtmlView()
    {
        $this->view('emails.payment.merchant');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.merchant_text');

        return $this;
    }
}
