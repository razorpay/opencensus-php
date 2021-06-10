<?php

namespace RZP\Mail\CapitalCards;

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
        $toEmail = $this->data["to"];

        $toName = $this->data["data"]['merchant_name'];

        $this->to($toEmail, $toName);

        return $this;
    }
    protected function addCc() {
        if (isset($this->data['cc']) === true)
        {
            $cc_list = $this->data['cc'];
            $this->cc($cc_list);
        }
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
        $this->view('emails.capital_cards.' . $this->data['template']);

        return $this;
    }
    protected function addAttachments()
    {
        if (isset($this->data['file']) === true)
        {
            $this->attach($this->data['file']['signed_url'], [
                'as'   => $this->data['file']['name'] . '.' . $this->data['file']['extension'],
                'mime' => $this->data['file']['mime']
            ]);
        }
        return $this;
    }
}
