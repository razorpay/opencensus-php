<?php

namespace RZP\Mail\Admin\Account;

use RZP\Constants\MailTags;

class ForgotPassword extends Base
{
    const TOKEN = 'token';

    public function __construct(array $admin, array $org, array $input)
    {
        parent::__construct($admin, $org, $input);
    }

    protected function addSubject()
    {
        $subject = 'Reset your password for' . $this->org['display_name'] . ' dashboard';

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.auth.admin_password_reset');

        return $this;
    }

    protected function addMailData()
    {
        $firstName = explode(' ', $this->admin['name'])[0];

        $data = [
            'firstName' => $firstName,
            'resetUrl'  => $this->input['reset_password_url'] . '/' . $this->input[self::TOKEN],
            'orgName'   => $this->org['display_name'],
        ];

        $this->with($data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::FORGOT_PASSWORD;
    }
}
