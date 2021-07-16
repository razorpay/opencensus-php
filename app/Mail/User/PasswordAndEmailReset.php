<?php

namespace RZP\Mail\User;

use App;
use RZP\Mail\Base;
use RZP\Models\User;
use RZP\Trace\TraceCode;

class PasswordAndEmailReset extends Base\Mailable
{
    protected $org;

    /**
     * @var User\Entity
     */
    protected $user;

    protected $token;

    protected $email;

    protected $merchantId;

    private $app;


    public function __construct($user, $org, $email, $merchantId)
    {
        parent::__construct();

        $this->user = $user;

        $this->email = $email;

        $this->merchantId = $merchantId;

        $this->token = (new User\Service)->getTokenWithExpiry(
            $this->user['id'],
            User\Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME
        );

        $this->org = $org;
    }

    protected function addRecipients()
    {
        $email = $this->email;

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

        $subject = sprintf("%s - Email Change Request", $orgName);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'token'               => $this->token,
            'org'                 => $this->org,
            'current_owner_email' => $this->user['email'],
            'email'               => $this->email,
            'merchant_id'         => $this->merchantId
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.edit_email_and_password_set');

        return $this;
    }
}
