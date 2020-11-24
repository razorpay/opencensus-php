<?php


namespace RZP\Notifications\Onboarding;

use RZP\Services\Stork;
use RZP\Models\Merchant\Constants;
use RZP\Notifications\BaseNotificationService;

class WhatsappNotificationService extends BaseNotificationService
{
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
        return [
            'ownerId'   => $merchant->getMerchantId(),
            'ownerType' => Constants::MERCHANT,
            'params'    => [
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
