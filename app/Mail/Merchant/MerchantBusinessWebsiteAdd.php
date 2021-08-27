<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class MerchantBusinessWebsiteAdd extends Mailable
{

    protected $user;

    protected $merchant;

    public function __construct(array $merchant, array $user)
    {
        parent::__construct();

        $this->user = $user;

        $this->merchant = $merchant;

    }

    protected function addRecipients()
    {
        $this->to($this->user['email'], $this->user['name']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Razorpay: Update on API key access ' . $this->merchant['name'] ?? $this->merchant['id']);

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $data = [
            'updated_business_website' => $this->merchant['merchant_detail']['business_website'],
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.merchant_business_website_add');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::MERCHANT_BUSINESS_WEBSITE_ADD);
        });

        return $this;
    }
}
