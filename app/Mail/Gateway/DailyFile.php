<?php

namespace RZP\Mail\Gateway;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Maiil\Base\Common;
use RZP\Mail\Base\Mailable;

class DailyFile extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = Common::MAIL_ADDRESSES[Common::SETTLEMENTS];

        $fromHeader = $this->data['bankName'] . ' Netbanking Refunds';

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = [Common::MAIL_ADDRESSES[Common::SETTLEMENTS]];

        $this->to($emails);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = $this->data['bankName'] . ' Netbanking claims and refund files for ' . $today;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $view = 'emails.admin.' . lcfirst($this->data['bankName']) . '_refunds';

        $this->view($view);

        return $view;
    }

    protected function addAttachments()
    {
        if (empty($this->data['claimsFile']) === false)
        {
            $this->attach($this->data['claimsFile']);
        }

        if (empty($this->data['refundFile']) === false)
        {
            $this->attach($this->data['refundsFile']);
        }

        return $this;
    }

    protected function addHeaders()
    {
        if ($this->data['bankName'] === 'Axis')
        {
            $this->withSwiftMessage(function ($message)
            {
                $headers = $message->getHeaders();

                $headers->addTextHeader(MailTags::HEADER, MailTags::AXIS_NETBANKING_REFUNDS_MAIL);
            });
        }

        return $this;
    }
}
