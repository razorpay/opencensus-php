<?php

namespace RZP\Mail\Banking;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class BeneficiaryFile extends Mailable
{
    const KOTAK_BENEFICIARY_MAIL       = 'kotak_beneficiary_file@razorpay.com';
    const KOTAK_BENEFICARY_FROM_HEADER = 'Razorpay Kotak Beneficiary File';
    const RECIPIENT_EMAILS             = ['aanchal.wadhwani@kotak.com', 'settlements@razorpay.com'];
    const CC_EMAILS                    = [
                                            'uphendra.bn@kotak.com',
                                            'Abhijit.B.Joshi@kotak.com',
                                            'anupam.namdeo@kotak.com'
                                         ];

    protected $data;

    public function __construct(array $data)
    {
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

    protected function addCc()
    {
        $this->cc(self::CC_EMAILS);

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
                        'Beneficiaries Count is '. $this->data['merchantsCount'] .'.';

        $this->with($data);

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['filePath']);

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
