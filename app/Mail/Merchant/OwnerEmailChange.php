<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base;
use RZP\Models\User;

class OwnerEmailChange extends Base\Mailable
{
    protected $org;

    /**
     * @var User\Entity
     */
    protected $user;

    protected $email;

    protected $isChangeRequest;

    protected $merchantId;

    public function __construct($user, $org, $email, $merchantId, $isChangeRequest = true)
    {
        parent::__construct();

        $this->user = $user;

        $this->isChangeRequest = $isChangeRequest;

        $this->email = $email;

        $this->merchantId = $merchantId;

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

        $subject = sprintf("%s - Email Change Request for Merchant ID: %s", $orgName, $this->merchantId);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'org'                 => $this->org,
            'current_owner_email' => $this->user['email'],
            'email'               => $this->email,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        if ($this->isChangeRequest === true)
        {
            $this->view('emails.merchant.owner_email_change_request');
        }
        else
        {
            $this->view('emails.merchant.owner_email_change');
        }

        return $this;
    }
}
