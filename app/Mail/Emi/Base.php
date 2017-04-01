<?php

namespace RZP\Mail\Emi;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class Base extends Mailable
{
    const AXIS = 'Axis';
    const INDUSIND = 'IndusInd';
    const KOTAK = 'Kotak';
    const RBL = 'Rbl';

    const BANK_RECIPIENTS_MAP = [
        self::AXIS     => ['axiscards.emi@razorpay.com'],
        self::INDUSIND => ['indusind.emi@razorpay.com'],
        self::KOTAK    => ['kotakcards.emi@razorpay.com'],
        self::RBL      => ['Rblcards.emi@razorpay.com']
    ];

    protected $bankName;

    public function __construct(string $bankName)
    {
        $this->bankName = $bankName;
    }

    protected function addRecipients()
    {
        $emails = array_merge(self::BANK_RECIPIENTS_MAP[$this->bankName], ['settlements@razorpay.com']);

        $this->to($emails);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::EMI_FILE);
        });

        return $this;
    }

    public function build()
    {
        $to = $this->getToEmails();

        $fromHeader = $this->getFromHeader();

        $data = $this->getData();

        $this->from('emifiles@razorpay.com', $fromHeader)
                    ->to($to)
                    ->subject($data['subject'])
                    ->with($data)
                    ->view('emails.message')
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::EMI_FILE);
                    });

        if ($this->filePath !== null)
        {
            $this->attach($this->file);
        }

        return $this;
    }

    protected function getFromHeader()
    {
        return '';
    }

    protected function getData()
    {
        return [];
    }

    protected function getToEmails()
    {
        return $this->emailIdsToSendTo;
    }
}
