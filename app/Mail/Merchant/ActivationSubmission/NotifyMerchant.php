<?php

namespace RZP\Mail\Merchant\ActivationSubmission;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Admin\Org;

class NotifyMerchant extends Mailable
{
    protected $data;

    protected $org;

    public function __construct(array $data, array $org)
    {
        parent::__construct();

        $this->data = $data;

        $this->org = $org;
    }

    protected function addRecipients()
    {
       $this->to($this->data['to_email'], $this->data['to_name']);

       return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.notify_activation_submission');

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
        $this->subject($this->data['subject']);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data['mail_data']);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::CONFIRM_ACTIVATION_SUBMISSION);
        });

        return $this;
    }
}
