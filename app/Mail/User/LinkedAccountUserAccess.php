<?php

namespace RZP\Mail\User;

use RZP\Mail\Base;
use RZP\Models\User;
use RZP\Models\Merchant;

class LinkedAccountUserAccess extends Base\Mailable
{
    protected $org;

    /** @var  User\Entity */
    protected $user;

    /**
     * @var string
     */
    protected $routeMerchantName;

    protected $token;

    public function __construct(User\Entity $user, array $org, Merchant\Entity $submerchant)
    {
        parent::__construct();

        $this->user = $user->toArrayPublic();

        $this->org = $org;

        $this->routeMerchantName = $submerchant->parent->getName();

        $this->token = (new User\Service)->getTokenWithExpiry(
                            $this->user['id'],
                            User\Constants::LINKED_ACCOUNT_CREATE_PASSOWRD_TOKEN_EXPIRY_TIME
                        );
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
        $orgName = $this->org['business_name'];

        $subject = sprintf("%s invite from %s", $orgName, $this->routeMerchantName);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'org'               => $this->org,
            'routeMerchantName' => $this->routeMerchantName,
            'token'             => $this->token,
            'email'             => urlencode($this->user['email']),
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.linked_account_user_access');

        return $this;
    }
}
