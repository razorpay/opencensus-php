<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base\Common;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;

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
        $this->subject('Congratulations! ' . $this->subMerchant['name'] . ' has been added as your merchant');

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'merchant'    => $this->aggregator,
            'subMerchant' => $this->subMerchant,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::SUB_MERCHANT_ADDED);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.add_sub_merchant_mail_partner');

        return $this;
    }
}
