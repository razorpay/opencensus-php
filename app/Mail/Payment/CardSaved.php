<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;

class CardSaved extends Base
{
    protected function addSender()
    {
        $email = Common::MAIL_ADDRESSES[Common::CARE];

        $this->from($email);

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
