<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\BvsValidation\Constants;

class POI extends BaseStatusUpdater
{
    protected $validationId;

    public function __construct(Detail\Entity $merchantDetails, string $documentType, string $validationId)
    {
        parent::__construct($merchantDetails, $documentType);

        $this->validationId = $validationId;
    }

    public function updateValidationStatus(): void
    {
        $validation = $this->repo->bvs_validation->findOrFail($this->validationId);

        $documentValidationStatus = $this->getDocumentValidationStatus($validation);

        $documentValidationStatusFromKyc = $this->merchantDetails->getPoiVerificationStatus();

        $bvsAndKycStatusComparisionResult = ($documentValidationStatus !== $documentValidationStatusFromKyc) ?
            Constants::MISMATCH : Constants::MATCH;

        $bvsPoiVerificationMetrics = [
            Constants::BVS_KYC_VERIFICATION_RESULT      => $bvsAndKycStatusComparisionResult,
            Detail\Constants::POI_STATUS                => $this->merchantDetails->getPoiVerificationStatus(),
            Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $validation->getValidationStatus()
        ];

        $this->trace->count(Detail\Metric::BVS_VALIDATION_STATUS_TOTAL, $bvsPoiVerificationMetrics);

    }
}
