<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;

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
    protected $entity;

    /**
     * DefaultStatusUpdate constructor.
     *
     * @param MerchantEntity $merchant
     * @param string $documentTypeStatusKey
     * @param string $artefactType
     * @param string $consumedValidationId
     * @param string $entity
     */
    public function __construct(MerchantEntity $merchant,
                                string $documentTypeStatusKey,
                                string $artefactType,
                                string $consumedValidationId,
                                string $entity=E::MERCHANT_DETAIL)
    {
        parent::__construct($merchant, $artefactType, $consumedValidationId);

        $this->documentTypeStatusKey = $documentTypeStatusKey;

        $this->entity = $entity;
    }

    public function updateValidationStatus(): void
    {
        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerId(
            $this->merchantId,
            $this->artefactType);

        if (empty($validation) === false)
        {
            $documentValidationStatus = $this->getDocumentValidationStatus($validation);

            switch ($this->entity)
            {
                case E::MERCHANT_DETAIL:
                    $this->merchantDetails->setAttribute(
                        $this->documentTypeStatusKey, $documentValidationStatus);
                    $this->updateStakeholderStatusIfApplicable($documentValidationStatus);
                    break;

                case E::STAKEHOLDER:
                    $this->merchantDetails->stakeholder->setAttribute(
                        $this->documentTypeStatusKey, $documentValidationStatus);
                    break;
            }

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

            $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_STATUS, [
                'merchant_id'                  => $this->merchantDetails->getId(),
                'artefact_type'                => $this->artefactType,
                'document_verification_status' => $documentValidationStatus
            ]);
        }

        $this->updateMerchantContext();

        $this->sendConsumedValidationResultEvent();
    }

    public function updateStatusToPending(): void
    {
        $this->merchantDetails->setAttribute($this->documentTypeStatusKey, Constants::PENDING);

        $this->updateStakeholderStatusIfApplicable(Constants::PENDING);
    }
}
