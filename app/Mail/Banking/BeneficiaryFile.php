<?php

namespace RZP\Mail\Banking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class BeneficiaryFile extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $filePath;

    protected $merchantsCount;

    public function __construct(string $filePath, int $merchantsCount)
    {
        $this->filePath = $filePath;

        $this->merchantsCount = $merchantsCount;
    }

    public function build()
    {
        $data = [];

        $data['body'] = 'Please find attached updated beneficiary file for ' .
                        'Razorpay and kindly update it on your end.' .
                        'Beneficiaries Count is '. $this->merchantsCount .'.';

        $emails = ['aanchal.wadhwani@kotak.com', 'settlements@razorpay.com'];

        $cc = ['uphendra.bn@kotak.com', 'Abhijit.B.Joshi@kotak.com', 'anupam.namdeo@kotak.com'];

        $this->from('kotak_beneficiary_file@razorpay.com', 'Razorpay Kotak Beneficiary File')
                ->to($emails)
                ->cc($cc)
                ->subject('Razorpay updated beneficiary file for Kotak')
                ->view('emails.message')
                ->with($data)
                ->attach($this->filePath)
                ->withSwiftMessage(function ($message)
                {
                    $headers = $message->getHeaders();

                    $headers->addTextHeader(MailTags::HEADER, MailTags::KOTAK_BENEFICIARY_MAIL);
                });
    }
}
