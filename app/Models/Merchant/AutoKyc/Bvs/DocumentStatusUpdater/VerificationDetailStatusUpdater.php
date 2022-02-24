<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * Class DocumentStatusUpdater
 *
 * @package RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater
 */

class VerificationDetailStatusUpdater extends BaseStatusUpdater
{
    protected $entity;

    protected $verificationDetailArtefactType;

    protected $verificaationDetailValidationUnit;

    /**
     * DefaultStatusUpdate constructor.
     *
     * @param MerchantEntity $merchant
     * @param Detail\Entity  $merchantDetails
     * @param Entity         $consumedValidation
     * @param string         $entity
     */

    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetails,
                                Entity $consumedValidation,
                                string $entity=E::MERCHANT_DETAIL)
    {
        parent::__construct($merchant,$merchantDetails, $consumedValidation);

        $this->entity = $entity;
    }

    public function updateValidationStatus(): void
    {
        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerId(
            $this->merchantId,
            $this->artefactType,
            $this->validationUnit,
            Constant::MERCHANT
        );

        if (empty($validation) === false)
        {
            $documentValidationStatus = $this->getDocumentValidationStatus($validation);

            $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
                $this->merchant->getId(),
                $this->verificationDetailArtefactType,
                $this->verificaationDetailValidationUnit
            );

            $verificationDetail->setAttribute(MVD\Entity::STATUS, $documentValidationStatus);

            $this->repo->merchant_verification_detail->saveOrFail($verificationDetail);

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
                'document_verification_status' => $documentValidationStatus,
                'bvs_validation_id'            => $this->consumedValidationId
            ]);
        }

        $this->sendConsumedValidationResultEvent();
    }

    public function updateStatusToPending(): void
    {
        $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
            $this->merchant->getId(),
            $this->verificationDetailArtefactType,
            $this->verificaationDetailValidationUnit
        );

        $verificationDetail->setAttribute(MVD\Entity::STATUS, Constants::PENDING);
    }
    public function canUpdateMerchantContext(): bool
    {
        return true;
    }
}
