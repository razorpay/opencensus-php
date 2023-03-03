<?php

namespace RZP\Mail\Loc;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Base extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        if ($this->data['to'] == 'ops')
        {
            $toEmail = Constants::MAIL_ADDRESSES[Constants::CAPITAL_OPS];
            $toName = Constants::CAPITAL_OPS;
        }
        else
        {
            $toEmail = $this->data['merchant_email'];

            $toName = $this->data['merchant_name'];
        }

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
        $subject = $this->data['subject'];

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
        $this->view('emails.loc.' . $this->data['template']);

        return $this;
    }
}
