<?php


namespace RZP\Notifications\Dashboard;

use RZP\Models\User;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\User\Entity as UserEntity;
use RZP\Notifications\BaseNotificationService;
use RZP\Models\Merchant\Entity as MerchantEntity;


class SmsNotificationService extends BaseNotificationService
{
    const DASHBOARD_SOURCE = 'api.merchant.dashboard';

    public function send(): void
    {
        $merchant = $this->args[Constants::MERCHANT];

        $recipients = $this->getRecipients($merchant);

        $recipients = array_unique($recipients);

        if (empty($recipients) === true)
        {
            return;
        }

        try
        {
            foreach ($recipients as $recipient)
            {
                $payload = $this->getPayload();

                $payload[Constants::DESTINATION] = $recipient;

                if (empty($payload[Constants::DESTINATION]) === true)
                {
                    return;
                }

                $this->app['stork_service']->sendSms(
                    $this->mode,
                    $payload
                );

                $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_VIA_SMS_SENT, [
                        Events::EVENT => $this->event,
                    ]
                );
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL,
                TraceCode::SEND_MERCHANT_SMS_NOTIFICATION_FAILED, [
                    Events::EVENT => $this->event,
                ]
            );
        }
    }

    private function getTemplateMessage()
    {
        return Events::SMS_TEMPLATES[$this->event];
    }

    protected function getPayload()
    {
        $merchant = $this->args[Constants::MERCHANT];

        $merchantId = $merchant->getMerchantId();

        $orgId = $merchant->getOrgId();

        $templateName = $this->getTemplateMessage();

        $payload = [
            Constants::OWNER_ID                    => $merchantId,
            Constants::OWNER_TYPE                  => Constants::MERCHANT,
            Constants::SENDER                      => Constants::RZRPAY,
            Constants::SMS_TEMPLATE_NAME           => $templateName,
            Constants::TEMPLATE_NAMESPACE          => Constants::PAYMENTS_DASHBOARD,
            Constants::LANGUAGE                    => Constants::ENGLISH,
            Constants::ORG_ID                      => $orgId,
            Constants::CONTENT_PARAMS              => [
                Constants::MERCHANT_NAME   => $merchant->getName(),
            ],
            Constants::DELIVERY_CALLBACK_REQUESTED => true
        ];

        $allowedKeys = Events::SMS_TEMPLATE_KEYS[$this->event] ?? [];

        $payload[Constants::CONTENT_PARAMS] = array_merge($payload[Constants::CONTENT_PARAMS], $this->args[Constants::PARAMS] ?? []);

        $payload[Constants::CONTENT_PARAMS] = empty($allowedKeys) ? null : array_only($payload[Constants::CONTENT_PARAMS], $allowedKeys);

        return $payload;
    }

    private function getRecipients(MerchantEntity $merchant)
    {
        $recipients = $merchant->users()
                               ->whereIn(UserEntity::ROLE, Events::RECIPIENT_ROLES[$this->event])
                               ->where(UserEntity::CONTACT_MOBILE_VERIFIED, 1)
                               ->get()
                               ->pluck(UserEntity::CONTACT_MOBILE)
                               ->toArray();

        return $recipients;
    }
}
