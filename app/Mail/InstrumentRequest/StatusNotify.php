<?php

namespace RZP\Mail\InstrumentRequest;

use RZP\Mail\Base;
use RZP\Constants\Product;
use RZP\Mail\Base\Constants;

class StatusNotify extends Base\Mailable
{
    protected $data;

    const REJECTED = "rejected";
    const ACTIVATED = "activated";
    const ACTION_REQUIRED = "action_required";


    public function __construct(array $data)
    {
        parent::__construct();

        $this->originProduct = "instrumentation";

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
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $fromName = Constants::HEADERS[Constants::NOREPLY];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Regarding your payment method activation request with Razorpay';

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
        if ($this->data['current_status'] == self::REJECTED)
        {
            $this->view('emails.instrumentation.instrument_rejected');
        }
        elseif ($this->data['current_status'] == self::ACTIVATED)
        {
            $this->view('emails.instrumentation.instrument_activated');
        }
        elseif ($this->data['current_status'] == self::ACTION_REQUIRED)
        {
            $this->view('emails.instrumentation.instrument_action_required');
        }

        return $this;
    }
}
