<?php

namespace RZP\Mail\Gateway;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\Timezone;
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
        if (empty($this->data['claimsFile']) === false)
        {
            if (empty($this->data['claimsFile']['url']) === false)
            {
                $this->attach($this->data['claimsFile']['url'], ['as' => $this->data['claimsFile']['name']]);
            }
        }

        if (empty($this->data['refundsFile']) === false)
        {
            if (empty($this->data['refundsFile']['url']) === false)
            {
                $this->attach($this->data['refundsFile']['url'], ['as' => $this->data['refundsFile']['name']]);
            }
        }

        if (empty($this->data['summaryFile']) === false)
        {
            if (empty($this->data['summaryFile']['url']) === false)
            {
                $this->attach($this->data['summaryFile']['url'], ['as' => $this->data['summaryFile']['name']]);
            }
        }

        return $this;
    }

    protected function addHeaders()
    {
        $mailTag = $this->getMailTag();

        $this->withSymfonyMessage(function (Email $message) use ($mailTag)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $mailTag);
        });

        return $this;
    }

    protected function getMailTag()
    {
        $bankName = $this->data['bankName'];

        return (strtolower($bankName) . '_' . MailTags::DAILY_FILE);
    }

    protected function getSubject()
    {
        if (isset($this->data['subject']) === true)
        {
            return $this->data['subject'];
        }

        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = '';

        $corporate = $this->data['corporate'] ?? false;

        if ($corporate === true)
        {
            $subject .= 'Corporate ';
        }

        $subject .= $this->data['bankName'] . ' Netbanking claims and refund files for ' . $today;

        return $subject;
    }
}
