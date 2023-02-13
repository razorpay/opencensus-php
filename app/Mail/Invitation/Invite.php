<?php

namespace RZP\Mail\Invitation;

use RZP\Mail\Base\Constants;
use Symfony\Component\Mime\Email;

use RZP\Mail\Base\Common;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class Invite extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $email = $this->data['email'];

        $this->to($email);

        return $this;
    }

    protected function addSubject()
    {

        $orgName = "Razorpay";

        if (!empty($this->data['org']['custom_branding']) and
            (isset($this->data['org']['custom_code']) === true) and
            ($this->data['org']['custom_code'] == 'curlec'))
        {
            $orgName = $this->data['org']['org_name'];
        }

        $subject = "Invitation to join a team | " . $orgName;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {

        $emailParams = [
            'sender_name'   => $this->data['sender_name'],
            'merchant_name' => $this->data['name'],
            'token'         => $this->data['token'],
            'product'       => $this->data['product'],
            'email_logo'    => $this->data['org']['email_logo'],
            'org_name'      => $this->data['org']['org_name'],
            'hostname'      => $this->data['org']['hostname'],
            'custom_code'   => $this->data['org']['custom_code'],
            'custom_branding'   => $this->data['org']['custom_branding'],
            'checkout_logo'   => $this->data['org']['checkout_logo'],
        ];

        $this->with(array_merge($emailParams, $this->data));

        return $this;
    }

    protected function addHtmlView()
    {
        $view = $this->data['user_id'] ? 'emails.invitation.existing' : 'emails.invitation.new';

        $this->view($view);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::MERCHANT_INVITATION_MAIL);
        });


        return $this;
    }

    protected function addSender()
    {
        $fromEmail = $this->getSenderEmail();

        $fromHeader = $this->getSenderHeader();

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function getSenderEmail(): string
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderEmailForOrg($orgCode, Constants::SUPPORT);
    }

    protected function getSenderHeader(): string
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderNameForOrg($orgCode, Constants::SUPPORT);
    }

}
