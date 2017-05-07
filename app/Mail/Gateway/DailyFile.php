<?php

namespace RZP\Mail\Gateway;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class DailyFile extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::REFUNDS];

        $fromHeader = $this->data['bankName'] . ' Netbanking Refunds';

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = $this->data['emails'];

        $this->to($emails);

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
        $this->data['subject'] = $this->getSubject();

        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $view = 'emails.admin.' . lcfirst($this->data['bankName']) . '_refunds';

        $this->view($view);

        return $this;
    }

    protected function addAttachments()
    {
        // @note Check with Firi once on this change
        if (empty($this->data['claimsFile']) === false)
        {
            if (isset($this->data['claimsFile']['url']) === true)
            {
                $this->attach($this->data['claimsFile']['url'], ['as' => $this->data['claimsFile']['name']]);
            }
            else
            {
                $this->attach($this->data['claimsFile']);
            }
        }

        if (empty($this->data['refundFile']) === false)
        {
            if (isset($this->data['refundsFile']['url']) === true)
            {
                $this->attach($this->data['refundsFile']['url'], ['as' => $this->data['refundsFile']['name']]);
            }
            else
            {
                $this->attach($this->data['refundsFile']);
            }
        }

        return $this;
    }

    protected function addHeaders()
    {
        $mailTag = $this->getMailTag();

        $this->withSwiftMessage(function ($message) use ($mailTag)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $mailTag);
        });

        return $this;
    }

    protected function getMailTag()
    {
        $bankName = $this->data['bankName'];

        switch ($bankName)
        {
            case 'Axis':

                return MailTags::AXIS_NETBANKING_REFUNDS_MAIL;

            case 'Federal':

                return MailTags::FEDERAL_NETBANKING_REFUNDS_MAIL;

            case 'Kotak':

                return MailTags::KOTAK_NETBANKING_REFUNDS_MAIL;
        }
    }

    protected function getSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = $this->data['bankName'] . ' Netbanking claims and refund files for ' . $today;

        return $subject;
    }
}
