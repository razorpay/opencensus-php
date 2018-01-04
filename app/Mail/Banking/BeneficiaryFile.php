<?php

namespace RZP\Mail\Banking;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class BeneficiaryFile extends Mailable
{
    const KOTAK_BENEFICIARY_MAIL       = 'kotak_beneficiary_file@razorpay.com';
    const KOTAK_BENEFICARY_FROM_HEADER = 'Razorpay Kotak Beneficiary File';
    const RECIPIENT_EMAILS             = ['kotak.beneficiary@razorpay.com'];

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = self::KOTAK_BENEFICIARY_MAIL;

        $fromHeader = self::KOTAK_BENEFICARY_FROM_HEADER;

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to(self::RECIPIENT_EMAILS);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Razorpay updated beneficiary file for Kotak';

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
        $data['body'] = 'Please find attached updated beneficiary file for ' .
                        'Razorpay and kindly update it on your end.' .
                        'Beneficiaries Count is '. $this->data['merchants_count'] .'.';

        $this->with($data);

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['signed_url'], ['as' => $this->data['file_name']]);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::KOTAK_BENEFICIARY_MAIL);
        });

        return $this;
    }
}
