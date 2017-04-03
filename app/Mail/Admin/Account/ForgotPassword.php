<?php

namespace RZP\Mail\Admin\Account;

use RZP\Constants\MailTags;

class ForgotPassword extends Base
{
    const TOKEN = 'token';

    public function __construct($admin, array $input)
    {
        parent::__construct($admin, $input);

        $this->header = MailTags::FORGOT_PASSWORD;
    }

    protected function addSubject()
    {
        $subject = 'Reset your password for' . $this->org->getDisplayName() . ' dashboard';

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
        $data = [
            'firstName' => $this->admin->getFirstName(),
            'resetUrl'  => $this->input['reset_password_url'] . '/' . $this->input[self::TOKEN],
            'orgName'   => $this->org->getDisplayName(),
        ];

        $this->with($data);

        return $this;
    }
}
