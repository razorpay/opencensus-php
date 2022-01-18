<?php


namespace RZP\Notifications\Onboarding;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Constants;
use RZP\Notifications\BaseNotificationService;

class SmsNotificationService extends BaseNotificationService
{
    const ONBOARDING_SOURCE = 'api.merchant.onboarding';

    public function send(): void
    {
        $payload  = $this->getPayload();
        $merchant = $this->args[Constants::MERCHANT];

        try
        {
            $this->app->raven->sendSms($payload);

            $this->trace->info(
                TraceCode::MERCHANT_ONBOARDING_SMS_SENT,
                [
                    'mid'      => $merchant->getMerchantId(),
                    'template' => $payload['template']
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

        $payload = [
            Constants::RECEIVER => $this->getPhone(),
            Constants::TEMPLATE => $this->getTemplateMessage(),
            Constants::SOURCE   => self::ONBOARDING_SOURCE,
            Constants::PARAMS   => [
                Constants::MERCHANT_NAME => $merchant->getName(),
                Constants::DASHBOARD_URL => $this->app[Constants::CONFIG]->get(Constants::APPLICATIONS_DASHBOARD_URL)
            ]
        ];

        $payload[Constants::PARAMS] = array_merge($payload[Constants::PARAMS], $this->args[Constants::PARAMS] ?? []);

        $orgId = $merchant->getMerchantOrgId();

        // appending orgId in stork context to be used on stork to select org specific sms gateway.
        if (empty($orgId) === false)
        {
            $payload['stork']['context']['org_id'] = $orgId;
        }

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
