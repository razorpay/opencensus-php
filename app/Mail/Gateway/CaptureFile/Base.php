<?php

namespace RZP\Mail\Gateway\CaptureFile;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class Base extends Mailable
{
    protected $gateway;

    protected $acquirer;

    protected $data;

    protected $emails;

    protected $fileData;

    public function __construct(array $data, string $gateway, string $acquirer, array $emails = [], $fileData)
    {
        parent::__construct();

        $this->data = $data;

        $this->gateway = $gateway;

        $this->acquirer = $acquirer;

        $this->emails = $emails;

        $this->fileData = $fileData;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::CAPTURE];

        $fromHeader = Constants::HEADER_MAP[$this->gateway][$this->acquirer];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = (empty($this->emails) === true) ?
            Constants::RECIPIENT_EMAILS_MAP[$this->gateway][$this->acquirer] :
            $this->emails;

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
        if(empty($this->data) === false)
        {
            $mailData = $this->data;
        }
        else
        {
            $mailData = [
                'body' => Constants::BODY_MAP[$this->gateway][$this->acquirer],
            ];
        }

        $mailData = array_merge($mailData, $this->data);

        $this->with($mailData);

        return $this;
    }

    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = Constants::SUBJECT_MAP[$this->gateway][$this->acquirer] . $today;

        return $subject;
    }

    protected function addAttachments()
    {
        if(empty($this->fileData) === false)
        {
            $this->attach($this->data['signed_url'], ['as' => $this->data['file_name']]);
        }

        return $this;
    }

    protected function addHeaders()
    {
        $header = Constants::MAILTAG_MAP[$this->gateway][$this->acquirer];

        $this->withSymfonyMessage(function (Email $message) use ($header)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $header);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(Constants::MAIL_TEMPLATE_MAP[$this->gateway][$this->acquirer]);

        return $this;
    }
}
