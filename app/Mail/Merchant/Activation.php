<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Admin\Org;

class Activation extends Mailable
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
       $this->to($this->data['merchant']['email']);

       return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.activation');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.merchant.activation_text');

        return $this;
    }

    protected function addSender()
    {
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->from($this->org['from_email'], $org['display_name']);
        }

        return $this;
    }

    protected function addCc()
    {
        if ($this->org[Org\Entity::ID] === Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->cc(Constants::MAIL_ADDRESSES[Constants::NOTIFICATIONS]);
        }

        return $this;
    }

    protected function addSubject()
    {
        $subjectName = $this->getSubjectName();

        $subject = "Razorpay | Account activated for $subjectName";

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $subjectName = $this->getSubjectName();

        $data = [
            'merchant' => $this->merchant,
            'plan'     => $this->plan,
            'name'     => $subjectName,
            'rules'    => $this->rules,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_ACTIVATED);
        });

        return $this;
    }

    protected function getSubjectName()
    {
        $subjectName = $this->merchant['billing_label'];

        if (empty($subjectName) === true)
        {
            $subjectName = $this->merchant['name'];
        }

        return $subjectName;
    }
}
