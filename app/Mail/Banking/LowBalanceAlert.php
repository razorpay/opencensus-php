<?php

namespace RZP\Mail\Banking;

use Symfony\Component\Mime\Email;

use RZP\Models\Merchant;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class LowBalanceAlert extends Mailable
{
    protected $data;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    public function __construct(Merchant\Entity $merchant, array $data)
    {
        parent::__construct();

        $this->data = $data;

        $this->merchant = $merchant;
    }

    protected function addRecipients()
    {
        $this->to($this->data['emails']);

        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::ALERTS];

        $this->from($email);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::X_SUPPORT];

        $header = Constants::HEADERS[Constants::X_SUPPORT];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addSubject()
    {
        $subject = '[Alert] Razorpay X | Low Balance for Account No. ' . $this->data['masked_account_number'];

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->merchant->getId());

            $headers->addTextHeader(MailTags::HEADER, MailTags::RX_LOW_BALANCE_ALERT);
        });

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.low_balance_alert');

        return $this;
    }
}
