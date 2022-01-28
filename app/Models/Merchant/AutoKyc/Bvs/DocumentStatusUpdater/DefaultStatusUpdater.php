<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\BvsValidation\Entity;
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
        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerId(
            $this->merchantId,
            $this->artefactType,
            $this->validationUnit,
            Constant::MERCHANT
        );

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
                'document_verification_status' => $documentValidationStatus,
                'bvs_validation_id'            => $this->consumedValidationId
            ]);
        }

        $this->instantlyActivateMerchantIfApplicable($this->merchant, $this->merchantDetails);

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
