<?php


namespace RZP\Models\Merchant\Cron\Collectors;


use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Trace\TraceCode;


class BvsDataCollector extends TimeBoundDbDataCollector
{
    protected $name = "bvs_validations";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        // fetch validations in captured state which are created before 1 day
        $capturedValidations = $this->repo->bvs_validation->getValidationsOfStatus(BvsValidationConstants::CAPTURED, $startTime, $endTime);

        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'                  => $this->args,
            'start_time'            => $startTime,
            'end_time'              => $endTime,
            'capturedValidations'   => count($capturedValidations)
        ]);

        return CollectorDto::create($capturedValidations);
    }

    protected function getStartInterval() : int
    {
        return $this->lastCronTime-300;
    }

    protected function getEndInterval() : int
    {
        return $this->cronStartTime-300;
    }
}
