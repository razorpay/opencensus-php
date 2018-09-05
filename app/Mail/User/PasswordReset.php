<?php

namespace RZP\Mail\User;

use RZP\Mail\Base;
use RZP\Models\User;

class PasswordReset extends Base\Mailable
{
    protected $org;

    /**
     * @var User\Entity
     */
    protected $user;

    protected $token;

    public function __construct(User\Entity $user, $org)
    {
        parent::__construct();

        $this->user = $user->toArrayPublic();

        $this->token = (new User\Service)->getTokenWithExpiry(
                            $this->user['id'],
                            User\Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME
                        );

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

        $subject = sprintf("%s - Password Reset Request", $orgName);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'token'      => $this->token,
            'org'        => $this->org,
            'email'      => urlencode($this->user['email']),
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
