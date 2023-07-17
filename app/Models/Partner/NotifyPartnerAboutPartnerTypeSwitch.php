<?php

namespace RZP\Models\Partner;

use Mail;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner\Constants as Constants;
use RZP\Mail\Merchant\PartnerTypeSwitchEmail;

class NotifyPartnerAboutPartnerTypeSwitch extends Core
{
    private Entity $partner;
    private string $oldType;
    private string $newType;
    private string $switchType;

    public function __construct(Entity $partner, string $oldType, string $newType)
    {
        parent::__construct();

        $this->partner = $partner;
        $this->oldType = $oldType;
        $this->newType = $newType;

        $this->switchType = $oldType . '_to_' . $newType;
    }

    public function notify()
    {
        $this->sendEmailToPartnerAboutSwitch();
        $this->sendSMSToPartnerAboutSwitch();
    }

    private function sendEmailToPartnerAboutSwitch(): void
    {
        $data = [
            'merchant'      => $this->partner->toArray(),
            'view'          => Constants::PARTNER_TYPE_SWITCH_TEMPLATES[$this->switchType]['email'],
            'country_code'  => $this->partner->getCountry(),
            'subject'       => Constants::PARTNER_TYPE_SWITCH_TEMPLATES[$this->switchType]['subject']
        ];

        $this->trace->info(
            TraceCode::SEND_PARTNER_TYPE_SWITCH_EMAIL,
            [
                'merchant_id'   => $data['merchant']['id'],
                'merchant_name' => $data['merchant']['name'],
                'old_type'      => $this->oldType,
                'new_type'      => $this->newType
            ]
        );

        $partnerTypeSwitchEmail = new PartnerTypeSwitchEmail($data);

        Mail::send($partnerTypeSwitchEmail);
    }

    private function sendSMSToPartnerAboutSwitch(): void
    {
        $contentParams = [
            'partnerName'           => $this->partner->getName(),
            'platformDocsLink'      => $this->elfin->shorten(
                Constants::PARTNER_TYPE_SWITCH_TEMPLATES[$this->switchType]['docs_link']
            ),
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
            'templateName'      => Constants::PARTNER_TYPE_SWITCH_TEMPLATES[$this->switchType]['sms']
        ];

        $tracePayload = [
            'partner_id'          => $this->partner->getId(),
            'templateName'        => Constants::PARTNER_TYPE_SWITCH_TEMPLATES[$this->switchType]['sms']
        ];
        $traceCode      = TraceCode::SEND_PARTNER_TYPE_SWITCH_SMS;
        $errorTraceCode = TraceCode::PARTNER_TYPE_SWITCH_SMS_FAILED;

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
