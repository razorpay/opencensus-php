<?php

namespace RZP\Models\Partner;

use Mail;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner\Constants as Constants;
use RZP\Mail\Merchant\ResellerToPurePlatformPartnerSwitchEmail;

class NotifyPartnerAboutPartnerTypeSwitch extends Core
{
    public function __construct(Entity $partner)
    {
        parent::__construct();

        $this->partner = $partner;
    }

    public function notify()
    {
        $this->sendEmailToPartnerAboutSwitch();
        $this->sendSMSToPartnerAboutSwitch();
    }

    private function sendEmailToPartnerAboutSwitch()
    {
        $data = [
            'merchant'      => $this->partner->toArray(),
            'view'          => Constants::RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_EMAIL_TEMPLATE,
            'country_code'  => $this->partner->getCountry()
        ];

        $this->trace->info(
            TraceCode::SEND_RESELLER_TO_PURE_PLATFORM_EMAIL,
            [
                'merchant_id'   => $data['merchant']['id'],
                'merchant_name' => $data['merchant']['name']
            ]
        );

        $resellerToPurePlatformPartnerSwitchEmail = new ResellerToPurePlatformPartnerSwitchEmail($data);

        Mail::send($resellerToPurePlatformPartnerSwitchEmail);
    }

    private function sendSMSToPartnerAboutSwitch()
    {
        $contentParams = [
            'partnerName'           => $this->partner->getName(),
            'platformDocsLink'      => $this->elfin->shorten(Constants::PURE_PLATFORM_DOCS_LINK),
            'partnerSupportEmail'   => Constants::PARTNER_SUPPORT_EMAIL
        ];

        $smsPayload = [
            'language'          => 'english',
            'ownerType'         => 'merchant',
            'templateNamespace' => 'partnerships-experience',
            'destination'       => $this->partner->merchantDetail->getContactMobile(),
            'orgId'             => $this->partner->getOrgId(),
            'ownerId'           => $this->partner->getId(),
            'contentParams'     => $contentParams,
            'sender'            => 'RZRPAY',
            'templateName'      => Constants::RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_SMS_TEMPLATE
        ];

        $tracePayload = [
            'partner_id'          => $this->partner->getId(),
            'templateName'        => Constants::RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_SMS_TEMPLATE
        ];
        $traceCode      = TraceCode::SEND_RESELLER_TO_PURE_PLATFORM_SMS;
        $errorTraceCode = TraceCode::RESELLER_TO_PURE_PLATFORM_SMS_FAILED;

        try
        {
            $this->app->stork_service->sendSms($this->mode, $smsPayload);

            $this->trace->info($traceCode, $tracePayload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, $errorTraceCode, $tracePayload);
        }
    }
}
