<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant;

class PartnerOnBoarded extends Mailable
{
    protected $partner;

    public function __construct(array $partner)
    {
        parent::__construct();

        $this->partner = $partner;
    }

    protected function addSender()
    {
        $email  = Constants::MAIL_ADDRESSES[Constants::PARTNER_ON_BOARDING];
        $header = Constants::HEADERS[Constants::PARTNER_ON_BOARDING];

        $this->from($email, $header);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::PARTNER_ON_BOARDING_REPLY];

        $this->replyTo($email);

        return $this;
    }

    protected function addRecipients()
    {
        $email = $this->partner['email'];
        $name  = $this->partner['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addSubject()
    {
        $partnerType = $this->partner[Merchant\Entity::PARTNER_TYPE];

        if ($partnerType === Merchant\Constants::PURE_PLATFORM)
        {
            $this->subject('You’re just a step away from becoming a Razorpay Partner');
        }
        else
        {
            $this->subject('Welcome to Razorpay Partner Program');
        }

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'name'         => $this->partner['name'],
            'partner_type' => $this->partner['partner_type'],
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::PARTNER_ON_BOARDED);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $partnerType = $this->partner[Merchant\Entity::PARTNER_TYPE];

        if ($partnerType === Merchant\Constants::RESELLER)
        {
            $this->view('emails.mjml.merchant.partner.onboarded.reseller');
        }
        else if ($partnerType === Merchant\Constants::AGGREGATOR)
        {
            $this->view('emails.mjml.merchant.partner.onboarded.aggregator');
        }
        else if ($partnerType === Merchant\Constants::PURE_PLATFORM)
        {
            $this->view('emails.mjml.merchant.partner.onboarded.pure_platform');
        }

        return $this;
    }


}
