<?php

namespace RZP\Models\Payment\Fraud\Notifications;

use RZP\Models\Merchant\RiskMobileSignupHelper;

class DomainMismatchMobileSignup extends DomainMismatch
{
    protected function getEmailData(): ?array
    {
        $pa = $this->payment->getMetadata('payment_analytics');
        if (is_null($pa) === true)
        {
            return null;
        }

        $refererUrl = $pa->getReferer();
        if (empty($refererUrl) === true)
        {
            return null;
        }

        $refererDomain = $this->getDomain($refererUrl);
        if (empty($refererDomain) === true)
        {
            return null;
        }

        return [
            'payment' => [
                'id'             => $this->payment->getId(),
                'referer_url'    => $refererUrl,
                'referer_domain' => $refererDomain
            ],
            'merchant' => [
                'id'    => $this->merchant->getId(),
                'name'  => $this->merchant->getName(),
                'email' => $this->merchant->getEmail()
            ]
        ];
    }

    protected function getSmsData(): ?array
    {
        $smsData = parent::getSmsData();

        $smsData['params']['supportTicketsUrl'] = (new RiskMobileSignupHelper())->getSupportTicketsUrl($this->merchant);

        return $smsData;
    }

    protected function getWhatsappData(): ?array
    {
        $whatsappData = parent::getWhatsappData();

        $whatsappData['supportTicketsUrl'] = (new RiskMobileSignupHelper())->getSupportTicketsUrl($this->merchant);

        return $whatsappData;
    }
}
