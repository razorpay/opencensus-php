<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant\AutoKyc\Escalations\Handler;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants as TypeConstants;

class MerchantAutoKycEscalationsAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["merchant_escalation_data"]; // since data collector is an array

        $data = $collectorData->getData();

        $escalationWithType = $data["escalations_with_type"];

        if (empty($escalationWithType) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        foreach ($escalationWithType as $type => $escalationLevelMap)
        {
            $threshold = ($type == TypeConstants::SOFT_LIMIT) ? env(TypeConstants::SOFT_LIMIT_MCC_PENDING_THRESHOLD) : env(TypeConstants::HARD_LIMIT_MCC_PENDING_THRESHOLD);
            foreach ($escalationLevelMap as $level => $merchants)
            {
                $this->app['trace']->info(TraceCode::SELF_SERVE_ESCALATION_ATTEMPT, [
                    'type'      => 'escalation ' . $type,
                    'level'     => $level,
                    'merchants' => array_map(function($merchant) {
                        return $merchant->getId();
                    }, $merchants)
                ]);

                $merchantIdList = array_map(function($merchant) {
                    return $merchant->getId();
                }, $merchants);

                $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
                    $merchantIdList, MConstants::PAYMENT, $threshold);

                (new Handler)->handleEscalations($merchants, $merchantsGmvList, $type, $level);

                if ($type === TypeConstants::HARD_LIMIT and $level === 4)
                {
                    // disable settlements for merchants
                    foreach ($merchants as $merchant)
                    {
                        $merchant->setHoldFunds(true);
                        $merchant->setHoldFundsReason('GMV hard limit breached for the merchant.');
                        $this->repo->merchant->saveOrFail($merchant);
                    }
                }
            }
        }
        return new ActionDto(Constants::SUCCESS);
    }
}
