<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\BvsValidation\Constants as ValidationConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\VerificationDetail as MVD;


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
     * @param Detail\Entity  $merchantDetails
     * @param string         $documentTypeStatusKey
     * @param Entity         $consumedValidation
     * @param string         $entity
     */
    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetails,
                                string $documentTypeStatusKey,
                                Entity $consumedValidation,
                                string $entity = E::MERCHANT_DETAIL)
    {
        parent::__construct($merchant,$merchantDetails, $consumedValidation);

        $this->documentTypeStatusKey = $documentTypeStatusKey;

        $this->entity = $entity;
    }

    public function updateValidationStatus(): void
    {
        $this->processUpdateValidationStatus();

        $this->handleArtefactSignatoryValidation();

        $this->postUpdateValidationStatus();
    }

    protected function handleArtefactSignatoryValidation()
    {
        if (in_array($this->artefactType . '-' . $this->validationUnit, MVD\Constants::SIGNATORY_ALLOWED_ARTEFACTS) === false)
        {
            return;
        }

        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerId(
            $this->merchantId,
            $this->artefactType,
            $this->validationUnit,
            Constant::MERCHANT
        );

        if (empty($validation) === false)
        {
            $signatoryValidationStatus = $this->getArtefactSignatoryVerificationStatus($validation);

            $this->app['trace']->info(TraceCode::MERCHANT_SIGNATORY,[
                'signatoryValidationStatus'    =>  $signatoryValidationStatus,
                'merchant_id'                  =>  $this->merchantId,
                'artefact_type'                =>  $this->artefactType,
                'artefact_identifier'          =>  $this->validationUnit,
            ]);

            if (empty($signatoryValidationStatus) === false)
            {
                $input = [
                    MVD\Entity::MERCHANT_ID         => $this->merchant->getId(),
                    MVD\Entity::ARTEFACT_TYPE       => $this->artefactType,
                    MVD\Entity::ARTEFACT_IDENTIFIER => ($this->validationUnit === ValidationConstants::IDENTIFIER) ? MVD\Constants::NUMBER : MVD\Constants::DOC,
                    MVD\Entity::METADATA            => [
                                                        'signatory_validation_status'   => $signatoryValidationStatus,
                                                        'bvs_validation_id'             => $this->consumedValidationId]
                ];

                (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $input);

                $verificationMetrics = [
                    Constant::ARTEFACT_TYPE                     => $this->artefactType . '_' . $this->validationUnit . '_' . 'signatory',
                    'document_signatory_verification_status'    => $signatoryValidationStatus
                ];

                $this->trace->count(Detail\Metric::VALIDATION_STATUS_BY_ARTEFACT_TOTAL, $verificationMetrics);

                $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_STATUS, [
                    'merchant_id'                            => $this->merchantDetails->getId(),
                    'artefact_type'                          => $this->artefactType,
                    'document_signatory_verification_status' => $signatoryValidationStatus,
                    'bvs_validation_id'                      => $this->consumedValidationId
                ]);

                $isArtefactsSignatoryVerificationEnabled = $this->ruleResultVerifier->isArtefactsSignatoryVerificationExperimentEnabled($this->merchantId);

                if ($isArtefactsSignatoryVerificationEnabled === true)
                {
                    $this->handleMerchantSignatory();
                }
            }
        }

    }

    protected function processUpdateValidationStatus()
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

            $updateVerificationStatusAttribute = $this->validateDocumentTypeStatusUpdate($documentValidationStatus);

            $this->trace->info(TraceCode::ENTITY_VERIFICATION_ATTRIBUTE_UPDATE, [
                'status_update' => $updateVerificationStatusAttribute,
            ]);

            if ($updateVerificationStatusAttribute === true)
            {
                switch ($this->entity)
                {
                    case E::MERCHANT_DETAIL:

                        $this->merchantDetails->setAttribute(
                            $this->documentTypeStatusKey, $documentValidationStatus);
                        $this->updateStakeholderStatusIfApplicable($documentValidationStatus);

                        break;

                    case E::STAKEHOLDER:

                        $this->merchantDetails->load('stakeholder');

                        $this->merchantDetails->stakeholder->setAttribute(
                            $this->documentTypeStatusKey, $documentValidationStatus);
                        break;
                }
            }

            // if $documentValidationStatus is null then don't send any metrics

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

        $this->instantlyActivateMerchantIfApplicable($this->merchant, $this->merchantDetails);
    }
    /*
     This function is to check for validations before updating the artefact status in the merchant details table
     */

    protected function validateDocumentTypeStatusUpdate($documentValidationStatus) : bool
    {
        $merchantId = $this->merchantDetails->getId();

        $this->trace->info(TraceCode::ENTITY_VERIFICATION_ATTRIBUTE_UPDATE, [
            'artefact_type'                => $this->artefactType,
            'document_verification_status' => $documentValidationStatus,
            'documentTypeStatusKey'        => $this->documentTypeStatusKey
        ]);

        if (($documentValidationStatus === ValidationConstants::VERIFIED) and
            ($this->artefactType === Constant::PERSONAL_PAN) and
            (($this->documentTypeStatusKey === Detail\Entity::POI_VERIFICATION_STATUS) or
             ($this->documentTypeStatusKey === \RZP\Models\Merchant\Stakeholder\Entity::POI_STATUS)))
        {
            /*
         We will set poi verification status in the db as verified only when we have updated pan name in the merchant
         detail table. Fetching merchant details from the db and not the merchant detail object in the current context
         */

            $merchantDetails = $this->repo->merchant_detail->findOrFail($merchantId);

            $promoterPanName = $merchantDetails->getPromoterPanName();

            if ($promoterPanName !== null)
            {
                return true;
            }

            return false;
        }

        return true;
    }

    protected function postUpdateValidationStatus()
    {
        $this->updateMerchantContext();

        $this->sendConsumedValidationResultEvent();

        $this->app['segment-analytics']->buildRequestAndSend();
    }

    protected function instantlyActivateMerchantIfApplicable($merchant, Detail\Entity $merchantDetails)
    {
        try
        {
            if ($this->artefactType !== Constant::PERSONAL_PAN)
            {
                return;
            }

            $isExperimentEnabled = (new MerchantCore())->isRazorxExperimentEnable($merchant->getId(),
                                                                                  RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY);

            if ($isExperimentEnabled === false)
            {
                return;
            }

            $isRiskyMerchant = (new Detail\DeDupe\Core())->isMerchantImpersonated($merchantDetails->merchant);

            if ((new Detail\Core)->canActivateMerchant($merchantDetails, $isRiskyMerchant) === true)
            {
                (new Detail\ActivationFlow\Whitelist())->process($merchant);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null,
                                         TraceCode::UPDATE_MERCHANT_CONTEXT_JOB_ERROR,
                                         [
                                             'merchant_id' => $this->merchantId,
                                             'method'      => __FUNCTION__
                                         ]);

        }
    }

    public function updateStatusToPending(): void
    {
        $this->merchantDetails->setAttribute($this->documentTypeStatusKey, Constants::PENDING);

        $this->updateStakeholderStatusIfApplicable(Constants::PENDING);
    }

}
