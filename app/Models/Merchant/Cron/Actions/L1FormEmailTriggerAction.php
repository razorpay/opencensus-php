<?php

namespace RZP\Models\Merchant\Cron\Actions;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core;
use RZP\Models\AMPEmail\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Cron\Dto\ActionDto;

class L1FormEmailTriggerAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["merchant_notification_data"]; // since data collector is an array

        $data = $collectorData->getData();

        $merchantIds = $data["merchantIds"];

        if (count($merchantIds) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $isRazorxExperimentEnabled = (new Core)->isRazorxExperimentEnable(
                    $merchantId,
                    RazorxTreatment::MAILMODO_L1_FORM_EMAIL_TRIGGER);

                $this->app['trace']->info(
                    TraceCode::RAZORX_EXPERIMENT_RESULT,
                    [RazorxTreatment::MAILMODO_L1_FORM_EMAIL_TRIGGER => $isRazorxExperimentEnabled]);

                if ($isRazorxExperimentEnabled === true)
                {
                    $result = (new Service())->triggerL1FormForMerchant($merchant);

                    if ($result === true)
                    {
                        $successCount += 1;
                    }
                }

            }
            catch (\Throwable   $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    '$merchantId' => $merchantId,
                    'args'        => $this->args
                ]);
            }
        }

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantIds)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }
}
