<?php

namespace RZP\Mail\Payout;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use \RZP\Models\Payout\Constants as PayoutConstant;

class Attachments extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout.attachments';

    const SUBJECT = 'Payouts Attachments';

    protected $data;

    protected $templateName;

    protected $merchantEmail;

    protected $customSubject;

    public function __construct(array $merchantEmail, array $data)
    {
        parent::__construct();

        $this->data = $data;

        $this->merchantEmail = $merchantEmail;
    }

    protected function addAttachments()
    {
        if ((isset($this->data[PayoutConstant::ATTACHMENT_FILE_URL]) === true) and
            (isset($this->data[PayoutConstant::DISPLAY_NAME]) === true) and
            (isset($this->data[PayoutConstant::MIME]) === true))
        {

            $this->attach($this->data[PayoutConstant::ATTACHMENT_FILE_URL],
                [
                    'as' => $this->data[PayoutConstant::DISPLAY_NAME] . '.' . $this->data[PayoutConstant::EXTENSION],
                    'mime' => $this->data[PayoutConstant::MIME]
                ]
            );
        }

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->merchantEmail);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
            Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addHtmlView()
    {
        $this->view(self::EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(sprintf(self::SUBJECT));

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

}
