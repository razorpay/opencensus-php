<?php

namespace RZP\Notifications\Onboarding;

use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\Constants;
use RZP\Notifications\BaseNotificationService;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Notifications\Onboarding\Constants as OnboardingConstants;

class WhatsappNotificationService extends BaseNotificationService
{
    protected const ONBOARDING_PREFIX = 'onboarding.';

    public function send(): void
    {
        $isExperimentEnabled = true;

        $merchantCore = new Core();
        //use the experiment if we need to block specific whatsapp templates
        if (isset(Events::WHATSAPP_TEMPLATES_NEW_EXPERIMENTS[$this->event]) === true)
        {
            $experiment = Events::WHATSAPP_TEMPLATES_NEW_EXPERIMENTS[$this->event];

            $merchant = $this->args[Constants::MERCHANT];

            $isExperimentEnabled = $merchantCore->isRazorxExperimentEnable($merchant->getMerchantId(), $experiment);
        }
        if (isset(Events::WHATSAPP_TEMPLATES_SPLITZ_EXPERIMENTS[$this->event]) === true)
        {
            $experimentKey = Events::WHATSAPP_TEMPLATES_SPLITZ_EXPERIMENTS[$this->event];
            $merchant      = $this->args[Constants::MERCHANT];

            $properties = [
                'id'            => $merchant->getId(),
                'experiment_id' => $this->app['config']->get('app.' . $experimentKey),
            ];

            $isExperimentEnabled = $merchantCore->isSplitzExperimentEnable($properties, 'enable');
        }

        if ($isExperimentEnabled === true)
        {

            $templateMessage = $this->getTemplateMessage();
            $payload         = $this->getPayload();

            if (strpos($this->event, Events::PARTNER_EVENTS_PREFIX) === 0)
            {
                // Send to partners
                $merchant = $this->args[Constants::MERCHANT];

                // Note: setting partner in the args sends the notification exclusively to the passed $partner.
                $partner = $this->args[Constants::PARTNER] ?? null;

                if (isset($partner) === true)
                {
                    $partners = [$partner];
                }
                else
                {
                    $accessMaps = $this->app['repo']->merchant_access_map->fetchAffiliatedPartnersForSubmerchant($merchant->getId());

                    $accessMaps = $accessMaps->filter(function($value, $key) {
                        return ($value->entityOwner->isNonPurePlatformPartner() === true);
                    })->unique(function($item) {
                        return $item->entityOwner->getId();
                    });

                    $partners = $accessMaps->map(function($item) {
                        return $item->entityOwner;
                    });

                    if (strpos($this->event, Events::PARTNER_SUBMERCHANT_EVENTS_PREFIX) === 0)
                    {
                        // Filter partners with kyc access
                        $partners = $accessMaps->filter(function($value, $key) {
                            return ($value->hasKycAccess() === true);
                        })->map(function($item) {
                            return $item->entityOwner;
                        });
                    }

                    if ($partners->isEmpty() === true)
                    {
                        return;
                    }
                }

                foreach ($partners as $partner)
                {
                    $notificationBlocked = $partner->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM);

                    $partnerContactMobile = $partner->merchantDetail ? $partner->merchantDetail->getContactMobile() : null;

                    if ($notificationBlocked === true || empty($partnerContactMobile) === true)
                    {
                        continue;
                    }

                    $response = $this->app['stork_service']->sendWhatsappMessage(
                        $this->mode,
                        $templateMessage,
                        $partnerContactMobile,
                        $payload
                    );

                    $this->trace->info(
                        TraceCode::MERCHANT_ONBOARDING_WHATSAPP_SENT,
                        [
                            'merchantId' => $merchant->getMerchantId(),
                            'template'    => $this->getTemplateMessage(),
                            'response'    => $response
                        ]);
                }
            }
            else
            {
                // Send to submerchant
                $response = $this->app['stork_service']->sendWhatsappMessage(
                    $this->mode,
                    $templateMessage,
                    $this->getPhone(),
                    $payload
                );
            }
        }
    }

    protected function getPayload()
    {
        $merchant = $this->args[Constants::MERCHANT];

        $templateName = $this->getTemplateName();

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
            $payload[Constants::IS_CTA_TEMPLATE] = true;

            $payload[Constants::BUTTON_URL_PARAM] = Events::WHATSAPP_TEMPLATES_CTA_TEMPLATE[$this->event];
        }

        return $payload;
    }

    private function getOrg($merchant)
    {
        $org = $merchant->org ?: $this->app[Constants::REPO]->org->getRazorpayOrg();

        return $org;
    }

    private function getTemplateName()
    {
        if (isset(Events::WHATSAPP_TEMPLATE_NAMES[$this->event]) === true)
        {
            return Events::WHATSAPP_TEMPLATE_NAMES[$this->event];
        }

        return self::ONBOARDING_PREFIX . strtolower($this->event);
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
