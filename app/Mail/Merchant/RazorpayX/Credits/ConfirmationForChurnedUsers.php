<?php

namespace RZP\Mail\Merchant\RazorpayX\Credits;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class ConfirmationForChurnedUsers extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addHtmlView()
    {
        $this->view('emails.credits.razorpayx.churned_users');

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
        $subject = $this->data['merchant']['name'] . ' there\'s an offer insider for you';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'credits' => $this->data['credits'],
        ];

        $this->with($data);

        return $this;
    }
}
