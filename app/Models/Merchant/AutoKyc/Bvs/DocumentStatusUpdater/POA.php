<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Document\Core;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class POA extends BaseStatusUpdater
{
    /**
     * @throws \RZP\Exception\LogicException
     */
    public function updateValidationStatus(): void
    {
        $validation = $this->fetchValidOcrDocumentValidation($this->merchantDetails->merchant);

        $documentValidationStatus = $this->getFailedStatus();

        if (empty($validation) === false)
        {
            $documentValidationStatus = $this->getDocumentValidationStatus($validation);
        }

        $this->merchantDetails->setPoaVerificationStatus($documentValidationStatus);

        $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_STATUS, [
            'merchant_id'                  => $this->merchantDetails->getId(),
            'artefact_type'                => $this->artefactType,
            'document_verification_status' => $documentValidationStatus
        ]);

        $verificationMetrics = [
            Constant::ARTEFACT_TYPE                                   => $this->artefactType,
            BvsValidation\Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $documentValidationStatus
        ];

        $this->trace->count(Detail\Metric::VALIDATION_STATUS_BY_ARTEFACT_TOTAL, $verificationMetrics);
    }

    /**
     * @return string
     */
    public function getUpdatedActivationStatus(): string
    {
        return (new Detail\Core())->getApplicableActivationStatus($this->merchantDetails);
    }

    /**
     * @param Entity $merchant
     * @return mixed|null
     */
    public function fetchValidOcrDocumentValidation(Entity $merchant)
    {
        $validations = (new Core())->fetchAllOcrDocumentsBvsValidations($merchant);

        $resultValidation = null;

        foreach ($validations as $validation)
        {
            $resultValidation = $validation;

            if ($validation->getValidationStatus() === BvsValidation\Constants::SUCCESS)
            {
                return $resultValidation;
            }
        }

        return $resultValidation;
    }
}
