<?php

namespace RZP\Mail\User;

use RZP\Mail\Base;
use RZP\Constants\Product;

class AccountVerification extends Base\Mailable
{
    protected $org;

    protected $user;

    protected $token;

    protected $product;

    public function __construct($user, $org, $product = Product::PRIMARY)
    {
        parent::__construct();

        $this->user = $user->toArrayPublic();

        $this->token = $user->getConfirmToken();

        $this->org = $org;

        $this->product = $product;
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

        $subject = sprintf("%s | Confirm Your Email", $orgName);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'token'     => $this->token,
            'org'       => $this->org,
            'product'   => $this->product,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.account_verification');

        return $this;
    }
}
