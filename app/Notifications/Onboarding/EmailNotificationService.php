<?php


namespace RZP\Notifications\Onboarding;

use Mail;
use RZP\Exception;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Notifications\BaseNotificationService;

class EmailNotificationService extends BaseNotificationService
{
    const ONBOARDING_SOURCE = 'api.merchant.onboarding';

    public function send(): void
    {
        $payload = $this->getPayload();
        $merchant = $this->args['merchant'];
        $org = $this->getOrg($merchant);

        try {
            $emailInstance = new MerchantOnboardingEmail(
                $payload, $org->toArray(),
                $this->getTemplateMessage(),
                $this->getTemplateSubject()
            );

            Mail::queue($emailInstance);

            $this->trace->info(
                TraceCode::MERCHANT_ONBOARDING_EMAIL_SENT,
                [
                    'merchant_id'   => $merchant->getMerchantId(),
                    'template'      => $this->getTemplateMessage()
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_ONBOARDING_EMAIL_FAILED,
                [
                    'merchant_id'   => $merchant->getMerchantId(),
                    'template'      => $this->getTemplateMessage()
                ]
            );
        }
    }

    private function getOrg($merchant)
    {
        $org = $merchant->org ?: $this->app['repo']->org->getRazorpayOrg();

        return $org;
    }

    protected function getPayload()
    {
        $merchant = $this->args['merchant'];
        $org = $this->getOrg($merchant);
        $hostname = '';

        if(empty($org->hostnames()->first()) === false)
        {
            $hostname = $org->getPrimaryHostName();
        }

        $data = [
            DEConstants::MERCHANT             => [
                MerchantEntity::NAME          => $merchant->getName(),
                MerchantEntity::BILLING_LABEL => $merchant->getBillingLabel(),
                MerchantEntity::EMAIL         => $merchant->getEmail(),
                DEConstants::ORG              => [
                    DEConstants::HOSTNAME     => $hostname,
                ]
            ],
        ];

        $extraData = $this->args['params'] ?? [];

        return array_merge($data, $extraData);
    }

    private function getTemplateMessage()
    {
        return Events::EMAIL_TEMPLATES[$this->event];
    }

    private function getTemplateSubject()
    {
        return Events::EMAIL_SUBJECTS[$this->event];
    }
}
