<?php

namespace RZP\Mail\User;

use RZP\Mail\Base;
use RZP\Models\User;
use RZP\Mail\Base\Constants;

class Login extends Base\Mailable
{
    protected $user;

    protected $orgHostname;

    protected $browserDetails;

    protected $loginAt;

    public function __construct(User\Entity $user, $orgHostname, $browserDetails, $loginAt)
    {
        parent::__construct();

        $this->user = $user->toArrayPublic();

        $this->orgHostname = $orgHostname;

        $this->browserDetails = $browserDetails;

        $this->loginAt = $loginAt;
    }

    protected function addRecipients()
    {
        $toEmail = $this->user['email'];

        $toName = $this->user['name'];

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SECURITY_ALERTS];

        $fromName = Constants::HEADERS[Constants::SECURITY_ALERTS];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addSubject()
    {
        $subject = "Security Alert: Login detected on your Razorpay account";

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'orgHostname'    => $this->orgHostname,
            'browserDetails' => $this->browserDetails,
            'loginAt'        => $this->loginAt
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.login');

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function getParamsForStork(): array
    {
        $storkParams = [
            'template_namespace' => 'payments_dashboard',
            'params'      => [
                'loginAt' => $this->loginAt,
                'os'      => $this->browserDetails['os'] ?? 'Unknown',
                'device'  => $this->browserDetails['device'] ?? 'Unknown',
                'browser' => $this->browserDetails['browser'] ?? 'Unknown',
                '2FALink' => 'https://' . $this->orgHostname . '/app/profile'
            ]
        ];

        return $storkParams;
    }
}

