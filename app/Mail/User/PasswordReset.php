<?php

namespace RZP\Mail\User;

use Carbon\Carbon;
use RZP\Mail\Base;
use RZP\Models\User;

class PasswordReset extends Base\Mailable
{
    const EXPIRYTIME = 86400; //24 hours

    protected $org;

    protected $user;

    protected $token;

    protected $expiryTime;

    public function __construct($user, $org)
    {
        parent::__construct();

        $this->user = $user->toArrayPublic();

        $this->token = $this->getToken();

        $this->org = $org;
    }

    protected function getToken()
    {
        $this->expiryTime = Carbon::now()->timestamp + self::EXPIRYTIME;

        $token = (new User\Core)->generateToken($this->user['id'], $this->expiryTime);

        return $token;
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
            'expiryTime' => $this->expiryTime,
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
