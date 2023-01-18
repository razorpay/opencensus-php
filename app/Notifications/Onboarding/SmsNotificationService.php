<?php

namespace RZP\Notifications\Onboarding;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Core;
use RZP\Notifications\BaseNotificationService;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Notifications\Onboarding\Constants as OnboardingConstants;

class SmsNotificationService extends BaseNotificationService
{
    const ONBOARDING_SOURCE = 'api.merchant.onboarding';

    public function send(): void
    {
        $payload      = $this->getPayload();
        $merchant     = $this->args[Constants::MERCHANT];
        $merchantCore = new Core();

        $isExperimentEnabled = true;

        if (isset(Events::SMS_TEMPLATES_RAZORX_EXPERIMENTS[$this->event]) === true)
        {
            $experiment = Events::SMS_TEMPLATES_RAZORX_EXPERIMENTS[$this->event];

            $merchant = $this->args[Constants::MERCHANT];

            $isExperimentEnabled = $merchantCore->isRazorxExperimentEnable($merchant->getMerchantId(), $experiment);
        }
        if (isset(Events::SMS_TEMPLATES_SPLITZ_EXPERIMENTS[$this->event]) === true)
        {
            $experimentKey = Events::SMS_TEMPLATES_SPLITZ_EXPERIMENTS[$this->event];

            $properties = [
                'id'            => $merchant->getId(),
                'experiment_id' => $this->app['config']->get('app.'.$experimentKey),
            ];

            $isExperimentEnabled = $merchantCore->isSplitzExperimentEnable($properties, 'enable');
        }

        if ($isExperimentEnabled === false)
        {
            return;
        }

        if (strpos($this->event, Events::PARTNER_EVENTS_PREFIX) === 0)
        {
            $this->sendToPartners($payload);
        }
        else
        {
            $this->sendToSubmerchant($payload, $merchant);
        }
    }

    protected function sendToSubmerchant($payload, $merchant)
    {
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
                ]
            );
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_ONBOARDING_SMS_FAILED,
                [
                    'mid'      => $merchant->getMerchantId(),
                    'template' => $this->getTemplateMessage(),
                ]
            );
        }
    }

    protected function sendToPartners($payload)
    {
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

            // mutate payload destination for each partner
            $payload[OnboardingConstants::DESTINATION] = $partnerContactMobile;

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
                    ]
                );
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::MERCHANT_ONBOARDING_SMS_FAILED,
                    [
                        'mid'      => $merchant->getMerchantId(),
                        'template' => $this->getTemplateMessage(),
                    ]
                );
            }
        }
    }

    protected function getPayload()
    {
        $merchant = $this->args[Constants::MERCHANT];

        $merchantId = $merchant->getMerchantId();

        $orgId = $merchant->getOrgId();

        $templateName = $this->getTemplateMessage();

        $templateNamespace = Events::SMS_TEMPLATES_CUSTOM_NAMESPACES[$this->event] ?? OnboardingConstants::PAYMENTS_ONBOARDING;

        $payload = [
            OnboardingConstants::OWNER_ID                    => $merchantId,
            OnboardingConstants::OWNER_TYPE                  => OnboardingConstants::MERCHANT,
            OnboardingConstants::ORG_ID                      => $orgId,
            OnboardingConstants::SENDER                      => OnboardingConstants::RZRPAY,
            OnboardingConstants::DESTINATION                 => $this->getPhone(),
            OnboardingConstants::SMS_TEMPLATE_NAME           => $templateName,
            OnboardingConstants::TEMPLATE_NAMESPACE          => $templateNamespace,
            OnboardingConstants::LANGUAGE                    => OnboardingConstants::ENGLISH,
            OnboardingConstants::CONTENT_PARAMS              => [
                Constants::MERCHANT_NAME => $merchant->getName(),
                Constants::DASHBOARD_URL => $this->app[Constants::CONFIG]->get(Constants::APPLICATIONS_DASHBOARD_URL)
            ],
            OnboardingConstants::DELIVERY_CALLBACK_REQUESTED => true
        ];

        $payload[Constants::PARAMS]                   = array_merge($payload[OnboardingConstants::CONTENT_PARAMS], $this->args[Constants::PARAMS] ?? []);
        $payload[OnboardingConstants::CONTENT_PARAMS] = $payload[Constants::PARAMS];

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
