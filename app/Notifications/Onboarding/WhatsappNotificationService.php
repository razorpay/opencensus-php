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
        $merchant = $this->args[Constants::MERCHANT];

        $templateName = self::ONBOARDING_PREFIX . strtolower($this->event);

        $payload = [
            Constants::OWNER_ID      => $merchant->getMerchantId(),
            Constants::OWNER_TYPE    => Constants::MERCHANT,
            Constants::TEMPLATE_NAME => $templateName,
            Constants::PARAMS        => [
                Constants::MERCHANT_NAME => $merchant->getName(),
                Constants::DASHBOARD_URL => $this->app[Constants::CONFIG]->get(Constants::APPLICATIONS_DASHBOARD_URL)
            ]
        ];

        $payload[Constants::PARAMS] = array_merge($payload[Constants::PARAMS], $this->args[Constants::PARAMS] ?? []);

        return $payload;
    }

    private function getTemplateMessage()
    {
        if (isset(Events::WHATSAPP_TEMPLATES[$this->event]) === true)
        {
            return Events::WHATSAPP_TEMPLATES[$this->event];
        }
        else
        {
            $template = Events::WHATSAPP_TEMPLATES_NEW[$this->event];

            return view($template, $this->getPayload()[Constants::PARAMS])->render();
        }
    }

    private function getPhone()
    {
        $merchant = $this->args[Constants::MERCHANT];

        return $merchant->merchantDetail->getContactMobile();
    }
}
