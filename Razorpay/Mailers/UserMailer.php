<?php

namespace Razorpay\Mailers;

use Models\User\Entity as UserEntity;
use Razorpay\Exceptions\InvalidContactInformationException;

class UserMailer extends Mailer
{
    const INVALID_USER_ERROR = "A valid user object must be provided for delivering an email.";

    /**
     * Create a new abstract mailer instance.
     *
     * @param object $user
     */
    public function __construct(UserEntity $user)
    {
        if(!is_object($user))
        {
            throw new InvalidContactInformationException(self::INVALID_USER_ERROR);
        }

        $this->to = $user->name;
        $this->email = $user->email;
        $this->data = $user->toArray();

        if($user->hasMerchants())
            $this->data['merchant_details'] = $user->currentMerchant->merchantDetails->toArray();
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

        return $this;
    }
}
