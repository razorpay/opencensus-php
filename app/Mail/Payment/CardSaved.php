<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;

class CardSaved extends Base
{
    protected function addSubject()
    {
        $subject = "Card successfully tokenized with Razorpay";

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.payment.cardsaving');

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::CARD_SAVING;
    }
}
