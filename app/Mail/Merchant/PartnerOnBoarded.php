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
    protected $countryCode;

    public function __construct(array $partner)
    {
        parent::__construct();

        $this->partner = $partner;
        $this->countryCode = $this->partner[Merchant\Entity::COUNTRY_CODE];
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES_GLOBAL[$this->countryCode][Constants::PARTNER_ON_BOARDING];
        $header = Constants::HEADERS_GLOBAL[$this->countryCode][Constants::PARTNER_ON_BOARDING];

        $this->from($email, $header);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES_GLOBAL[$this->countryCode][Constants::PARTNER_ON_BOARDING_REPLY];

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

        $this->subject(Constants::PARTNER_ONBOARDED_SUBJECT_MAP[$this->countryCode][$partnerType]);

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
        $emailTemplate = Constants::PARTNER_ONBOARDER_EMAIL_TEMPLATE_MAP[$this->countryCode][$partnerType];

        $this->view($emailTemplate);

        return $this;
    }


}
