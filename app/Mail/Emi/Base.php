<?php

namespace RZP\Mail\Emi;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class Base extends Mailable
{
    protected $emails;

    protected $bankName;

    public function __construct(string $bankName, array $emails)
    {
        parent::__construct();

        $this->bankName = $bankName;

        $this->emails = $emails;
    }

    protected function addRecipients()
    {
        $emails = array_merge($this->emails, ['settlements@razorpay.com']);

        $this->to($emails);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::EMI_FILE);
        });

        return $this;
    }
}
