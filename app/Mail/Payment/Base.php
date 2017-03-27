<?php

namespace RZP\Mail\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class Base extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $event;

    protected $data;

    protected $domain;

    protected $metadata;

    public function __construct(string $event, array $data)
    {
        $this->event = $event;

        $this->data = $data;

        $this->domain = 'razorpay.com';
    }

    public function build()
    {
        $from = $this->metadata['from'] ?? 'reports';
        $from = $this->getCompleteEmail($from);

        $fromName = 'Team Razorpay';

        $replyTo = $this->getCompleteEmail('support');

        $label = Event::getLabel($this->event);

        $to = $this->getTo();

        $subject = $this->getSubject();

        $paymentId = $this->data['payment']['id'];

        $this->from($from, $fromName)
                ->to($to)
                ->subject($subject)
                ->replyTo($replyTo)
                ->with($this->data)
                ->withSwiftMessage(function ($message) use ($paymentId, $label)
                {
                    $headers = $message->getHeaders();

                    $headers->addTextHeader(MailTags::HEADER, $paymentId);

                    $headers->addTextHeader(MailTags::HEADER, $label);
                });

        if (isset($this->metadata['view']['html']) === true)
        {
            $this->view($this->metadata['view']['html']);
        }

        if (isset($this->metadata['view']['text']) === true)
        {
            $this->text($this->metadata['view']['text']);
        }

        return $this;
    }

    protected function getSubject()
    {
        $action = Event::getAction($this->event, $this->data);

        /**
         * The reason we have a fallback to the amount here is because
         * not every merchant necessarily has a proper billing label (most do)
         * Since the dba field was moved from the dashboard to the API after a
         * while. All new merchants have this field for sure, though. We
         * can do a survey later and remove this check from here and other
         * places
         */
        if (isset($this->data['merchant']['billing_label']))
        {
            $subject = "$action successful for {$this->data['merchant']['billing_label']}";
        }
        else
        {
            $subject = "$action successful for {$this->data['payment']['amount']}";
        }

        return $subject;
    }

    /**
     * Returns a complete email address
     *
     * @param  string $user (reports)
     * @return string (reports@razorpay.com)
     */
    protected function getCompleteEmail(string $user)
    {
        return "$user@{$this->domain}";
    }
}
