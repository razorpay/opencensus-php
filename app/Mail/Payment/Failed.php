<?php

namespace RZP\Mail\Payment;

class Failed extends Base
{
    protected function addSubject()
    {
        $label = $this->data['merchant']['billing_label'] ?? $this->data['payment']['amount'];

        $subject = "Razorpay | Payment failed for $label";

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.payment.merchant_failure');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.merchant_text');

        return $this;
    }
}
