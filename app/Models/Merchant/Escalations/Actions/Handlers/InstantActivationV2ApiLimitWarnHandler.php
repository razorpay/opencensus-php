<?php

namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

use RZP\Exception;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant\Escalations\Actions\Entity;
use RZP\Trace\TraceCode;

class InstantActivationV2ApiLimitWarnHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        try {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $data = $this->getInstantActivationLimitWarnData($merchantId, $params);

            $eventPayload = [
                ApiEventSubscriber::MAIN        => $merchant,
                ApiEventSubscriber::WITH        => $data,
                ApiEventSubscriber::MERCHANT_ID => $merchantId
            ];

            $this->app['events']->dispatch('api.account.instant_activation_gmv_limit_warning', $eventPayload);

            $this->trace->info(TraceCode::INSTANT_ACTIVATION_ESCALATION_WEBHOOK_TRIGGERED, [
                'merchant_id'   => $merchantId,
                'milestone'     => $params['milestone'] ?? null,
                'threshold'     => $params['threshold'] ?? null
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::INSTANT_ACTIVATION_ESCALATION_WEBHOOK_FAILURE,[
                'error'         => $e->getMessage(),
                'merchant_id'   => $merchantId,
                'milestone'     => $params['milestone'] ?? null,
                'threshold'     => $params['threshold'] ?? null
            ]);
            throw $e;
        }
    }

    public function getInstantActivationLimitWarnData(string $merchantId, array $params=[])
    {
        if(empty($params) === true or empty($params['threshold']) === true or empty($params['current_gmv']) === true)
        {
            throw new Exception\RuntimeException('Data sent to trigger webhook for warning is not sufficient', [
                'merchant_id'  => $merchantId,
                'parameters'   => $params
            ]);
        }

        $threshold = $params['threshold'];

        $currentGmv = $params['current_gmv'];

        if($threshold === 1500000)
        {
            $message = 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.';
        }
        else
        {
            $message = "You can accept payments upto INR " .max((1500000 - $currentGmv)/100, 0). ". In order to remove this limit and settle the amount to your account, fill in the remaining details";
        }

        return [
            'acc_id'        => $merchantId,
            'gmv_limit'     => 15000,
            'current_gmv'   => $currentGmv/100,
            'message'       => $message
        ];
    }
}
