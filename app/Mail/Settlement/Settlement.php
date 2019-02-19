<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Mail\Settlement\Constants as MailConstants;

class Settlement extends Base
{
    public function __construct(array $data)
    {
        $this->channel = ucfirst($data['channel']);

        parent::__construct($data);
    }

    protected function getFromHeader()
    {
        return $this->channel . ' Settlement File';
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $subject = $this->getSubject();

        $this->data['subject'] = $subject;

        $this->with($this->data);

        return $this;
    }

    protected function addRecipients()
    {
        if (isset($this->data['recipients']) === false)
        {
            parent::addRecipients();
        }
        else
        {
            $recipient = $this->data['recipients'];

            $this->to($recipient);
        }

        return $this;
    }

    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = $this->channel . " Settlement File for $today";

        if (isset($this->data['file_data']) === true)
        {
            $subject = $this->channel . " Settlement File::". $this->data['file_data']['file_name'] ." for $today";
        }

        return $subject;
    }

    protected function getMailTag()
    {
        $channel = $this->data['channel'];

        return MailConstants::MAILTAG_MAP[$channel];
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.settlement');

        return $this;
    }

    protected function addAttachments()
    {
        if (isset($this->data['file_data']) === true)
        {
            $this->attach($this->data['file_data']['signed_url'], ['as' => $this->data['file_data']['file_name']]);
        }

        return $this;
    }
}
