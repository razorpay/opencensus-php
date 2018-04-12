<?php

namespace RZP\Mail\OAuth;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class CompetitorAuthorized extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
//        $this->to('support@razorpay.com');
        $this->to('pratik.kapasi@razorpay.com');

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.oauth.competitor_authorized');

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Razorpay | Competitor access grant notification');

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::OAUTH_APP_AUTHORIZED);
        });

        return $this;
    }
}
