<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use Symfony\Component\Mime\Email;

class ResellerToPurePlatformPartnerSwitchEmail extends Mailable
{
    protected $data;
    protected $countryCode;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;

        $this->countryCode = $data['country_code'];
    }

    protected function addSender()
    {
        $this->from(
            Constants::MAIL_ADDRESSES_GLOBAL[$this->countryCode][Constants::PARTNER_ON_BOARDING],
            Constants::HEADERS_GLOBAL[$this->countryCode][Constants::PARTNER_ON_BOARDING]
        );

        return $this;
    }

    protected function addRecipients()
    {
        $email = $this->data['merchant']['email'];

        $name = $this->data['merchant']['name'];

        $this->to($email, strlen($name) < 50 ? $name : substr($name, 0, 46) . "...");

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Partner account type updated to Platform Partner type';

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::COMMISSION_INVOICE);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->data['view']);

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_namespace' => 'partnerships',
            'params'      => [
                'partner_name'           => $this->data['merchant']['name'],
            ]
        ];
    }
}
