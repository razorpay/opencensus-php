<?php

namespace RZP\Mail\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class Base extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    protected $domain;

    protected $isMerchantEmail;

    public function __construct(array $data, string $domain, bool $isMerchantEmail = false)
    {
        $this->data = $data;

        $this->domain = $domain;

        $this->isMerchantEmail = $isMerchantEmail;
    }

    public function build()
    {
        $from = $this->getFrom();

        $fromName = 'Team Razorpay';

        $replyTo = $this->getCompleteEmail('support');

        $mailTag = $this->getMailTag();

        $to = $this->getTo();

        $subject = $this->getSubject();

        $paymentId = $this->data['payment']['id'];

        $this->from($from, $fromName)
                ->to($to)
                ->subject($subject)
                ->replyTo($replyTo)
                ->with($this->data)
                ->addHtmlView()
                ->addTextView()
                ->withSwiftMessage(function ($message) use ($paymentId, $mailTag)
                {
                    $headers = $message->getHeaders();

                    $headers->addTextHeader(MailTags::HEADER, $paymentId);

                    $headers->addTextHeader(MailTags::HEADER, $mailTag);
                });

        return $this;
    }

    protected function addHtmlView()
    {
        return $this;
    }

    protected function addTextView()
    {
        return $this;
    }

    protected function getSubject()
    {
        $action = $this->getAction();

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

        if ($this->isMerchantEmail === true)
        {
            $subject = "Razorpay | $subject";
        }

        return $subject;
    }

    protected function getAction()
    {
        return 'Payment';
    }

    protected function getTo()
    {
        if ($this->isMerchantEmail === true)
        {
            return $this->data['merchant']['email'];
        }

        return $this->data['customer']['email'];
    }

    protected function getMailTag()
    {
        return MailTags::PAYMENT_SUCCESSFUL;
    }

    protected function getFrom()
    {
        return $this->getCompleteEmail('reports');
    }

    public function isCustomerReceiptEmail()
    {
        return false;
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
