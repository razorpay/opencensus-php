<?php

namespace RZP\Mail\Banking;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Models\BankAccount\Entity as BankAccount;

class BeneficiaryFile extends Mailable
{
    protected $data;

    protected $channel;

    protected $count;

    public function __construct(array $data, string $channel, int $count)
    {
        parent::__construct();

        $this->data = $data;

        $this->channel = $channel;

        $this->count = $count;
    }

    protected function addSender()
    {
        $fromEmail = Constants::FROM_EMAIL_MAP[$this->channel];

        $fromHeader = Constants::HEADER_MAP[$this->channel];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = $this->data[BankAccount::RECIPIENT_EMAILS] ?? Constants::RECIPIENT_EMAILS_MAP[$this->channel];

        $this->to($emails);

        return $this;
    }

    protected function addSubject()
    {
        $channel = $this->channel;

        $subject = 'Razorpay updated beneficiaries for ' . ucfirst($channel);

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addMailData()
    {
        if (isset($data['body']) === false)
        {
            $data['body'] = 'Please find attached updated beneficiary file for ' .
                            'Razorpay and kindly update it on your end.' .
                            'Beneficiaries Count is ' . $this->count . '.';
        }

        $this->with($data);

        return $this;
    }

    protected function addAttachments()
    {
        if (isset($this->data['signed_url']) === true)
        {
            $this->attach($this->data['signed_url'], ['as' => $this->data['file_name']]);
        }

        return $this;
    }

    protected function addHeaders()
    {
        $mailtag = Constants::MAILTAG_MAP[$this->channel];

        $this->withSymfonyMessage(function (Email $message) use ($mailtag)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $mailtag);
        });

        return $this;
    }
}
