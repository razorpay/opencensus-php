<?php

namespace RZP\Mail\Gateway\Nach;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Mailable;

class Base extends Mailable
{
    protected $data;

    protected $type;

    protected $emails;

    public function __construct(array $data, string $type, array $emails = [])
    {
        parent::__construct();

        $this->data = $data;

        $this->type = $type;

        $this->emails = $emails;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NACH];

        $header = Constants::HEADER_MAP[$this->type];

        $this->from($email, $header);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->emails);

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(Constants::MAIL_TEMPLATE_MAP[$this->type]);

        return $this;
    }

    protected function addAttachments()
    {
        if (isset($this->data['files']) === true)
        {
            foreach ($this->data['files'] as $file)
            {
                $this->attach($file['signed_url'], ['as' => $file['file_name']]);
            }
        }

        return $this;
    }

    protected function addMailData()
    {
        $mailData = [
            'body' => Constants::BODY_MAP[$this->type],
        ];

        $mailData = array_merge($mailData, $this->data);

        $this->with($mailData);

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

    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        return Constants::SUBJECT_MAP[$this->type] . $today;
    }
}
