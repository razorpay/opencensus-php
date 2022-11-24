<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\OrgWiseConfig;

class FeatureEnabled extends Mailable
{
    protected $data;

    protected $merchant;

    public function __construct(array $data, $merchant = null)
    {
        parent::__construct();

        $this->data = $data;

        $this->merchant = $merchant;
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
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $fromName = Constants::HEADERS[Constants::SUPPORT];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.feature_enabled');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.merchant.feature_enabled_text');

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->data['feature'] . ' enabled for Live mode';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->data = array_merge($this->data, OrgWiseConfig::getOrgDataForEmail($this->merchant));

        $this->with($this->data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::FEATURE_ENABLED;
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
