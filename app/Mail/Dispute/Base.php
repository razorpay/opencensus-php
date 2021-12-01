<?php

namespace RZP\Mail\Dispute;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Currency\Currency;
use RZP\Models\Dispute;

class Base extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $fromName = Constants::HEADERS[Constants::DISPUTES];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addRecipients()
    {
        $merchantEmail = $this->data['merchant']['email'];

        $this->to($merchantEmail);

        return $this;
    }

    protected function addCc()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $this->cc($email);

        $salesPOCEmailId = (new Dispute\Service)->getSalesPOCEmailId($this->data['merchant']['id']);

        if ($salesPOCEmailId !== null)
        {
            $this->cc($salesPOCEmailId);
        }

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $replyToName = Constants::HEADERS[Constants::DISPUTES];

        $this->replyTo($email, $replyToName);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function getFormattedAmount($amount, $currency)
    {
        return Currency::getSymbol($currency) . ' ' . ((float) ($amount / Currency::getDenomination($currency)));
    }

}
