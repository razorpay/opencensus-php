<?php

namespace RZP\Mail\OAuth;

use RZP\Mail\Base\Constants;
use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class TallyAuthOtp extends Mailable
{
    const TALLY_AUTH_OTP_EMAIL_TEMPLATE       = 'emails.oauth.tally_auth_otp';
    const TALLY_AUTH_OTP_EMAIL_STORK_TEMPLATE = 'gai_tally_auth_otp';
    const STORK_NAMESPACE                     = "razorpayx_apps";
    const SENDER_ADDRESS                      = "noreply@razorpay.com";
    const SENDER_NAME                         = "Team RazorpayX";

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $this->to($this->data['email'], $this->data['user']['name']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TALLY_AUTH_OTP_EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Authorisation OTP for ' . $this->data["application"]["name"] . ' Integration');

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function(Email $message) {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::OAUTH_APP_AUTHORIZED);
        });

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT], Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT], Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_namespace' => self::STORK_NAMESPACE,
            'owner_id'           => $this->data['merchant']['id'],
            'org_id'             => $this->data['merchant']['org_id'],
            'template_name'      => self::TALLY_AUTH_OTP_EMAIL_STORK_TEMPLATE,
            'params'             => [
                'application_logo_url' => $this->data['application']['logo_url'],
                'application_name'     => $this->data['application']['name'],
                'merchant_id'          => $this->data['merchant']['id'],
                'merchant_name'        => $this->data['merchant']['name'],
                'otp'                  => $this->data['otp'],
                'email'                => $this->data['email'],
            ]
        ];
    }
}
