<?php

namespace RZP\Mail\OAuth;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class CompetitorAppAuthorized extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $this->to(Constants::MAIL_ADDRESSES[Constants::SUPPORT], Constants::HEADERS[Constants::SUPPORT]);

        $this->cc(Constants::MAIL_ADDRESSES[Constants::PRODUCT_OAUTH], Constants::HEADERS[Constants::PRODUCT_OAUTH]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.oauth.competitor_app_authorized');

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Razorpay | Competitor access granted');

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
