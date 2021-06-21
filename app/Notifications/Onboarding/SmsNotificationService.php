<?php


namespace RZP\Notifications\Onboarding;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Notifications\BaseNotificationService;

class SmsNotificationService extends BaseNotificationService
{
    const ONBOARDING_SOURCE = 'api.merchant.onboarding';

    public function send(): void
    {
        $payload = $this->getPayload();
        $merchant = $this->args['merchant'];

        try {
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
        $merchant = $this->args['merchant'];

        $payload = [
            'receiver' => $this->getPhone(),
            'template' => $this->getTemplateMessage(),
            'source'   => self::ONBOARDING_SOURCE,
            'params'   => [
                'merchantName' => $merchant->getName(),
                'dashboardUrl' => $this->app['config']->get('applications.dashboard.url')
            ]
        ];

        $payload['params'] = array_merge($payload['params'], $this->args['params'] ?? []);
        return $payload;
    }

    private function getPhone()
    {
        $merchant = $this->args['merchant'];
        return $merchant->merchantDetail->getContactMobile();
    }

    private function getTemplateMessage()
    {
        return Events::SMS_TEMPLATES[$this->event];
    }
}
