<?php

namespace RZP\Models\Merchant\Cron\Collectors;


use Carbon\Carbon;
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
            'args'       => $this->args,
            'start_time' => $startTime,
            'end_time'   => $endTime
        ]);

        $partlyProcessedValidations = [];

        // iterate over each table entity pair and create a list of validation id's whose status
        // has not been yet updated
        foreach (Constant::ARTEFACT_STATUS_ATTRIBUTE_MAPPING as $artefactIdentifier => $tableNameFieldName)
        {
            $tableName = $tableNameFieldName[0];

            // TODO: Support Merchant Verification Table.

            if ($tableName === TABLE::MERCHANT_DETAIL)
            {
                $fieldName = $tableNameFieldName[1];

                $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                    'fieldName'         => $fieldName,
                    'args'              => $this->args,
                    'time_before_query' => Carbon::now(),
                ]);

                // list of merchant ids in the past 24 hours with entity status as null
                $merchantIds = $this->repo->merchant_detail->filterNullAndInitiatedFieldStatusMerchants($fieldName, $startTime, $endTime);

                $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                    '$merchantIds'     => count($merchantIds),
                    'args'             => $this->args,
                    'time_after_query' => Carbon::now(),
                ]);

                $artefactIdentifierArr = (explode("-", $artefactIdentifier));
                $artefact_type         = $artefactIdentifierArr[0];
                $validation_unit       = $artefactIdentifierArr[1];

                // for each of the merchant id, find the corresponding validation record
                foreach ($merchantIds as $merchantId)
                {
                    if ($fieldName === \RZP\Models\Merchant\Detail\Entity::POA_VERIFICATION_STATUS)
                    {
                        $validations = $this->repo->bvs_validation->getValidationsForArtefactAndValidationUnit($merchantId,
                                                                                                               $artefact_type, $validation_unit);
                        foreach ($validations as $validation)
                        {
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
                    else
                    {
                        $validation = $this->repo->bvs_validation->getLatestValidationForArtefactAndValidationUnit($merchantId, $artefact_type, $validation_unit);
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
        }

        return CollectorDto::create($partlyProcessedValidations);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - 300;
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime-300;
    }

}
