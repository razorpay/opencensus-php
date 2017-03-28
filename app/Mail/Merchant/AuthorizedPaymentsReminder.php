<?php

namespace RZP\Mail\Merchant;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class AuthorizedPaymentsReminder extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = $this->getSubject();

        $emails = $this->data['merchant'][Merchant\Entity::TRANSACTION_REPORT_EMAIL];

        $name = $this->data['merchant'][Merchant\Entity::NAME];

        $to = [];

        foreach ($emails as $email) {
            $to[] = [$email. $name];
        }

        return $this->view('emails.merchant.authorized_reminder')
                    ->with($this->data)
                    ->to($to)
                    ->from('reports@razorpay.com')
                    ->cc('notifications@razorpay.com')
                    ->replyTo('support@razorpay.com', 'Razorpay Support')
                    ->subject($subject)
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::AUTH_REMINDER);

                        foreach ($this->data['payments'] as $payment) {
                            $headers->addTextHeader(MailTags::HEADER, $payment->getPublicId());
                        }
                    });
    }

    protected function getSubject()
    {
        // date format = 6th July 2015
        $date = Carbon::today('Asia/Kolkata')->format('jS F Y');

        $final = $this->data['final'];

        if ($final === true)
        {
            return "Razorpay | Final Authorized Payments Reminder for $date";
        }

        return "Razorpay | Authorized Payments Reminder for $date";
    }
}
