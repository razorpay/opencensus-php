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

        $users = $this->getRecipients($merchant);

        if (empty($users) === true)
        {
            return;
        }

        try
        {
            foreach ($users as $user)
            {
                $payload = $this->getPayload();

                $payload[Constants::RECEIVER] = $user[User\Constants::CONTACT_MOBILE];

                if ((empty($payload[Constants::RECEIVER]) === true) or
                    ($user[User\Entity::CONTACT_MOBILE_VERIFIED] === false))
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

        $allowedKeys = Events::WHATSAPP_TEMPLATE_KEYS[$this->event] ?? [];

        $payload[Constants::PARAMS] = array_only($payload[Constants::PARAMS], $allowedKeys);

        return $payload;
    }

    private function getTemplateMessage()
    {
        return Events::SMS_TEMPLATES[$this->event];
    }

    private function getRecipients(MerchantEntity $merchant)
    {
        $users = $merchant->users()
                          ->whereIn(UserEntity::ROLE, Events::RECIPIENT_ROLES[$this->event])
                          ->get()
                          ->toArray();

        return $users;
    }
}
