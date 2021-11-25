<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Constants\MailTags;

class SettlementBankAccountFailure extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        parent::addMailData();

        $this->data = array_merge($this->data, $data);
    }

    protected function addRecipients()
    {
        $email =$this->data['merchant']['email'];

        $this->to($email);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY], 'Razorpay Settlement Support');

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $this->replyTo($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $mailData = $this->data;

        $mailData['subject'] = $this->getSubject();

        $this->with($mailData);

        return $this;
    }

    protected function getSubject()
    {
        return 'Your settlements are on temporary hold!';
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.settlement_bank_account_failure');

        return $this;
    }
}
