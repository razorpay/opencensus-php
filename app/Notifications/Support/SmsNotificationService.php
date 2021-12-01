<?php

namespace RZP\Notifications\Support;

use RZP\Models\Merchant\RazorxTreatment;
use RZP\Notifications\BaseNotificationService;
use RZP\Notifications\Support\Events;
use RZP\Models\Merchant;

class SmsNotificationService extends BaseNotificationService
{
    public function send(): void
    {
        $payload = $this->getPayload();

        try {

            if($this->canSend() === true)
            {
                $this->app['raven']->sendSms($payload, true);
            }
            else
            {
                return;
            }
        }
        catch (\Throwable $throwable)
        {
            $this->trace->traceException($throwable);
        }
    }

    private function canSend()
    {
        $merchant = $this->getMerchant();

        $canSend = true;

        $isExperimentEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::SMS_SUPPORT_NOTIFICATIONS);

        if((empty($this->getPhone()) === true) || ($isExperimentEnabled === false))
        {
            $canSend = false;
        }

        return $canSend;
    }

    protected function getPayload()
    {
        $merchant = $this->getMerchant();

        $payload = [
            'template' => $this->getTemplateMessage(),
            'receiver' => $this->getPhone(),
            'source'        => 'api.' . $this->mode . '.support',
            'params'        => [
                'merchant_id' => $merchant->getId(),
                'url'         => $this->app['config']->get('applications.dashboard.url'),
                'ticket_id'   => $this->args['ticket']->getTicketId(),
            ],
        ];

        return $payload;
    }

    private function getTemplateMessage()
    {
        return Events::SMS_TEMPLATES[$this->event];
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
