<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use Illuminate\Support\Facades\App;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\BvsValidation\Entity as Validation;

class UpdateStatusForBAS
{
    protected $app;

    protected $repo;

    protected $trace;

    protected $validation;

    public function __construct(Entity $validation)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->validation = $validation;
    }

    /**
     * @throws LogicException
     */
    public function updateValidationStatus(): void
    {
        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerIdAndOwnerType(
            $this->validation->getOwnerId(), Constant::BAS_DOCUMENT, $this->validation->getArtefactType());

        if (empty($validation) === false)
        {
            $documentValidationStatus = $this->getDocumentValidationStatus($validation);

            //
            // if $documentValidationStatus is null then don't send any metrics
            //
            if (empty($documentValidationStatus) === false)
            {
                try
                {
                    $this->sendUpdateRequestToBAS($validation, $documentValidationStatus);
                }
                catch (\Exception $exception)
                {
                    $this->trace->error(TraceCode::REQUEST_TO_BAS_DOCUMENT_STATUS_FAIL, ['ERROR' => $exception]);
                }

                $verificationMetrics = [
                    Constant::ARTEFACT_TYPE                     => $this->validation->getArtefactType(),
                    Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $documentValidationStatus
                ];

                $this->trace->count(Detail\Metric::VALIDATION_STATUS_BY_ARTEFACT_TOTAL, $verificationMetrics);
            }

            $this->trace->info(TraceCode::BAS_BVS_PAN_VERIFICATION_STATUS, [
                'bas_document_id'              => $this->validation->getOwnerId(),
                'artefact_type'                => $this->validation->getArtefactType(),
                'document_verification_status' => $documentValidationStatus
            ]);
        }
    }

    /**
     * Returns document validation status for a validation entity
     *
     * @param Validation $validation
     *
     * @return null|string
     * @throws LogicException
     */
    protected function getDocumentValidationStatus(Validation $validation): ?string
    {
        if ($validation->getValidationStatus() === Constants::SUCCESS)
        {
            return $this->getVerifiedStatus();
        }

        if ($validation->getValidationStatus() === Constants::FAILED)
        {
            foreach (Constants::ERROR_MAPPING as $artifactValidationStatus => $error_codes)
            {
                if (array_search($validation->getErrorCode(), $error_codes, true) !== false)
                {
                    $func = BaseStatusUpdater::VALIDATION_STATUS_FUNCTION_MAPPING[$artifactValidationStatus] ?? '';

                    if (method_exists($this, $func) === true)
                    {
                        return $this->$func();
                    }

                    throw new LogicException(
                        ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_VALIDATION_STATUS,
                        null,
                        [Constant::STATUS => $artifactValidationStatus]);
                }
            }

            //
            // If no error code is mapped then consider it as failed status
            //
            return $this->getFailedStatus();
        }

        //
        // If validation is still in pending status
        //

        return null;
    }

    protected function getVerifiedStatus(): string
    {
        return Constants::VERIFIED;
    }

    protected function getFailedStatus(): string
    {
        return Constants::FAILED;
    }

    protected function getIncorrectDetailStatus(): string
    {
        return Constants::INCORRECT_DETAILS;
    }

    protected function getNotMatchedStatus(): string
    {
        return Constants::NOT_MATCHED;
    }

    private function sendUpdateRequestToBAS(Validation $validation, string $documentValidationStatus)
    {
        $payload = [
            Constant::OWNER_TYPE => $validation->getOwnerType(),
            Constant::OWNER_ID   => $validation->getOwnerId(),
            Entity::VALIDATION_STATUS => $documentValidationStatus,
        ];

        $this->app['banking_account_service']->updateBVSValidationStatus($payload);
    }
}

