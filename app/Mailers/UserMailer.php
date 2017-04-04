<?php

namespace App\Mailers;

use App\User\Entity as UserEntity;
use App\Exception\InvalidContactInformationException;

class UserMailer extends Mailer
{
    const INVALID_USER_ERROR = "A valid user object must be provided for delivering an email.";

    /**
     * Create a new abstract mailer instance.
     *
     * @param UserEntity $user
     */
    public function __construct(UserEntity $user)
    {
        $this->to = $user->name;
        $this->email = $user->email;
        $this->data = $user->toArray();
    }

    /**
     * Responsible for sending out an account confirmation email to the user
     *
     * @return self
     */
    public function accountVerification()
    {
        $this->subject = 'Razorpay | Confirm Your Email';
        $this->view = 'emails.confirmation';
        $this->mailTag = MailTags::ACCOUNT_CONFIRMATION_MAIL;

        return $this;
    }
}
