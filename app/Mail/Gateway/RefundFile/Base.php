<?php

namespace RZP\Mail\Gateway\RefundFile;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Models\Payment\Gateway;

class Base extends Mailable
{
    protected $type;

    protected $data;

    protected $emails;

    public function __construct(array $data,
                                string $type,
                                array $emails = [])
    {
        parent::__construct();

        $this->data = $data;

        $this->type = $type;

        $this->emails = $emails;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::REFUNDS];

        $fromHeader = Constants::HEADER_MAP[$this->type];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = (empty($this->emails) === true) ?
                    Constants::RECIPIENT_EMAILS_MAP[$this->type] :
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
        $mailData = [
            'body' => Constants::BODY_MAP[$this->type],
        ];

        if (empty($this->data['body']) === false)
        {
            $mailData['body'] = $this->data['body'];
        }

        // For ICICI netbanking refunds we are adding the subject to template data
        // as the template used for this requires the subject
        if ($this->type === Gateway::NETBANKING_ICICI)
        {
            $mailData['subject'] = $this->getSubject();
        }

        $mailData = array_merge($mailData, $this->data);

        $this->with($mailData);

        return $this;
    }

    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = Constants::SUBJECT_MAP[$this->type] . $today;

        return $subject;
    }

    protected function addHtmlView()
    {
        $this->view(Constants::MAIL_TEMPLATE_MAP[$this->type]);

        return $this;
    }

    protected function addAttachments()
    {
        if ((empty($this->data['signed_url']) === false) && (empty($this->data['file_name']) === false))
        {
            $this->attach($this->data['signed_url'], ['as' => $this->data['file_name']]);
        }

        return $this;
    }

    protected function addHeaders()
    {
        $header = Constants::MAILTAG_MAP[$this->type];

        $this->withSymfonyMessage(function (Email $message) use ($header)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $header);
        });

        return $this;
    }
}
