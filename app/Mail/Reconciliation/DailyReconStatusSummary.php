<?php

namespace RZP\Mail\Reconciliation;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class DailyReconStatusSummary extends Mailable
{
    protected $data;

    protected $emails;

    protected $params;

    const RECIPIENT_EMAILS_MAP = ['pgrecon.reports@razorpay.com'];

    public function __construct(array $emails, array $params, array $data)
    {
        parent::__construct();

        $this->emails = (empty($emails) === false) ? $emails : self::RECIPIENT_EMAILS_MAP;

        $this->params = $params;

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $to = $this->emails;

        $this->to($to);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::RECON];

        $fromHeader = Constants::HEADERS[Constants::RECON];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addSubject()
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-y');

        $subject = 'Razorpay | Daily Reconciliation Summary for ' . $date;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $mailData = [
            'summary' => $this->data,
            'params'  => $this->params
        ];

        $this->with($mailData);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DAILY_RECON_SUMMARY);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.reconciliation.daily_recon_summary');

        return $this;
    }

    protected function addAttachments()
    {
        foreach ($this->data as $entity => $data)
        {
            if (empty($data['unreconciled_data_file']) === false)
            {
                foreach ($data['unreconciled_data_file'] as $file)
                {
                    $this->attach($file['url'], ['as' => $file['name']]);
                }
            }

            if (empty($data['recon_summary_file']) === false)
            {
                $file  = $data['recon_summary_file'];

                $this->attach($file['url'], ['as' => $file['name']]);
            }
        }

        return $this;
    }
}
