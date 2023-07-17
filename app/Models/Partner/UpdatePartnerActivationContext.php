<?php

namespace RZP\Models\Partner;

use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Partner\Metric as PartnerMetrics;
use RZP\Models\Merchant\Detail\NeedsClarification\Core;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;
use RZP\Models\Merchant\Detail\NeedsClarification\Metrics;
use RZP\Models\Partner\Activation\Core as PartnerActivationCore;
use RZP\Models\Merchant\Detail\NeedsClarification\UpdateContextRequirements;

class UpdatePartnerActivationContext extends Core
{
    public function __construct(Merchant\Entity $merchant)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->updateContextRequirements = new UpdateContextRequirements();
    }

    /**
     * Update partner context (activation_status) if possible based on the verification
     * status of fields such as Bank details, PAN or GSTIN
     *
     * @return void
     * @throws \RZP\Exception\LogicException
     * @throws \Throwable
     */
    public function update(string $validationId = null): void
    {
//        Currently this update is done only for Resellers
        if ($this->merchant->isResellerPartner() === false)
        {
            return;
        }

        $partnerActivation = (new PartnerCore())->getPartnerActivation($this->merchant);

        $canUpdatePartnerContext = !($partnerActivation === null) &&
            $this->updateContextRequirements->canUpdatePartnerContext($partnerActivation);

        if ($canUpdatePartnerContext === true)
        {
            $clarificationCore = new Core();

            $newActivationStatus = (new PartnerCore())->getApplicablePartnerActivationStatus(
                $this->merchant->merchantDetail, $partnerActivation
            );

            $properties = [
                'id'            => $this->merchant->getId(),
                'experiment_id' => $this->app['config']->get('app.partner_independent_kyc_exp_id'),
            ];
            $isSystemBasedNeedsClarificationEnabledForPartner = (new Merchant\Core())->isSplitzExperimentEnable(
                $properties, 'enable', TraceCode::SYSTEM_BASED_NEEDS_CLARIFICATION_FOR_PARTNER_ERROR
            );

            if (($clarificationCore->shouldTriggerNeedsClarification($partnerActivation) === true) and
                ($isSystemBasedNeedsClarificationEnabledForPartner === true))
            {
                $kycClarificationReasons = (new Core())->composeNeedsClarificationReason($partnerActivation);

                if (empty($kycClarificationReasons) === false)
                {
                    $input[Entity::KYC_CLARIFICATION_REASONS] = $kycClarificationReasons;
                    $kycClarificationReasons = (new PartnerCore())->getUpdatedPartnerKycClarificationReasons(
                        $input, $partnerActivation->getMerchantId(), DetailConstant::SYSTEM
                    );
                    $partnerActivation->setKycClarificationReasons($kycClarificationReasons);

                    $newActivationStatus = Status::NEEDS_CLARIFICATION;

                    $this->trace->count(Metrics::PARTNER_NEEDS_CLARIFICATION_TRIGGERED_TOTAL);
                }
            }

            $activationStatus = $partnerActivation->getActivationStatus();
            $dimension = array("activation_status" => $activationStatus);
            $this->trace->count(PartnerMetrics::PARTNERS_KYC_ACTIVATION_STATUS_TOTAL, $dimension);

            $this->trace->info(TraceCode::UPDATE_PARTNER_CONTEXT,[
                'partner_id'            => $this->merchant->getId(),
                'new_activation_status' => $newActivationStatus,
                'old_activation_status' => $activationStatus
            ]);

            if (($activationStatus !== $newActivationStatus) and ($activationStatus === Status::UNDER_REVIEW))
            {
                $activationStatusData = [
                    Entity::ACTIVATION_STATUS => $newActivationStatus
                ];

                (new PartnerActivationCore)->updatePartnerActivationStatus(
                    $this->merchant, $partnerActivation, $this->merchant, $activationStatusData
                );

                if($newActivationStatus === Status::NEEDS_CLARIFICATION)
                {
                    (new Merchant\Core)->appendTag($this->merchant, "Partner Auto NC");

                    $this->sendAutoNeedsClarificationEvent($this->merchant, E::PARTNER_ACTIVATION, $validationId);

                    $this->trace->debug(TraceCode::PARTNER_AUTO_NC_TAG_ADDED, [
                        'partner_id' => $this->merchant->getId(),
                        'tags'       => $this->merchant->tagNames()
                    ]);
                }
            }

            $this->sendSegmentEvents();
        }
    }

    protected function sendAutoNeedsClarificationEvent($merchant, $source = null, string $validationId = null): void
    {
        if(empty($this->validationId) === true)
        {
            return;
        }

        try
        {
            $validation = (new BvsValidation\Core)->getValidation($validationId);

            $eventAttributes = [
                BvsValidation\Entity::ARTEFACT_TYPE         => $validation->getArtefactType(),
                BvsValidation\Entity::VALIDATION_STATUS     => $validation->getValidationStatus(),
                BvsValidation\Entity::ERROR_CODE            => $validation->getErrorCode(),
                BvsValidation\Entity::ERROR_DESCRIPTION     => $validation->getErrorDescription(),
            ];

            $eventCode = ($source === E::PARTNER_ACTIVATION ) ? EventCode::PARTNER_AUTO_NC: EventCode::MERCHANT_AUTO_NC;

            $app = App::getFacadeRoot();

            $app['diag']->trackOnboardingEvent(
                $eventCode, $merchant, null, $eventAttributes);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null,
                TraceCode::AUTO_NC_EVENT_FAILED
            );
        }
    }

    protected function sendSegmentEvents(): void
    {
        try
        {
            $app = App::getFacadeRoot();
            $app['segment-analytics']->buildRequestAndSend();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null,
                TraceCode::SEGMENT_EVENT_PUSH_FAILURE
            );
        }
    }
}
