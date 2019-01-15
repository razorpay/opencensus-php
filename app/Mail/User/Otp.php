<?php

namespace RZP\Mail\User;

use RZP\Models\User;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Otp extends Mailable
{
    /**
     * @var string
     */
    public $action;

    /**
     * @var array
     */
    public $user;

    /**
     * @var array
     */
    public $otp;

    public function __construct(string $action, User\Entity $user, array $otp)
    {
        parent::__construct();

        $this->action = str_replace('_', ' ', $action);
        $this->user   = $user->toArrayPublic();
        $this->otp    = $otp;
    }

    protected function addRecipients()
    {
        $this->to($this->user['email'], $this->user['name']);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject("RazorpayX | OTP to {$this->action}");

        return $this;
    }

    protected function addMailData()
    {
        $this->with(
            [
                'action' => $this->action,
                'user'   => $this->user,
                'otp'    => $this->otp,
            ]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.otp');

        return $this;
    }
}
