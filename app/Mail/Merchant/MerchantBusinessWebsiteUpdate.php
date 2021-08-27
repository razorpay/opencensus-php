<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class MerchantBusinessWebsiteUpdate extends Mailable
{
    protected $previousWebsite;

    protected $merchant;

    protected $user;

    public function __construct(array $merchant, string $previousWebsite, array $user)
    {
        parent::__construct();

        $this->user = $user;

        $this->merchant = $merchant;

        $this->previousWebsite = $previousWebsite;
    }

    protected function addRecipients()
    {
        $this->to($this->user['email'], $this->user['name']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Razorpay: Website updated successfully ' . $this->merchant['name'] ?? $this->merchant['id']);

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $data = [
            'previous_business_website' => $this->previousWebsite,
            'updated_business_website'  => $this->merchant['merchant_detail']['business_website']
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.merchant_business_website_update');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::MERCHANT_BUSINESS_WEBSITE_UPDATE);
        });

        return $this;
    }
}
