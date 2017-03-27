<?php

namespace RZP\Mail\Admin;

use RZP\Constants\MailTags;

class CreateAccount extends Base
{
    protected $url;

    public function __construct($admin, $input, $url)
    {
        parent::__construct($admin, $input);

        $this->header = MailTags::ADMIN_CREATE;

        $this->url = $url;
    }

    public function canSend()
    {
        return ($this->org->getAuthType() !== 'password');
    }

    protected function getSubject()
    {
        $subject = 'Your admin account details for ' . $this->org->getDisplayName() . ' dashboard';
    }

    public function getView()
    {
        return [
            'html' => 'emails.admin.user',
            'text' => 'emails.admin.user_text'
        ];
    }

    public function getData()
    {
        return [
            'user' => $this->admin->getEmail(),
            // todo: Hack for now. Remove it
            'password' => $input['password'],
            'org' => $org->getDisplayName(),
            'url' => $this->url,
        ];
    }
}
