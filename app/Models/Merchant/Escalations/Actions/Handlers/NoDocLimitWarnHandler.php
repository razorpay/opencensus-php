<?php

namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Escalations\Actions\Entity;
use RZP\Models\Merchant\AccountV2\Core as AccV2Core;

class NoDocLimitWarnHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        try {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            if(in_array($merchant->merchantDetail->getActivationStatus(), Status::MERCHANT_NO_DOC_OPEN_STATUSES, true) === false)
            {
                $this->trace->info(
                    TraceCode::NO_DOC_ONBOARDING_ESCALATION_SKIPPED,
                    [
                        'merchant_id'   => $merchantId,
                        'step'          => 'no_doc_limit_warn_handler',
                        'reason'        => 'Xpress escalation warning skipped since the merchant does not have any of the xpress open statuses',
                    ]
                );

                return;
            }

            $accountV2Core = new AccV2Core();

            $accountV2Core->triggerWebhookForNoDocGmvLimitBreach($merchant, $params);

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
}
