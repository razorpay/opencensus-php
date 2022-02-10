<?php

namespace RZP\Models\Merchant\Cron\Collectors;


use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class BVSPartlyExecutedValidationsDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {

        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'                  => $this->args,
            'start_time'            => $startTime,
            'end_time'              => $endTime
        ]);

        // iterate over each table entity pair and create a list of validation id's whose status
        // has not been yet updated
        $partlyProcessedValidations = [];
        foreach (Constant::ARTEFACT_STATUS_ATTRIBUTE_MAPPING as $artefactIdentifier => $tableNameFieldName)
        {
            $tableName = $tableNameFieldName[0];
            // TODO: Support Merchant Verification Table.
            if ($tableName === TABLE::MERCHANT_DETAIL)
            {
                $fieldName = $tableNameFieldName[1];
                // list of merchant ids in the past 24 hours with entity status as null
                $merchantIds = $this->repo->merchant_detail->filterNullFieldStatusMerchants($fieldName, $startTime, $endTime);
                $artefactIdentifierArr = (explode("-", $artefactIdentifier));
                $artefact_type   = $artefactIdentifierArr[0];
                $validation_unit = $artefactIdentifierArr[1];
                // for each of the merchant id, find the corresponding validation record
                foreach ($merchantIds as $merchantId)
                {
                    $validation = $this->repo->bvs_validation->getLatestValidationForArtefactAndValidationUnit($merchantId,
                        $artefact_type, $validation_unit);
                    // if record not found ignore
                    if (empty($validation))
                    {
                        continue;
                    }
                    $validation_status = $validation[BvsValidationConstants::STATUS];
                    // we only push partly processed validations
                    if ($validation_status != BvsValidationConstants::CAPTURED)
                    {
                        array_push($partlyProcessedValidations, $validation);
                    }
                }
            }
        }
        return CollectorDto::create($partlyProcessedValidations);
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
