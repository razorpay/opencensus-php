<?php

namespace RZP\Mail\Merchant\Partner;

use RZP\Mail\Base;
use RZP\Mail\Base\Mailable;

class KycAccessConfirmed extends Mailable
{
    const SUBJECT = '[Request Approved] Now perform your affiliates’s Razorpay KYC';
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $this->from(Base\Constants::MAIL_ADDRESSES[Base\Constants::PARTNERSHIPS],
            Base\Constants::HEADERS[Base\Constants::PARTNERSHIPS]);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->data['partner']['email']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Base\Constants::MAIL_ADDRESSES[Base\Constants::PARTNERSHIPS],
            Base\Constants::HEADERS[Base\Constants::PARTNERSHIPS]);

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
            'org_id'             => $this->data['merchant']['org_id'],
            'template_name'      => 'submerchant_kyc_access.approved',
            'params'  => [
                'submerchant_id' => $this->data['merchant']['id'],
                'submerchant_name' => $this->data['merchant']['name'],
                'partner_name' => $this->data['partner']['name'],
                'partner_id' => $this->data['partner']['id'],
            ],
        ];
    }

    protected function addHtmlView()
    {
        $this->view('submerchant_kyc_access.approved');

        return $this;
    }
}