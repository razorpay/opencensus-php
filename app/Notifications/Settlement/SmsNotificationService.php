<?php

namespace RZP\Notifications\Settlement;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Service;
use RZP\Notifications\BaseNotificationService;

class SmsNotificationService extends BaseNotificationService
{

    public function send(): void
    {
        $payload = $this->getPayload();
        $merchant = $this->args['merchant'];
        $settlement = $this->args['settlement'];

        try {
            $validity = $this->canSend();

            if($validity['can_send'] === true)
            {
                $this->app['raven']->sendSms($payload, true);
            }
            else
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_NOTIFICATION_SKIPPED,
                    [
                        'notification_mode' => 'sms',
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
                    'notification_mode' => 'sms',
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

        $status = (new Service)->getSettlementSmsNotificationStatus($notificationMerchant);

        if($status['enabled'] === false)
        {
            return [
                'can_send'  => false,
                'reason'    => 'merchant has settlement sms notify block feature'
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

        $payload = [
            'template' => $this->getTemplateMessage(),
            'receiver' => $this->getPhone(),
            'source'   => 'api.'. $this->mode . '.settlements',
            'params'   => [
                'merchant_id'     => $merchant->getId(),
                'bank_account_id' => $this->args['bankAccountNumber'],
                'settlement_id'   => $settlement->getPublicId(),
                'date'            => Carbon::now(timezone::IST)->format('j M Y, g A'),
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

        // appending orgId in stork context to be used on stork to select org specific sms gateway.
        $orgId = $merchant->getMerchantOrgId();

        if (empty($orgId) === false)
        {
            $payload['stork']['context']['org_id'] = $orgId;
        }

        return $payload;
    }

    private function getTemplateMessage()
    {
        return Events::SMS_TEMPLATES[$this->event];
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
