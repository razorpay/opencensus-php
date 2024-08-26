<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Models\Admin\Org;
use RZP\Mail\Base\EmailHelper;

class Rejection extends Mailable
{
    protected $data;

    protected $org;

    public function __construct(array $data, array $org)
    {
        parent::__construct();
        $this->data = $data;
        $this->org  = $org;
    }

    public function getData()
    {
        return $this->data;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->data['email'], $this->data['name']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.rejection_notification');

        return $this;
    }

    protected function addSender()
    {
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->from($this->org['from_email'], $this->org['display_name']);
        }

        return $this;
    }

    protected function addSubject()
    {
        $label   = $this->data['id'] .' '. $this->data['name'];
        $subject = 'Activation form update '. $label;
        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_REJECTED);
        });

        return $this;
    }

    public function shouldSendEmailViaStork(): bool
    {
        return  (new EmailHelper)->isStorkSupported($this->data['id'], $this->org['id'], '_rejection_notification') ?? false;
    }

    public function getParamsForStork(): array
    {
        $storkParams = 
        [
            'template_name' => 'banking_mail_rejection_notification',
            'template_namespace' => 'payments_banking',
            'params' => $this->data,
        ];
        //
        if((empty($this->data['custom_branding']) === false) and ( $this->data['custom_branding'] === true) and (empty($this->data['email_logo']) === false))
        {
            $storkParams['params']['is_email_logo'] = true;
        }else
        {
            $storkParams['params']['is_email_logo'] = false;
        }

        return $storkParams;
    }
}
