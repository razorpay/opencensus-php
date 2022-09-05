<?php


namespace RZP\Notifications\Onboarding;

use RZP\Services\Stork;
use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\Constants;
use RZP\Notifications\BaseNotificationService;

class WhatsappNotificationService extends BaseNotificationService
{
    protected const ONBOARDING_PREFIX = 'onboarding.';

    public function send(): void
    {
        $isExperimentEnabled = true;

        //use the experiment if we need to block specific whatsapp templates
        if (isset(Events::WHATSAPP_TEMPLATES_NEW_EXPERIMENTS[$this->event]) === true)
        {
            $experiment = Events::WHATSAPP_TEMPLATES_NEW_EXPERIMENTS[$this->event];

            $merchant = $this->args[Constants::MERCHANT];

            $isExperimentEnabled = (new Core)->isRazorxExperimentEnable($merchant->getMerchantId(), $experiment);
        }

        if ($isExperimentEnabled === true)
        {
            (new Stork)->sendWhatsappMessage(
                $this->mode,
                $this->getTemplateMessage(),
                $this->getPhone(),
                $this->getPayload()
            );
        }
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

        if (array_key_exists($this->event, Events::WHATSAPP_TEMPLATES_CTA_TEMPLATE) === true)
        {
            $args[Constants::IS_CTA_TEMPLATE] = true;

            $args[Constants::BUTTON_URL_PARAM] = Events::WHATSAPP_TEMPLATES_CTA_TEMPLATE[$this->event];
        }

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
