<?php

namespace RZP\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Merchant;

class DailyReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $data;

    protected $merchant;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $data, Merchant\Entity $merchant)
    {
        $this->data = $data;

        $this->merchant = $merchant;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Razorpay | Daily Transaction Report for ' . $data['date'];

        return $this->view('emails.merchant.daily_report')
                    ->to($this->data['email'])
                    ->from('reports@razorpay.com')
                    ->replyTo('support@razorpay.com', 'Razorpay Support')
                    ->cc('notifications@razorpay.com')
                    ->subject($subject)
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, $this->merchant->getPublicId());

                        $headers->addTextHeader(MailTags::HEADER, MailTags::DAILY_REPORT);
                    });
    }
}
