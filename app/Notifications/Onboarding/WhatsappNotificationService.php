<?php


namespace RZP\Notifications\Onboarding;

use RZP\Services\Stork;
use RZP\Models\Merchant\Constants;
use RZP\Notifications\BaseNotificationService;

class WhatsappNotificationService extends BaseNotificationService
{
    protected const ONBOARDING_PREFIX = 'onboarding.';

    public function send(): void
    {
        (new Stork)->sendWhatsappMessage(
            $this->mode,
            $this->getTemplateMessage(),
            $this->getPhone(),
            $this->getPayload()
        );
    }

    protected function getPayload()
    {
        $merchant = $this->args['merchant'];

        $templateName = self::ONBOARDING_PREFIX . strtolower($this->event);

        return [
            'ownerId'       => $merchant->getMerchantId(),
            'ownerType'     => Constants::MERCHANT,
            'template_name' => $templateName,
            'params'        => [
                'merchantName' => $merchant->getName(),
                'dashboardUrl' => $this->app['config']->get('applications.dashboard.url')
            ]
        ];
    }

    private function getTemplateMessage()
    {
        return Events::WHATSAPP_TEMPLATES[$this->event];
    }

    private function getPhone()
    {
        $merchant = $this->args['merchant'];
        return $merchant->merchantDetail->getContactMobile();
    }
}
