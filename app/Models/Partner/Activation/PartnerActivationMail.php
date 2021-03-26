<?php

namespace RZP\Models\Partner\Activation;

use App;
use RZP\Mail\Base;
use RZP\Mail\Base\Mailable;

class PartnerActivationMail extends Mailable
{
    const SUBJECT = "Partner Activation";

    const TEMPLATE_PATH = "emails.mjml.merchant.partner.activation.partner_activation_confirmation";

    protected $merchantId;

    protected $merchant;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $this->merchantId = $merchantId;

        $this->setMerchant();
    }

    protected function setMerchant()
    {
        $repo = App::getFacadeRoot()['repo'];

        $this->merchant = $repo->merchant->find($this->merchantId);
    }


    protected function addSender()
    {
        $this->from(Base\Constants::MAIL_ADDRESSES[Base\Constants::PARTNERSHIPS],
                    Base\Constants::HEADERS[Base\Constants::PARTNERSHIPS]);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->merchant->getEmail());

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Base\Constants::MAIL_ADDRESSES[Base\Constants::PARTNERSHIPS],
                    Base\Constants::HEADERS[Base\Constants::PARTNERSHIPS]);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'merchantId'    => $this->merchantId,
            'merchant_name' => $this->merchant->getName()
        ];

        $this->with($data);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH, ['merchantName' => $this->merchant['name']]);

        return $this;
    }

}