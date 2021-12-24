<?php

namespace RZP\Models\Payment\Fraud\Notifications;

use View;
use RZP\lib\TemplateEngine;
use RZP\Models\Payment\Fraud\Constants\Notification as NotifConstants;

class DomainMismatch extends Base
{
    const EMAIL_SUBJECT_TPL = '[IMP] Payment failed due to  attempts from unregistered website for MID - %s';
    const EMAIL_BODY    = 'emails.payment.fraud.domain_mismatch';

    protected function getEmailPayload(): ?array
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

    protected function getEmailData(): ?array
    {
        $emailPayload = $this->getEmailPayload();

        if ($this->config->getEmailProvider() === NotifConstants::FRESHDESK)
        {
            $mailSubject = sprintf(self::EMAIL_SUBJECT_TPL, $this->merchant->getId());

            $mailBody = View::make(self::EMAIL_BODY, $emailPayload)->render();

            return [
                'subject'         => $mailSubject,
                'description'     => $mailBody,
                'status'          => 6,
                'type'            => 'Service request',
                'priority'        => 1,
                'email'           => $this->merchant->getEmail(),
                'tags'            => ['website_mismatch'],
                'group_id'        => (int) $this->app['config']->get('applications.freshdesk')['group_ids']['rzpind']['merchant_risk'],
                'email_config_id' => (int) $this->app['config']->get('applications.freshdesk')['email_config_ids']['rzpind']['risk_notification'],
                'custom_fields'   => [
                    'cf_ticket_queue' => 'Merchant',
                    'cf_category'     => 'Risk Report_Merchant',
                    'cf_subcategory'  => 'Website Mismatch',
                    'cf_product'      => 'Payment Gateway',
                ],
            ];
        }
        return $emailPayload;
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
                'merchant_id'       => $this->merchant->getId(),
                'referer_domain'    => $refererDomain,
                'merchantName'      => $this->merchant->getName(),
            ],
            'stork' => [
                'context' => [
                    'org_id' => $this->merchant->getOrgId(),
                ],
            ],
        ];
    }

    protected function getWhatsappData(): ?array
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
            'merchantName'      => $this->merchant->getName(),
            'merchant_id'       => $this->merchant->getId(),
            'referer_domain'    => $refererDomain
        ];
    }

    protected function getDomain(string $url): string
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

    protected function getFreshdeskTicketData(): ?array
    {
        $emailPayload = $this->getEmailPayload();

        $requestParams = [
            'tags'            => ['website_mismatch'],
            'type'            => 'Service request',
            'subCategory'     => 'Website Mismatch',
        ];

        return [self::EMAIL_BODY, self::EMAIL_SUBJECT_TPL, $emailPayload, $requestParams];
    }
}
