<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Core;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Trace\TraceCode;

class BVSPartlyExecutedValidationActions extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            $this->app['trace']->info(TraceCode::PARTLY_EXECUTED_VALIDATIONS_PROCESS_SKIPPED, [
                'message' => 'partly executed validations do not exist.'
            ]);
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data["bvs_partly_executed_validations"]; // since data collector is an array
        $unprocessedValidations = $collectorData->getData();
        $unprocessedValidationCount = count($unprocessedValidations);

        // Skip as there are no unprocessed validations.
        if ($unprocessedValidationCount === 0)
        {
            $this->app['trace']->info(TraceCode::PARTLY_EXECUTED_VALIDATIONS_PROCESS_SKIPPED, [
                'message' => 'partly executed validations do not exist.'
            ]);
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;
        $this->app['trace']->info(TraceCode::PARTLY_EXECUTED_VALIDATIONS_PROCESS, [
            '$unprocessedValidationCount' => $unprocessedValidationCount
        ]);

        foreach ($unprocessedValidations as $validation)
        {
            try
            {
                $validationId    = $validation[Constant::VALIDATION_ID];
                // create response
                (new Core())->processValidation($validationId, []);

                $successCount++;
            }
            catch (\Throwable $e)
            {
                $this->app['trace']->traceException(
                    $e,
                    null,
                    TraceCode::PARTLY_EXECUTED_VALIDATIONS_PROCESS_FAILED,
                    [
                        "validation" => $validation,
                    ]);
            }
        }

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($unprocessedValidations)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        $this->app['trace']->info(TraceCode::PARTLY_EXECUTED_VALIDATIONS_PROCESS_SUCCESS, [
            '$successCount' => $successCount
        ]);

        return new ActionDto($status);
    }
}
