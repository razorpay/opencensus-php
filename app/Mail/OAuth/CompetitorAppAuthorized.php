<?php

namespace RZP\Mail\OAuth;

use Symfony\Component\Mime\Email;

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
        $this->to(Constants::MAIL_ADDRESSES[Constants::FRESHDESK], Constants::HEADERS[Constants::FRESHDESK]);

        $this->cc(Constants::MAIL_ADDRESSES[Constants::PARTNERSHIPS], Constants::HEADERS[Constants::PARTNERSHIPS]);

        $this->cc(Constants::MAIL_ADDRESSES[Constants::APPROVALS_OAUTH], Constants::HEADERS[Constants::APPROVALS_OAUTH]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.oauth.competitor_app_authorized');

        return $this;
    }

    protected function addSubject()
    {
        $appName = $this->data['application']['name'] ?? '';

        $merchantName = $this->data['merchant']['name'] ?? '';

        $this->subject('Razorpay | Partner Oauth Grant - ' . $appName . ' - by ' . $merchantName);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::OAUTH_APP_AUTHORIZED);
        });

        return $this;
    }
}
