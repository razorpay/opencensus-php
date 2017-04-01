<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Common;

class CardSaved extends Base
{
    protected function addSender()
    {
        $email = Common::MAIL_ADDRESSES[Common::CARE];

        $this->from($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = "Card successfully saved with Razorpay";

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
