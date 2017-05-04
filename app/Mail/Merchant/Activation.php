<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class Activation extends Mailable
{
    protected $merchant;

    protected $parentAccount;

    protected $plan;

    protected $rules;

    public function __construct(array $merchant, array $parentAccount = null, array $plan, array $rules)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->parentAccount = $parentAccount;

        $this->plan = $plan;

    }

    protected function addRecipients()
    {
       $email = $this->merchant['email'];

       // For marketplace accounts, send this email to the parent merchant
       if ($this->parentAccount !== null)
       {
            $email = $this->parentAccount['email'];
       }

       $this->to($email);

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

    protected function addCc()
    {
        $this->cc(Constants::MAIL_ADDRESSES[Constants::NOTIFICATIONS]);

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
