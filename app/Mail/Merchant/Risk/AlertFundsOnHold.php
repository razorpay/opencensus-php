<?php

namespace RZP\Mail\Merchant\Risk;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class AlertFundsOnHold extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $toEmail = $this->data['merchant']['email'];

        $this->to($toEmail);

        $this->cc(Constants::MAIL_ADDRESSES[Constants::SUPPORT], Constants::HEADERS[Constants::SUPPORT]);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $fromName = Constants::HEADERS[Constants::SUPPORT];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.risk.alert_funds_on_hold');

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Account Review: ' . $this->data['merchant']['id'] . ' | ' . $this->data['merchant']['name'];

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::MERCHANT_RISK_ALERT_FUNDS_ON_HOLD;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->data['merchant']['id']);

            $headers->addTextHeader(MailTags::HEADER, MailTags::MERCHANT_RISK_ALERT_FUNDS_ON_HOLD);
        });

        return $this;
    }
}
