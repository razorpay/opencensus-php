<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class EsEnabledNotify extends Mailable
{
    const TO_EMAIL = 'to_email';
    const TO_NAME = 'to_name';
    const VIEW = 'view';
    const SUBJECT = 'subject';
    const MERCHANT_DATA = 'merchant_data';
    const KAM_MAILING_LIST_EMAILS = ['ayush.bansal@razorpay.com', 'abhirup.bhabani@razorpay.com', 'kamteam@razorpay.com'];
    const KAM_MAILING_LIST_NAMES = ['Ayush Bansal', 'Abhirup Bhabhani', 'KAM team'];
    const KAM_MAILER_VIEW = 'emails.merchant.es_enabled_notify_kam';
    const MERCHANT_MAILER_VIEW = 'emails.merchant.es_enabled_notify_merchant';
    const KAM_MAILER_SUBJECT = 'A new Merchant has joined ES Scheduled!!';
    const MERCHANT_MAILER_SUBJECT = 'Congratulations, Early Settlements has been enabled for your account!';

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $toEmail = $this->data['to_email'];

        $toName = $this->data['to_name'];

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

    protected function addHtmlView()
    {
        $this->view($this->data['view']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->data['subject']);

        return $this;
    }

    protected function addMailData()
    {
        // Exposes elements from $data into the HTML template
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::FEATURE_ENABLED);
        });

        return $this;
    }
}
