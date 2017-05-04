<?php

namespace RZP\Mail\Admin\Account;

use Config;

use RZP\Constants\MailTags;

class Create extends Base
{
    public function __construct(array $admin, array $org, array $input)
    {
        parent::__construct($admin, $org, $input);

        $this->header = MailTags::ADMIN_CREATE;
    }

    public function canSend()
    {
        return ($this->org['auth_type'] !== 'password');
    }

    protected function addSubject()
    {
        $subject = 'Your admin account details for ' . $this->org['display_name'] . ' dashboard';

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.user');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.admin.user_text');

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'user' => [
                'email'    => $this->admin['email'],
                // todo: Hack for now. Remove it
                'password' => $this->input['password'],
                'org'      => $this->org['display_name'],
                'url'      => Config::get('applications.dashboard.url'),
            ]
        ];

        $this->with($data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::ADMIN_CREATE;
    }
}
