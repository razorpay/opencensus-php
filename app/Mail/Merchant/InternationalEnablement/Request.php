<?php
namespace RZP\Mail\Merchant\InternationalEnablement;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class Request extends Mailable
{
    protected $data;
    protected $email;

    public function __construct(array $data, string $email)
    {
        parent::__construct();

        $this->data = $data;
        $this->email = $email;
    }

    protected function addRecipients()
    {
        $name = $this->data['business_name'];
        $this->to($this->email, $name);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Razorpay | International Payment Acceptance Request';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->data['merchant_id']);

            $headers->addTextHeader(MailTags::HEADER, MailTags::INTERNATIONAL_ENABLEMENT);
        });

        return $this;
    }

    protected function addSender()
    {
        $from = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $fromHeader = Constants::HEADERS[Constants::NOREPLY];

        $this->from($from, $fromHeader);

        return $this;
    }
}
