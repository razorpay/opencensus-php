<?php

namespace RZP\Notifications\Support;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Notifications\BaseNotificationService;

class WhatsappNotificationService extends BaseNotificationService
{
    protected const SUPPORT_PREFIX = 'support.';

    public function send(): void
    {
        try
        {
            $payload = $this->getPayload();

            if ($this->canSend() === false)
            {
                return;
            }

            $this->app['stork_service']->sendWhatsappMessage(
                Mode::LIVE,
                $this->getTemplateMessage(),
                $this->getPhone(),
                $payload
            );
        }
        catch (\Throwable $throwable)
        {
            $this->trace->traceException($throwable);
        }
    }

    protected function canSend()
    {
        $merchant = $this->getMerchant();

        $isExperimentEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::WHATSAPP_SUPPORT_NOTIFICATIONS);

        $isEmptyPhone = empty($this->getPhone()) === true;

        $isRequesterItemEligibleForNotification = in_array(trim($this->args['ticketNewRequesterItem']), Merchant\FreshdeskTicket\Constants::TICKET_NEW_REQUESTER_ITEMS_FOR_WA_NOTIFICATION);

        $canSend = ($isExperimentEnabled === true) && ($isEmptyPhone === false) && ($isRequesterItemEligibleForNotification === true);

        $traceData = [
            'new_requester_item_eligible' => $isRequesterItemEligibleForNotification,
            'variant'                     => $isExperimentEnabled,
            'empty_mobile'                => $isEmptyPhone,
            'can_send'                    => $canSend
        ];

        $this->trace->info(TraceCode::SUPPORT_NOTIFICATION_ELIGIBILITY, $traceData);

        return $canSend;
    }

    protected function getPayload()
    {
        $merchant = $this->getMerchant();

        $templateName = self::SUPPORT_PREFIX . strtolower($this->event);

        $payload = [
            'ownerId'       => $merchant->getId(),
            'ownerType'     => Merchant\Constants::MERCHANT,
            'template'      => $this->getTemplateMessage(),
            'template_name' => $templateName,
            'receiver'      => $this->getPhone(),
            'source'        => 'api.' . $this->mode . '.support',
            'params'        => [
                'merchant_id' => $merchant->getId(),
                'url'         => $this->app['config']->get('applications.dashboard.url'),
                'ticket_id'   => $this->args['ticket']->getTicketId(),
            ],
        ];

        return $payload;
    }

    protected function getTemplateMessage()
    {
        return implode(PHP_EOL, Events::WHATSAPP_TEMPLATES[$this->event]);
    }

    protected function getPhone()
    {
        $merchant = $this->getMerchant();

        return $merchant->merchantDetail->getContactMobile();
    }

    protected function getMerchant()
    {
        return $this->args['ticket']->merchant;
    }
}
