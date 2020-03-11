<?php

namespace RZP\Mail\Banking;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class YesbankLoadViaAdjustment extends Mailable
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    public function __construct(Merchant\Entity $merchant, array $data)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->data = $data;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT], Constants::HEADERS[Constants::X_SUPPORT]);
    }

    protected function addRecipients()
    {
        return $this->to($this->merchant->getEmail());
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::X_SUPPORT];

        $header = Constants::HEADERS[Constants::X_SUPPORT];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addSubject()
    {
        $modePrefix = ($this->mode === Mode::TEST) ? Constants::TEST_MODE_PREFIX : '';

        $subject = sprintf(
            "{$modePrefix}Your A/C ending with %s has been credited with INR %s",
            mask_except_last4($this->data['account_number']),
            amount_format_IN($this->data['amount']));

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        return $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->data['adjustment_id']);
            $headers->addTextHeader(MailTags::HEADER, MailTags::YESBANK_LOAD_ADJUSTMENT);
        });
    }


    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.banking.yesbank_load_via_adjustment');

        return $this;
    }
}
