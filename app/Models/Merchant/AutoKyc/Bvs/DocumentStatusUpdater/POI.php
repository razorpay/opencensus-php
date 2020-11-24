<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;

class POI extends BaseStatusUpdater
{
    protected $validationId;

    /**
     * POI constructor.
     *
     * @param MerchantEntity $merchant
     * @param string         $documentType
     * @param string         $consumedValidationId
     */
    public function __construct(MerchantEntity $merchant, string $documentType, string $consumedValidationId)
    {
        parent::__construct($merchant, $documentType, $consumedValidationId);

        $this->documentTypeStatusKey = Detail\Entity::POI_VERIFICATION_STATUS;
    }

    public function updateValidationStatus(): void
    {
        $validation = $this->repo->bvs_validation->findOrFail($this->consumedValidationId);

        $documentValidationStatus = $this->getDocumentValidationStatus($validation);

        $documentValidationStatusFromKyc = $this->merchantDetails->getPoiVerificationStatus();

        $bvsAndKycStatusComparisonResult = ($documentValidationStatus !== $documentValidationStatusFromKyc) ?
            Constants::MISMATCH : Constants::MATCH;

        $bvsPoiVerificationMetrics = [
            Constants::BVS_KYC_VERIFICATION_RESULT      => $bvsAndKycStatusComparisonResult,
            Detail\Constants::POI_STATUS                => $this->merchantDetails->getPoiVerificationStatus(),
            Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $validation->getValidationStatus()
        ];

        $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_STATUS, [
            'merchant_id'                  => $this->merchantDetails->getId(),
            'artefact_type'                => $this->artefactType,
            'document_verification_status' => $documentValidationStatus
        ]);

        $this->trace->count(Detail\Metric::BVS_VALIDATION_STATUS_TOTAL, $bvsPoiVerificationMetrics);

        $this->updateMerchantContext();

        $this->sendConsumedValidationResultEvent();
    }
}
