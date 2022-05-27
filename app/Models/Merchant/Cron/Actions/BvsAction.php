<?php


namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\Factory;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\BvsValidation\Core;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Trace\TraceCode;

class BvsAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data["bvs_validations"]; // since data collector is an array

        $capturedValidations = $collectorData->getData();

        $successCount = 0;

        foreach ($capturedValidations as $validation)
        {
            try
            {
                $artefact_type   = $validation[Constant::ARTEFACT_TYPE];
                $validation_unit = $validation[Constant::VALIDATION_UNIT];
                $validationId    = $validation[Constant::VALIDATION_ID];
                $merchantId      = $validation[Constant::OWNER_ID];


                //get the merchant verification status corresponding to the validation artefact type and validation unit
                $status = $this->getMerchantStatusForValidation($merchantId, $artefact_type, $validation_unit);

                $this->app['trace']->info(TraceCode::CAPTURED_VALIDATIONS_STATUS, [
                    "validation" => $validation,
                    "status" => $status
                ]);

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $processor = (new Factory())->getProcessor([],$merchant);

                //get validation status from bvs
                try
                {
                    $response = $processor->FetchDetails($validationId);
                }
                catch (IntegrationException $error)
                {
                    $this->app['trace']->info(TraceCode::BVS_GET_VALIDATIONS_ERROR, [
                        'error'     => $error->getMessage(),
                        'errorCode' => $error->getCode()]);

                    //update the bvs_Validation status to failed if no records found in bvs
                    if ($error->getCode() === ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND)
                    {
                        $this->app['trace']->info(TraceCode::UPDATE_BVS_VALIDATIONS_STATUS, [
                            "validation" => $validation,
                            "status"     => 'failed']);

                        $validation->setValidationStatus('failed');
                        $this->repo->bvs_validation->saveOrFail($validation);
                    }

                    continue;
                }

                catch (\Exception $e)
                {
                    $this->app['trace']->traceException(
                        $e,
                        null,
                        TraceCode::CAPTURED_VALIDATIONS_PROCESS_FAILED,
                        [
                            "validation" => $validation,
                        ]);
                }
                //if validation status fetched from bvs is success or failed then process the validation
                if (empty($response->getStatus()) === false and
                    ($response->getStatus() === 'failed' or
                        $response->getStatus() === 'success'))
                {
                    //if the verification is in progress process the validation
                    if (empty($status) or $status === BvsValidationConstants::INITIATED)
                    {
                        (new Core())->processValidation($validationId, $response->getResponse());
                    }
                    //only update bvs_validation status
                    else
                    {
                        $this->app['trace']->info(TraceCode::UPDATE_BVS_VALIDATIONS_STATUS, ["validation" => $validation, "status" => $response->getStatus()]);

                        $validation->setValidationStatus($response->getStatus());
                        $this->repo->bvs_validation->saveOrFail($validation);
                    }
                }
                $successCount++;
            }
            catch (\Throwable $e)
            {
                $this->app['trace']->traceException(
                    $e,
                    null,
                    TraceCode::CAPTURED_VALIDATIONS_PROCESS_FAILED,
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
            $status = ($successCount < count($capturedValidations)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }

    private function getMerchantStatusForValidation($merchantId, $artefact_type, $validation_unit)
    {
        $artefactStatusDetails = Constant::ARTEFACT_STATUS_ATTRIBUTE_MAPPING[$artefact_type . '-' . $validation_unit];

        $status = null;

        $entity = $artefactStatusDetails[0];

        switch ($entity)
        {
            case Table::MERCHANT_DETAIL:
                $merchantDetails = $this->repo->merchant_detail->findOrFail($merchantId);
                $status = $merchantDetails->getAttribute($artefactStatusDetails[1]);
                break;
            case Table::MERCHANT_VERIFICATION_DETAIL:
                $merchantVerificationDetails = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier($merchantId, $artefactStatusDetails[1], $artefactStatusDetails[2]);
                $status = $merchantVerificationDetails->getStatus();
                break;
            case Table::STAKEHOLDER:
                $merchantDetails = $this->repo->merchant_detail->findOrFail($merchantId);
                $status = $merchantDetails->stakeholder->getAttribute($artefactStatusDetails[1]);
                break;
        }

        return $status;
    }
}
