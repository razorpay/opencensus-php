<?php

namespace RZP\Mail\Dispute\Admin;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;

class AcceptedAdmin extends Base
{
    protected function addSubject()
    {
        $subject = 'Merchant has accepted the dispute ('. $this->data['dispute']['id'] . ')';

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.dispute.accepted_admin');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $disputeId = $this->data['dispute']['id'];

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DISPUTE_ACCEPTED_ADMIN);

            $headers->addTextHeader(MailTags::HEADER, $disputeId);
        });

        return $this;
    }
}
