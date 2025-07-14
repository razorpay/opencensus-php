<?php

namespace RZP\Mail\Merchant\Partner;

use RZP\Mail\Base;
use RZP\Mail\Base\Mailable;
use RZP\Models\Merchant;

class KycAccessConfirmed extends Mailable
{
    const RESELLER_SUBJECT = '[Request Approved] Now perform your affiliates’s Razorpay KYC';
    const AGGREGATOR_SUBJECT = '[Request Approved] Access Merchant Account for your affiliate';

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
        $this->subject(self::RESELLER_SUBJECT);

        if ($this->data['partner']['partner_type'] === Merchant\Constants::AGGREGATOR) {
            $this->subject(self::AGGREGATOR_SUBJECT);
        }

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
        $templateName = 'submerchant_kyc_access.approved';

        if ($this->data['partner']['partner_type'] === Merchant\Constants::AGGREGATOR) {
            $templateName = 'submerchant_account_access.approved';
        }

        return [
            'template_namespace' => 'partnerships',
            'org_id'             => $this->data['merchant']['org_id'],
            'template_name'      => $templateName,
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
