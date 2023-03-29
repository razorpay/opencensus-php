<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Mail\Base\Common;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Models\Merchant\Detail\Entity as Detail;

class CreateSubMerchantPartner extends Mailable
{
    protected $subMerchant;

    protected $aggregator;

    public function __construct(array $subMerchant, array $aggregator)
    {
        parent::__construct();

        $this->subMerchant = $subMerchant;

        $this->aggregator = $aggregator;
    }

    protected function addRecipients()
    {
        $email = $this->aggregator['email'];

        $name = $this->aggregator['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Congratulations! ' . $this->subMerchant['name'] . ' has been added as your sub-merchant');

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'merchant'           => $this->aggregator,
            'subMerchant'        => $this->subMerchant,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::SUB_MERCHANT_ADDED);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $countryCode = $this->aggregator['country_code'];

        if ($countryCode == 'MY')
        {
            $this->view('emails.merchant.my_add_sub_merchant_mail_partner');
        }
        else
        {
            $this->view('emails.merchant.add_sub_merchant_mail_partner');
        }

        return $this;
    }
}
