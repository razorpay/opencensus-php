<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Document\Core;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Entity as MerchantEntity;

class POA extends BaseStatusUpdater
{
    /**
     * POA constructor.
     *
     * @param MerchantEntity       $merchant
     * @param Detail\Entity        $merchantDetails
     * @param BvsValidation\Entity $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetails,
                                BvsValidation\Entity $consumedValidation)
    {
        parent::__construct($merchant,$merchantDetails, $consumedValidation);

        $this->documentTypeStatusKey = Detail\Entity::POA_VERIFICATION_STATUS;
    }

    /**
     * @throws \RZP\Exception\LogicException
     */
    public function updateValidationStatus(): void
    {
        $validation = $this->fetchValidOcrDocumentValidation($this->merchant);

        $documentValidationStatus = $this->getFailedStatus();

        if (empty($validation) === false)
        {
            $documentValidationStatus = $this->getDocumentValidationStatus($validation);
        }

        if(empty($documentValidationStatus) === true){

            $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_STATUS_SKIPPED, [
                'merchant_id'                  => $this->merchantDetails->getId(),
                'artefact_type'                => $this->artefactType,
                'document_verification_status' => $documentValidationStatus,
                'bvs_validation_id'            => $this->consumedValidationId
            ]);

            return;
        }

        $this->merchantDetails->setPoaVerificationStatus($documentValidationStatus);
        $this->updateStakeholderStatusIfApplicable($documentValidationStatus);

        $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_STATUS, [
            'merchant_id'                  => $this->merchantDetails->getId(),
            'artefact_type'                => $this->artefactType,
            'document_verification_status' => $documentValidationStatus,
            'bvs_validation_id'            => $this->consumedValidationId
        ]);

        $verificationMetrics = [
            Constant::ARTEFACT_TYPE                                   => $this->artefactType,
            BvsValidation\Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $documentValidationStatus
        ];

        $this->trace->count(Detail\Metric::VALIDATION_STATUS_BY_ARTEFACT_TOTAL, $verificationMetrics);

        $this->updateMerchantContext();

        $this->sendConsumedValidationResultEvent();
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
     *
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
