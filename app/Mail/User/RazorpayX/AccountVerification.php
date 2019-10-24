<?php

namespace RZP\Mail\User\RazorpayX;

use RZP\Mail\Base\Mailable;

class AccountVerification extends Mailable
{
    protected $org;

    protected $user;

    protected $token;

    protected $product;

    public function __construct($user, $org)
    {
        parent::__construct();

        $this->user = $user->toArrayPublic();

        $this->token = $user->getConfirmToken();

        $this->org = $org;
    }

    protected function addRecipients()
    {
        $email = $this->user['email'];

        $name = $this->user['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addSender()
    {
        $this->from($this->org['from_email'], $this->org['display_name']);

        return $this;
    }

    protected function addSubject()
    {
        $orgName = $this->org['display_name'];

        $subject = sprintf('%s | Confirm Your Email', $orgName);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'token' => $this->token,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.razorpayx.account_verification');

        return $this;
    }
}

