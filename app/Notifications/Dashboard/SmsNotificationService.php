<?php


namespace RZP\Notifications\Dashboard;

use RZP\Models\User;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Constants;
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

                $payload[Constants::RECEIVER] = $recipient;

                if ((empty($payload[Constants::RECEIVER]) === true))
                {
                    continue;
                }

                $this->app->raven->sendSms($payload, false);

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

    protected function getPayload()
    {
        $merchant = $this->args[Constants::MERCHANT];

        $payload = [
            Constants::TEMPLATE => $this->getTemplateMessage(),
            Constants::SOURCE   => self::DASHBOARD_SOURCE,
            Constants::PARAMS   => [
                Constants::MERCHANT_NAME => $merchant->getName(),
            ]
        ];

        $payload[Constants::PARAMS] = array_merge($payload[Constants::PARAMS], $this->args[Constants::PARAMS] ?? []);

        $allowedKeys = Events::SMS_TEMPLATE_KEYS[$this->event] ?? [];

        $payload[Constants::PARAMS] = array_only($payload[Constants::PARAMS], $allowedKeys);

        return $payload;
    }

    private function getTemplateMessage()
    {
        return Events::SMS_TEMPLATES[$this->event];
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
