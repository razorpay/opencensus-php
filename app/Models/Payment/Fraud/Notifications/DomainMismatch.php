<?php

namespace RZP\Models\Payment\Fraud\Notifications;

class DomainMismatch extends Base
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

        $accountDetails = $this->merchant->merchantDetail;
        $phone = $accountDetails->getContactMobile();
        if (empty($phone) === true)
        {
            return null;
        }

        return [
            'receiver' => $phone,
            'source'   => "api.{$this->mode}.payment",
            'params'   => [
                'merchant_id' => $this->merchant->getId(),
                'referer_domain' => $refererDomain
            ]
        ];
    }

    private function getDomain(string $url): string
    {
        $parsedUrl = parse_url($url);
        if (empty($parsedUrl['scheme']) === true)
        {
            $parsedUrl = parse_url('http://' . $url);
        }

        $host = '';
        if (isset($parsedUrl['host']) === true)
        {
            $host = $parsedUrl['host'];
        }

        return $host;
    }
}
