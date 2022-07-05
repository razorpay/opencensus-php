<?php

namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant\Escalations\Actions\Entity;

class NoDocLimitWarnHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        try {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $data = $this->getNoDocGmvLimitWarnData($merchantId, $params);

            $eventPayload = [
                ApiEventSubscriber::MAIN        => $merchant,
                ApiEventSubscriber::WITH        => $data,
                ApiEventSubscriber::MERCHANT_ID => $merchantId
            ];

            $this->app['events']->dispatch('api.account.no_doc_onboarding_gmv_limit_warning', $eventPayload);

            $this->trace->info(
                TraceCode::NO_DOC_ONBOARDING_ESCALATION_SUCCESS,
                [
                    'merchant_id'   => $merchantId,
                    'milestone'     => $params['milestone'] ?? null,
                    'threshold'     => $params['threshold'] ?? null
                ]
            );
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::NO_DOC_ONBOARDING_ESCALATION_FAILURE,
                [
                    'reason'        => 'something went wrong while handling no-doc onboarding gmv breach warning',
                    'trace'         => $e->getMessage(),
                    'merchant_id'   => $merchantId,
                    'milestone'     => $params['milestone'] ?? null,
                    'threshold'     => $params['threshold'] ?? null
                ]
            );

            throw $e;
        }
    }

    private function getNoDocGmvLimitWarnData(string $merchantId, array $params = [])
    {
        if(empty($params) === true or empty($params['threshold']) === true or empty($params['current_gmv']) === true)
        {
            throw new Exception\RuntimeException('Data sent to trigger webhook for no doc gmv breach warning is not sufficient', [
                'merchant_id'  => $merchantId,
                'parameters'   => $params
            ]);
        }

        $threshold = $params['threshold'];

        $currentGmv = $params['current_gmv'];

        return [
            'acc_id'        => $merchantId,
            'gmv_limit'     => $threshold,
            'current_gmv'   => $currentGmv,
            'message'       =>  "You can accept payments upto INR " .max(($threshold - $currentGmv), 0). ". In order to remove this limit, kindly submit the KYC documents."
        ];
    }
}
