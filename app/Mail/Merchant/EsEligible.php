<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base;
use RZP\Constants\Product;
use RZP\Mail\Base\Constants;

class EsEligible extends Base\Mailable
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
        $subject = 'Get settlements in less than 10 seconds!';

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
        $this->view('emails.merchant.es_eligible');

        return $this;
    }
}
