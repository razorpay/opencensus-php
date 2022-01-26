<?php


namespace RZP\Notifications\Onboarding;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Constants;
use RZP\Notifications\BaseNotificationService;
use RZP\Notifications\Onboarding\Constants as OnboardingConstants;

class SmsNotificationService extends BaseNotificationService
{
    const ONBOARDING_SOURCE = 'api.merchant.onboarding';

    public function send(): void
    {
        $payload  = $this->getPayload();
        $merchant = $this->args[Constants::MERCHANT];

        try
        {
            $this->app['stork_service']->sendSms(
                $this->mode,
                $payload
            );

            $this->trace->info(
                TraceCode::MERCHANT_ONBOARDING_SMS_SENT,
                [
                    'mid'      => $merchant->getMerchantId(),
                    'template' => $payload[OnboardingConstants::SMS_TEMPLATE_NAME]
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_ONBOARDING_SMS_FAILED,
                [
                    'mid'      => $merchant->getMerchantId(),
                    'template' => $this->getTemplateMessage()
                ]
            );
        }
    }

    protected function getPayload()
    {
        $merchant = $this->args[Constants::MERCHANT];

        $merchantId = $merchant->getMerchantId();

        $orgId = $merchant->getOrgId();

        $templateName = $this->getTemplateMessage();

        $payload = [
            OnboardingConstants::OWNER_ID                    => $merchantId,
            OnboardingConstants::OWNER_TYPE                  => OnboardingConstants::MERCHANT,
            OnboardingConstants::ORG_ID                      => $orgId,
            OnboardingConstants::SENDER                      => OnboardingConstants::RZRPAY,
            OnboardingConstants::DESTINATION                 => $this->getPhone(),
            OnboardingConstants::SMS_TEMPLATE_NAME           => $templateName,
            OnboardingConstants::TEMPLATE_NAMESPACE          => OnboardingConstants::PAYMENTS_ONBOARDING,
            OnboardingConstants::LANGUAGE                    => OnboardingConstants::ENGLISH,
            OnboardingConstants::CONTENT_PARAMS              => [
                Constants::MERCHANT_NAME   => $merchant->getName(),
                Constants::DASHBOARD_URL   => $this->app[Constants::CONFIG]->get(Constants::APPLICATIONS_DASHBOARD_URL)
            ],
            OnboardingConstants::DELIVERY_CALLBACK_REQUESTED => true
        ];

        $payload[Constants::PARAMS] = array_merge($payload[OnboardingConstants::CONTENT_PARAMS], $this->args[Constants::PARAMS] ?? []);

        return $payload;
    }

    private function getPhone()
    {
        $merchant = $this->args[Constants::MERCHANT];

        return $merchant->merchantDetail->getContactMobile();
    }

    private function getTemplateMessage()
    {
        return Events::SMS_TEMPLATES[$this->event];
    }
}
