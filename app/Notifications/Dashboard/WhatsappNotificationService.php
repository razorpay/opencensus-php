<?php


namespace RZP\Notifications\Dashboard;

use RZP\Models\User;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Notifications\BaseNotificationService;
use RZP\Models\Merchant\Entity as MerchantEntity;

class WhatsappNotificationService extends BaseNotificationService
{

    public function send(): void
    {
        $merchant = $this->args[Constants::MERCHANT];

        $recipients = $this->getRecipients($merchant);

        $recipients = array_unique($recipients);

        $payload = $this->getPayload();

        if (empty($recipients) === true)
        {
            return;
        }

        try
        {
            foreach ($recipients as $recipient)
            {
                $receiver = $recipient;

                if (empty($receiver) === true)
                {
                    continue;
                }

                $this->app['stork_service']->sendWhatsappMessage(
                    $this->mode,
                    $this->getTemplateMessage(),
                    $receiver,
                    $payload
                );

                $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_VIA_WHATSAPP_SENT, [
                        Events::EVENT => $this->event,
                    ]
                );
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL,
                TraceCode::SEND_MERCHANT_WHATSAPP_NOTIFICATION_FAILED, [
                    Events::EVENT => $this->event,
                ]
            );
        }
    }

    protected function getPayload()
    {
        $merchant = $this->args[Constants::MERCHANT];

        $templateName = Events::WHATSAPP_TEMPLATES[$this->event];

        $payload = [
            Constants::OWNER_ID               => $merchant->getMerchantId(),
            Constants::OWNER_TYPE             => Constants::MERCHANT,
            Constants::WHATSAPP_TEMPLATE_NAME => $templateName,
            Constants::PARAMS                 => [
                Constants::MERCHANT_NAME => $merchant->getName(),
            ]
        ];

        $payload[Constants::PARAMS] = array_merge($payload[Constants::PARAMS], $this->args[Constants::PARAMS] ?? []);

        $allowedKeys = Events::WHATSAPP_TEMPLATE_KEYS[$this->event] ?? [];

        $payload[Constants::PARAMS] = array_only($payload[Constants::PARAMS], $allowedKeys);

        if (isset($this->args[Constants::IS_CTA_TEMPLATE]) === true)
        {
            $payload[Constants::IS_CTA_TEMPLATE] = true;

            $payload[Constants::BUTTON_URL_PARAM] = $this->args[Constants::BUTTON_URL_PARAM];
        }

        if (isset(Events::WHATSAPP_TEMPLATE_MEDIA_URL_PATH[$this->event]) === true)
        {
            $publicFileUrlConfigPath = Events::WHATSAPP_TEMPLATE_MEDIA_URL_PATH[$this->event];
            $payload[Constants::PUBLIC_FILE_URL] = app('config')->get($publicFileUrlConfigPath);
            $payload[Constants::MSG_TYPE] = 'IMAGE';
            $payload[Constants::DISPLAY_NAME] = $this->event;
            $payload[Constants::EXTENSION] = 'png';
            $payload[Constants::IS_CTA_TEMPLATE] = isset($payload[Constants::IS_CTA_TEMPLATE]) ? $payload[Constants::IS_CTA_TEMPLATE] : false;
        }

        return $payload;
    }

    private function getTemplateMessage()
    {
        $template = Events::WHATSAPP_TEMPLATES[$this->event];

        return view($template, $this->getPayload()[Constants::PARAMS])->render();
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
