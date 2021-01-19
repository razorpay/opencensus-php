<?php

namespace RZP\Mail\Payout;

use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class DowntimeNotification  extends Mailable
{
     protected $data;

    //TODO: to be added
    const DEFAULT_TEMPLATE = 'emails.payout.downtime_default';

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
            Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->data['to']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->data['subject']);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data['body']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->data['template']);

        return $this;
    }
}
