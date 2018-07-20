<?php

namespace RZP\Mail\User;

use RZP\Mail\Base;
use RZP\Models\User;

class MappedToAccount extends Base\Mailable
{
    protected $org;

    /** @var  User\Entity */
    protected $user;

    /**
     * @var array
     */
    protected $subMerchant;

    public function __construct(User\Entity $user, array $org, array $submerchant)
    {
        parent::__construct();

        $this->user = $user->toArrayPublic();

        $this->org = $org;

        $this->subMerchant = $submerchant;
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

        $subject = sprintf("Added as owner to a team | %s", $orgName);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'org'         => $this->org,
            'subMerchant' => $this->subMerchant,
            'user'        => $this->user,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.mapped_to_account');

        return $this;
    }
}
