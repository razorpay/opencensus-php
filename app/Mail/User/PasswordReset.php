<?php

namespace RZP\Mail\User;

use RZP\Mail\Base;
use RZP\Models\User;
use RZP\Constants\Product;

class PasswordReset extends Base\Mailable
{
    protected $org;

    /**
     * @var User\Entity
     */
    protected $user;

    protected $token;

    protected $product;

    public function __construct($user, $org, $product = Product::PRIMARY)
    {
        parent::__construct();

        $this->user = $user;

        $this->token = (new User\Service)->getTokenWithExpiry(
                            $this->user['id'],
                            User\Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME
                        );

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

        $source = ($this->product === Product::BANKING) ? "RazorpayX" : "Razorpay";

        $subject = sprintf("Reset your %s password. ", $source);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'token'      => $this->token,
            'org'        => $this->org,
            'email'      => urlencode($this->user['email']),
            'product'    => $this->product,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.password_reset');

        return $this;
    }
}
