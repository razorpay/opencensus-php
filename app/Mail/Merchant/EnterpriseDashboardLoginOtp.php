<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base;
use RZP\Models\User;

class EnterpriseDashboardLoginOtp extends Base\Mailable
{
    /**
     * @var User\Entity
     */

    protected $email;

    protected $otp;

    public function __construct($email, $otp)
    {
        parent::__construct();

        $this->email = $email;

        $this->otp = $otp;
    }

    protected function addRecipients()
    {
        $email = $this->email;

        $name = $this->email;

        $this->to($email, $name);

        return $this;
    }

    protected function addSender()
    {
        $this->from('noreply@razorpay.com', 'Razorpay Software Private Ltd');

        return $this;
    }

    protected function addSubject()
    {

        $subject = sprintf("Razorpay Support Login OTP");

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = ['otp' => $this->otp];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {

        $this->view('emails.merchant.enterprise_dashboard_login_otp');

        return $this;
    }
}
