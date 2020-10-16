<?php

namespace RZP\Mail\Payment;

class Failed extends Base
{
    protected function addSubject()
    {
        $label = $this->data['merchant']['billing_label'] ?? $this->data['payment']['amount'];

        $subject = "Payment failed for $label";

        if ($this->isMerchantEmail === true)
        {
            $subject = "Razorpay | $subject";
        }
        
        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        if ($this->isMerchantEmail === true)
        {
            $this->view('emails.payment.merchant_failure');
        }
        else
        {
            $this->view('emails.mjml.customer.failure');
        }
        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.merchant_text');

        return $this;
    }
}
