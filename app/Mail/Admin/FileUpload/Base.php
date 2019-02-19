<?php

namespace RZP\Mail\Admin\FileUpload;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Base extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $fromName = Constants::HEADERS[Constants::SUPPORT];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addReplyTo()
    {
        $address = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $header = Constants::HEADERS[Constants::NOREPLY];

        return $this->replyTo($address, $header);
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addAttachments()
    {
        if (isset($this->data['file']) === true)
        {
            $this->attach($this->data['file']['signed_url'], [
                'as'   => $this->data['file']['display_name'] . '.' . $this->data['file']['extension'],
                'mime' => $this->data['file']['mime']
            ]);
        }

        return $this;
    }
}
