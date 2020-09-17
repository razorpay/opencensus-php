<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

/**
 * Default status update implementation for a artefact type, ideally class name
 * should be default but default is a keyword in php , so using DocumentStatusUpdater
 *
 * Class DocumentStatusUpdater
 *
 * @package RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater
 */
class DefaultStatusUpdater extends BaseStatusUpdater
{

    protected $documentTypeStatusKey;

    /**
     * DefaultStatusUpdate constructor.
     *
     * @param DetailEntity $merchantDetails
     * @param string       $documentTypeStatusKey
     * @param string       $artefactType
     */
    public function __construct(DetailEntity $merchantDetails, string $documentTypeStatusKey, string $artefactType)
    {
        parent::__construct($merchantDetails, $artefactType);

        $this->documentTypeStatusKey = $documentTypeStatusKey;
    }

    public function updateValidationStatus(): void
    {
        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerId(
            $this->merchantId,
            $this->artefactType);

        if (empty($validation) === false)
        {
            $documentValidationStatus = $this->getDocumentValidationStatus($validation);

            $this->merchantDetails->setAttribute($this->documentTypeStatusKey, $documentValidationStatus);

            //
            // if $documentValidationStatus is null then don't send any metrics
            //
            if (empty($documentValidationStatus) === false)
            {
                $verificationMetrics = [
                    Constant::ARTEFACT_TYPE                     => $this->artefactType,
                    Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $documentValidationStatus
                ];

                $this->trace->count(Detail\Metric::VALIDATION_STATUS_BY_ARTEFACT_TOTAL, $verificationMetrics);
            }
        }
    }
}
