<?php

namespace RZP\Mail\Admin;

use RZP\Constants\MailTags;

class ForgotPassword extends Base
{
    public function __construct($event, array $input)
    {
        parent::__construct($event);

        $this->header = MailTags::FORGOT_PASSWORD;
    }

    protected function getSubject()
    {
        $subject = 'Reset your password for' . $this->org->getDisplayName() . ' dashboard';

        return $subject;
    }

    protected function getView()
    {
        return 'emails.auth.admin_password_reset';
    }

    protected function getData()
    {
        return [
            'firstName' => $this->admin->getFirstName(),
            'resetUrl'  => $this->input['reset_password_url'] . '/' . $this->input[self::TOKEN],
            'orgName'   => $this->org->getDisplayName(),
        ];
    }
}
