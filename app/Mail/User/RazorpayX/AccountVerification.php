<?php

namespace RZP\Mail\User\RazorpayX;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class AccountVerification extends Mailable
{
    const SUBJECT  = 'Verify your Email for RazorpayX';

    const TEMPLATE = 'emails.user.razorpayx.account_verification';

    protected $userId;

    protected $user = null;

    public function __construct($userId)
    {
        parent::__construct();

        $this->userId = $userId;
    }

    protected function getUser()
    {
        if ($this->user === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->user = $repo->user->find($this->userId);
        }

        return $this->user;
    }

    protected function addRecipients()
    {
        $user = $this->getUser();

        $this->to($user->getEmail(),
                  $user->getName());

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addMailData()
    {
        $user = $this->getUser();

        $data = [
            'token' => $user->getConfirmToken(),
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE);

        return $this;
    }
}

