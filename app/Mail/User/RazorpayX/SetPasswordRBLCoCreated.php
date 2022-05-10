<?php

namespace RZP\Mail\User\RazorpayX;

use App;
use RZP\Models\User;
use RZP\Constants\Product;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class SetPasswordRBLCoCreated extends Mailable
{
    const SUBJECT = 'Welcome to RazorpayX - Banking made awesome! ⚡';

    const TEMPLATE = 'emails.user.razorpayx.set_password_rbl_co_created';

    protected $org;

    /**
     * @var User\Entity
     */
    protected $user;

    protected $token;

    protected $product;

    public function __construct($user, $product = Product::BANKING)
    {
        parent::__construct();

        $this->user = $user;

        $this->token = (new User\Service)->getTokenWithExpiry(
            $this->user['id'],
            User\Constants::CO_CREATED_CREATE_PASSWORD_TOKEN_EXPIRY_TIME
        );

        $this->product = $product;
    }

    protected function addRecipients(): SetPasswordRBLCoCreated
    {
        $user = $this->user;

        $this->to($user->getEmail(),
                  $user->getName());

        return $this;
    }

    protected function addSender(): SetPasswordRBLCoCreated
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addSubject(): SetPasswordRBLCoCreated
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addMailData(): SetPasswordRBLCoCreated
    {
        $data = [
            'token'      => $this->token,
            'email'      => urlencode($this->user['email']),
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView(): SetPasswordRBLCoCreated
    {
        $this->view(self::TEMPLATE);

        return $this;
    }
}

