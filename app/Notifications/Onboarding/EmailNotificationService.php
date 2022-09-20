<?php


namespace RZP\Notifications\Onboarding;

use Mail;
use RZP\Mail\Merchant\PartnerSubmerchantOnboardingEmail;
use RZP\Models\Merchant\AutoKyc\Escalations\Types\Email;
use RZP\Models\Merchant\Constants;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Entity as MerchantEntity;

use RZP\Models\Merchant\Detail\Entity as DEEntity;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Notifications\BaseNotificationService;

class EmailNotificationService extends BaseNotificationService
{
    const ONBOARDING_SOURCE = 'api.merchant.onboarding';

    public function send(): void
    {
        if (strpos($this->event, Events::PARTNER_EVENTS_PREFIX) === 0)
        {
            $this->sendToPartner();
        }
        else
        {
            $this->sendToMerchant();
        }
    }

    public function sendToPartner()
    {
        $merchant = $this->args[Constants::MERCHANT];
        $org      = $this->getOrg($merchant);

        $accessMaps = $this->app['repo']->merchant_access_map->fetchAffiliatedPartnersForSubmerchant($merchant->getId());
        $accessMaps = $accessMaps->filter(function ($value, $key) {
            return ($value->entityOwner->isNonPurePlatformPartner() === true);
        })->unique(function ($item) {return $item->entityOwner->getId();});

        $partners = $accessMaps->map(function ($item) {return $item->entityOwner;});

        if ($partners->isEmpty() === true) {
            return;
        }

        if (strpos($this->event, Events::PARTNER_SUBMERCHANT_EVENTS_PREFIX) === 0)
        {
            $partners = $accessMaps->filter(function ($value, $key) {
                return ($value->hasKycAccess() === true);
            })->map(function ($item) {return $item->entityOwner;});
        }

        if ($partners->isEmpty() === true) {
            return;
        }

        foreach ($partners as $partner)
        {
//            if (empty($partner->getEmail()) === true)
//            {
//                continue;
//            }
            $payload = [
                DEConstants::PARTNER  => [
                    MerchantEntity::ID    => $partner->getId(),
                    MerchantEntity::NAME  => $partner->getName(),
                    MerchantEntity::EMAIL => $partner->getEmail(),
                ],
                DEConstants::MERCHANT => [
                    MerchantEntity::ID    => $merchant->getId(),
                    MerchantEntity::NAME  => $merchant->getName(),
                    MerchantEntity::EMAIL => $merchant->getEmail(),
                ],
                DEConstants::ORG => $org->toArray(),
            ];

            try {
                $email = new PartnerSubmerchantOnboardingEmail($payload, $this->getTemplateMessage(), $this->getTemplateSubject());
                Mail::queue($email);

                $this->trace->info(
                    TraceCode::PARTNER_SUBMERCHANT_ONBOARDING_EMAIL_SENT,
                    [
                        'merchant_id' => $merchant->getMerchantId(),
                        'template'    => $this->getTemplateMessage(),
                        'payload'     => $payload
                    ]);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e,
                    Trace::CRITICAL,
                    TraceCode::PARTNER_SUBMERCHANT_ONBOARDING_EMAIL_FAILED,
                    [
                        'merchant_id' => $merchant->getMerchantId(),
                        'template'    => $this->getTemplateMessage()
                    ]
                );
            }
        }
    }

    public function sendToMerchant(): void
    {
        $payload  = $this->getMerchantEmailPayload();
        $merchant = $this->args[Constants::MERCHANT];
        $org      = $this->getOrg($merchant);

        if (empty($merchant->getEmail()) === true){
            return;
        }

        try
        {
            $emailInstance = new MerchantOnboardingEmail(
                $payload, $org->toArray(),
                $this->event,
                $this->getTemplateMessage(),
                $this->getTemplateSubject(),
                $this->files
            );

            Mail::queue($emailInstance);

            $this->trace->info(
                TraceCode::MERCHANT_ONBOARDING_EMAIL_SENT,
                [
                    'merchant_id' => $merchant->getMerchantId(),
                    'template'    => $this->getTemplateMessage(),
                    'payload'     => $payload
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::CRITICAL,
                                         TraceCode::MERCHANT_ONBOARDING_EMAIL_FAILED,
                                         [
                                             'merchant_id' => $merchant->getMerchantId(),
                                             'template'    => $this->getTemplateMessage()
                                         ]
            );
        }
    }

    private function getOrg($merchant)
    {
        $org = $merchant->org ?: $this->app[Constants::REPO]->org->getRazorpayOrg();

        return $org;
    }

    protected function getMerchantEmailPayload()
    {
        $merchant = $this->args[Constants::MERCHANT];
        $org      = $this->getOrg($merchant);
        $hostname = '';

        if (empty($org->hostnames()->first()) === false)
        {
            $hostname = $org->getPrimaryHostName();
        }
        $merchantDetails = $merchant->merchantDetail;

        $business_website=empty($merchantDetails->getAttribute(DEEntity::BUSINESS_WEBSITE))?null:$merchantDetails->getAttribute(DEEntity::BUSINESS_WEBSITE);

        $data = [
            DEConstants::MERCHANT => [
                MerchantEntity::NAME          => $merchant->getName(),
                MerchantEntity::BILLING_LABEL => $merchant->getBillingLabel(),
                MerchantEntity::EMAIL         => $merchant->getEmail(),
                DEConstants::ORG              => [
                    DEConstants::HOSTNAME => $hostname,
                ],
                DEEntity::BUSINESS_WEBSITE    => $business_website
            ],
        ];

        $extraData = $this->args[Constants::PARAMS] ?? [];

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
