<?php

namespace RZP\Mail\Merchant\Partner;

use RZP\Mail\Base;
use RZP\Mail\Base\Mailable;

class KycAccessRequest extends Mailable
{
    const SUBJECT = 'Your Partner wants to perform your Razorpay KYC ';

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
        $this->to($this->data['merchant']['email']);

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
            'template_name'      => 'submerchant_kyc_access.requested',
            'params'  => [
                'submerchant_name' => $this->data['merchant']['name'],
                'partner_name' => $this->data['partner']['name'],
                'partner_id' => $this->data['partner']['id'],
                'approve_url' => $this->data['approve_url'],
                'reject_url' => $this->data['reject_url'],
            ],
        ];
    }

    protected function addHtmlView()
    {
        $this->view('submerchant_kyc_access.requested');

        return $this;
    }
}