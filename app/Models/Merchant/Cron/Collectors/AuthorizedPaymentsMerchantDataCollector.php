<?php

namespace RZP\Models\Merchant\Cron\Collectors;


use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class AuthorizedPaymentsMerchantDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'                  => $this->args,
            'start_time'            => $startTime,
            'end_time'              => $endTime
        ]);

        $merchantIds = $this->repo->payment->getAuthorizedPaymentsMerchants($startTime);

        return CollectorDto::create($merchantIds);
    }

    protected function getStartInterval() : int
    {
        return $this->lastCronTime;
    }

    protected function getEndInterval() : int
    {
        return $this->cronStartTime;
    }
}
