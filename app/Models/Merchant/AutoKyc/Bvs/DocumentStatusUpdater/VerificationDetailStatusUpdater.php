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

    protected $verificationDetailValidationUnit;

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

            if (empty($documentValidationStatus) === true)
            {
                $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_STATUS_SKIPPED, [
                    'merchant_id'                  => $this->merchantDetails->getId(),
                    'artefact_type'                => $this->artefactType,
                    'document_verification_status' => $documentValidationStatus,
                    'bvs_validation_id'            => $this->consumedValidationId
                ]);

                return;
            }

            $payload = $this->getVerificationDetailsPayload($validation, $documentValidationStatus);

            if (in_array($this->artefactType . '-' . $this->validationUnit, MVD\Constants::SIGNATORY_ALLOWED_ARTEFACTS) === true)
            {
                $signatoryValidationStatus = $this->getArtefactSignatoryVerificationStatus($validation);

                if (empty($signatoryValidationStatus) === false)
                {
                    $payload[MVD\Entity::METADATA] = [
                        'signatory_validation_status'   => $signatoryValidationStatus,
                        'bvs_validation_id'             => $this->consumedValidationId
                    ];
                }
            }

            (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $payload);
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

            if (in_array($this->artefactType . '-' . $this->validationUnit, MVD\Constants::SIGNATORY_ALLOWED_ARTEFACTS) === true)
            {
                $this->handleMerchantSignatory();
            }
        }

        $this->sendConsumedValidationResultEvent();

        $this->app['segment-analytics']->buildRequestAndSend();

        $this->updateMerchantContext();
    }

    public function updateStatusToPending(): void
    {
        $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
            $this->merchant->getId(),
            $this->verificationDetailArtefactType,
            $this->verificationDetailValidationUnit
        );

        $verificationDetail->setAttribute(MVD\Entity::STATUS, Constants::PENDING);
    }

    public function canUpdateMerchantContext(): bool
    {
        return true;
    }

    public function getVerificationDetailsPayload($validation, $documentValidationStatus)
    {
        return [
            MVD\Entity::STATUS               => $documentValidationStatus,
            MVD\Entity::MERCHANT_ID          => $this->merchant->getId(),
            MVD\Entity::ARTEFACT_TYPE        => $this->verificationDetailArtefactType,
            MVD\Entity::ARTEFACT_IDENTIFIER  => $this->verificationDetailValidationUnit,
        ];
    }
}
