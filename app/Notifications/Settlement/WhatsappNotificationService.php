<?php

namespace RZP\Notifications\Settlement;

use Carbon\Carbon;
use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Notifications\BaseNotificationService;

class WhatsappNotificationService extends BaseNotificationService
{
    protected const SETTLEMENT_PREFIX = 'settlement.';

    public function send(): void
    {
        $payload = $this->getPayload();
        $merchant = $this->args['merchant'];
        $settlement = $this->args['settlement'];

        try
        {
            $validity = $this->canSend();

            if($validity['can_send'] === true)
            {
                (new Stork)->sendWhatsappMessage(
                    $this->mode,
                    $this->getTemplateMessage(),
                    $this->getPhone(),
                    $payload
                );
            }
            else
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_NOTIFICATION_SKIPPED,
                    [
                        'notification_mode' => 'whatsapp',
                        'merchant_id'       => $merchant->getId(),
                        'settlement_id'     => $settlement->getId(),
                        'reason'            => $validity['reason'],
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SETTLEMENT_NOTIFICATION_FAILED,
                [
                    'merchant_id'       => $merchant->getId(),
                    'settlement_id'     => $settlement->getId(),
                    'notification_mode' => 'whatsapp',
                    'settlement_status' => $settlement->getStatus(),
                ]
            );
        }
    }

    private function canSend()
    {
        $merchant = $this->args['merchant'];
        $settlement = $this->args['settlement'];

        $notificationMerchant = ($merchant->isLinkedAccount() === true) ? $merchant->parent : $merchant;

        $isWhatsappEnabled = (new Merchant\Core())->isRazorxExperimentEnable($notificationMerchant->getId(),
            RazorxTreatment::WHATSAPP_NOTIFICATIONS_SETTLEMENTS);

        if($isWhatsappEnabled !== true)
        {
            return [
                'can_send'  => false,
                'reason'    => 'merchant is not enabled via whatsapp experiment'
            ];
        }

        if(empty($this->getPhone()) === true)
        {
            return [
                'can_send'  => false,
                'reason'    => 'no contact no. associated with merchant account'
            ];
        }

        if($this->event === Events::FAILED)
        {
            $failureReason = $settlement->getRemarks();
            if (empty($failureReason) === true)
            {
                return [
                    'can_send'  => false,
                    'reason'    => 'no failure reason provided'
                ];
            }
        }

        return [
            'can_send'  => true
        ];
    }

    protected function getPayload()
    {
        $merchant = $this->args['merchant'];
        $settlement = $this->args['settlement'];

        $templateName = self::SETTLEMENT_PREFIX . strtolower($this->event);

        $payload = [
            'ownerId'       => $merchant->getId(),
            'ownerType'     => Merchant\Constants::MERCHANT,
            'template'      => $this->getTemplateMessage(),
            'template_name' => $templateName,
            'receiver'      => $this->getPhone(),
            'source'        => 'api.'. $this->mode . '.settlements',
            'params'        => [
                'merchant_id'     => $merchant->getId(),
                'bank_account_id' => $this->args['bankAccountNumber'],
                'settlement_id'   => $settlement->getPublicId(),
                'date'            => Carbon::now(timezone::IST)->format('j M Y, g A'),
                'url'             => $this->app['config']->get('applications.dashboard.url'),
            ]
        ];

        if($this->event === Events::PROCESSED)
        {
            $payload['params']['utr']    = $settlement->getUtr();
            $payload['params']['amount'] = 'Rs.'.$settlement->getAmount()/100;
        }
        else
        {
            $failureReason = $settlement->getRemarks();
            $payload['params']['failure_reason'] = $failureReason;
        }

        return $payload;
    }

    private function getTemplateMessage()
    {
        return Events::WHATSAPP_TEMPLATES[$this->event];
    }

    protected function getPhone()
    {
        $merchant = $this->args['merchant'];

        if ($merchant->isLinkedAccount() === true)
        {
            return $merchant->parent->merchantDetail->getContactMobile();
        }

        return $merchant->merchantDetail->getContactMobile();
    }
}
