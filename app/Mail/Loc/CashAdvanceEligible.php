<?php

namespace RZP\Mail\Loc;

use RZP\Mail\Base;
use RZP\Mail\Base\Constants;

class CashAdvanceEligible extends Base\Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $toEmail = $this->data['contact_email'];

        $toName = $this->data['contact_name'];

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::CAPITAL_SUPPORT];

        $fromName = Constants::HEADERS[Constants::CAPITAL_SUPPORT];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Congratulations, you have unlocked Razorpay Credit line facility';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.loc.razorpay_cash_advance_eligible');

        return $this;
    }
}
