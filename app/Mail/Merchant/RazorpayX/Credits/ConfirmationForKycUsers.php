<?php

namespace RZP\Mail\Merchant\RazorpayX\Credits;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class ConfirmationForKycUsers extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addHtmlView()
    {
        $this->view('emails.credits.razorpayx.kyc_users');

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->data['merchant']['email']);

        return $this;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY], Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addSubject()
    {
        $subject = 'Your Rs.' . $this->data['credits'] . ' worth Free Credits are waiting for you!';

        $this->subject($subject);

        return $this;
    }
}
