<?php

namespace RZP\Jobs;

use App;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\Middleware\EventTracker;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\NeedsClarification\Core;
use RZP\Models\Merchant\Detail\NeedsClarification\Metrics;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;
use RZP\Models\Partner\Activation\Core as PartnerActivationCore;
use RZP\Models\Merchant\Detail\NeedsClarification\UpdateContextRequirements;

class UpdateMerchantContext extends Job
{
    const MAX_RETRY_ATTEMPT = 2;

    const RETRY_INTERVAL = 300;

    protected $queueConfigKey = 'onboarding_kyc_verification';

    protected $merchantId;

    protected $validationId;

    protected $updateContextRequirements;

    public function __construct(string $mode, string $merchantId, string $validationId = null)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->validationId = $validationId;

        $this->updateContextRequirements = new UpdateContextRequirements();
    }

    /**
     * Updates merchant status based on verification , triggers system based needs clarification if required .
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $tracePayload = [
                Entity::MERCHANT_ID => $this->merchantId,
                'bvs_validation_id' => $this->validationId
            ];

            $this->trace->debug(TraceCode::UPDATE_MERCHANT_CONTEXT_JOB, $tracePayload);

            $this->trace->count(Metrics::UPDATE_CONTEXT_JOB_TOTAL);

            $this->mutex->acquireAndRelease(
                $this->merchantId,
                function() {
                    $this->updateMerchantContext();

                    $this->delete();
                },
                Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
                Constants::MERCHANT_MUTEX_RETRY_COUNT);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPDATE_MERCHANT_CONTEXT_JOB_ERROR,
                [
                    'merchant_id'       => $this->merchantId,
                    'bvs_validation_id' => $this->validationId
                ]);
        }

        $this->checkRetry();
    }

    /**
     * Update merchant & partner context (activation_status) if possible based on the verification
     * status of fields such as bank details, PAN, GSTIN etc
     *
     * @return void
     * @throws \RZP\Exception\LogicException
     * @throws \Throwable
     */
    protected function updateMerchantContext(): void
    {
        [$merchant, $merchantDetail] = (new DetailCore())->getMerchantAndSetBasicAuth($this->merchantId);

        $canUpdateMerchantContext = $this->updateContextRequirements
                                         ->canUpdateMerchantContext($merchantDetail);

        $this->trace->info(TraceCode::UPDATE_MERCHANT_CONTEXT_JOB,[
            "merchant_id"   => $merchant->getId(),
            "business_type" => $merchantDetail->getBusinessType(),
            "bvs_validation_id" => $this->validationId,
            "CAN_UPDATE_MERCHANT_CONTEXT"=>$canUpdateMerchantContext,
            "POA_VERIFICATION_STATUS"=>$merchantDetail->getAttribute(Entity::POA_VERIFICATION_STATUS),
            "COMPANY_PAN_VERIFICATION_STATUS"=>$merchantDetail->getAttribute(Entity::COMPANY_PAN_VERIFICATION_STATUS),
            "BANK_DETAILS_VERIFICATION_STATUS"=>$merchantDetail->getAttribute(Entity::BANK_DETAILS_VERIFICATION_STATUS),
            "POI_VERIFICATION_STATUS"=>$merchantDetail->getAttribute(Entity::POI_VERIFICATION_STATUS),
            "CIN_VERIFICATION_STATUS"=>$merchantDetail->getAttribute(Entity::CIN_VERIFICATION_STATUS),
            "GSTIN_VERIFICATION_STATUS"=>$merchantDetail->getAttribute(Entity::GSTIN_VERIFICATION_STATUS),
            "SHOP_ESTABLISHMENT_VERIFICATION_STATUS"=>$merchantDetail->getAttribute(Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS)
        ]);

        if ($canUpdateMerchantContext === true)
        {
            $detailCore = new DetailCore();

            $newActivationStatus = $detailCore->getApplicableActivationStatus($merchantDetail);

            if ($newActivationStatus !== Status::ACTIVATED_KYC_PENDING or $newActivationStatus !== Status::ACTIVATED)
            {
                $clarificationCore = new Core();

                if ($clarificationCore->shouldTriggerNeedsClarification($merchantDetail) === true)
                {
                    $kycClarificationReasons = (new Core())->composeNeedsClarificationReason($merchantDetail);

                    if (empty($kycClarificationReasons) === false)
                    {
                        $input[Entity::KYC_CLARIFICATION_REASONS] = $kycClarificationReasons;

                        $kycClarificationReasons = (new DetailCore())
                            ->getUpdatedKycClarificationReasons($input, $merchantDetail->getId(), DetailConstant::SYSTEM);

                        $merchantDetail->setKycClarificationReasons($kycClarificationReasons);

                        $newActivationStatus = Status::NEEDS_CLARIFICATION;

                        $this->trace->count(Metrics::NEEDS_CLARIFICATION_TRIGGERED_TOTAL);
                    }
                }
            }

            $activationStatus = $merchantDetail->getActivationStatus();

            $this->trace->info(TraceCode::UPDATE_MERCHANT_CONTEXT_JOB,[
                'merchant_id'           => $merchant->getId(),
                'new_activation_status' => $newActivationStatus,
                'old_activation_status' => $activationStatus
            ]);

            if (($activationStatus !== $newActivationStatus) and
                ($activationStatus === Status::UNDER_REVIEW))
            {
                $activationStatusData = [
                    Entity::ACTIVATION_STATUS => $newActivationStatus
                ];

                $detailCore->updateActivationStatus($merchant, $activationStatusData, $merchant);

                if($newActivationStatus === Status::NEEDS_CLARIFICATION)
                {
                    (new Merchant\Core)->appendTag($merchant, "Auto NC");

                    $this->sendAutoNeedsClarificationEvent($merchant);

                    $this->trace->debug(TraceCode::AUTO_NC_TAG_ADDED, [
                        'merchant_id'   => $merchant->getId(),
                        'tags'          => $merchant->tagNames()
                    ]);
                }
            }

            $this->sendSegmentEvents();
        }

        $this->updatePartnerContext($merchant);
    }


    /**
     * Update partner context (activation_status) if possible based on the verification
     * status of fields such as Bank details, PAN or GSTIN
     *
     * @param Merchant\Entity $merchant
     *
     * @return void
     * @throws \RZP\Exception\LogicException
     * @throws \Throwable
     */
    protected function updatePartnerContext(Merchant\Entity $merchant): void
    {
        $partnerActivation = (new PartnerCore())->getPartnerActivation($merchant);

        $canUpdatePartnerContext = !($partnerActivation === null) &&
                                    $this->updateContextRequirements->canUpdatePartnerContext($partnerActivation);

        if ($canUpdatePartnerContext === true)
        {
            $clarificationCore = new Core();

            $newActivationStatus = (new PartnerCore())->getApplicablePartnerActivationStatus($merchant->merchantDetail, $partnerActivation);

            $isSystemBasedNeedsClarificationEnabledForPartner = (new Merchant\Core())->isRazorxExperimentEnable(
                $merchant->getId(),
                RazorxTreatment::SYSTEM_BASED_NEEDS_CLARIFICATION_FOR_PARTNER);

            if (($clarificationCore->shouldTriggerNeedsClarification($partnerActivation) === true) and
                ($isSystemBasedNeedsClarificationEnabledForPartner === true))
            {
                $kycClarificationReasons = (new Core())->composeNeedsClarificationReason($partnerActivation);

                if (empty($kycClarificationReasons) === false)
                {
                    $input[Entity::KYC_CLARIFICATION_REASONS] = $kycClarificationReasons;

                    $kycClarificationReasons = (new PartnerCore())->
                    getUpdatedPartnerKycClarificationReasons($input, $partnerActivation->getMerchantId(), DetailConstant::SYSTEM);

                    $partnerActivation->setKycClarificationReasons($kycClarificationReasons);

                    $newActivationStatus = Status::NEEDS_CLARIFICATION;

                    $this->trace->count(Metrics::PARTNER_NEEDS_CLARIFICATION_TRIGGERED_TOTAL);
                }
            }

            $activationStatus = $partnerActivation->getActivationStatus();

            $this->trace->info(TraceCode::UPDATE_PARTNER_CONTEXT,[
                'partner_id'            => $merchant->getId(),
                'new_activation_status' => $newActivationStatus,
                'old_activation_status' => $activationStatus
            ]);

            if (($activationStatus !== $newActivationStatus) and
                ($activationStatus === Status::UNDER_REVIEW))
            {
                $activationStatusData = [
                    Entity::ACTIVATION_STATUS => $newActivationStatus
                ];

                (new PartnerActivationCore)->updatePartnerActivationStatus($merchant, $partnerActivation, $merchant, $activationStatusData);

                if($newActivationStatus === Status::NEEDS_CLARIFICATION)
                {
                    (new Merchant\Core)->appendTag($merchant, "Partner Auto NC");

                    $this->sendAutoNeedsClarificationEvent($merchant, E::PARTNER_ACTIVATION);

                    $this->trace->debug(TraceCode::PARTNER_AUTO_NC_TAG_ADDED, [
                        'partner_id' => $merchant->getId(),
                        'tags'       => $merchant->tagNames()
                    ]);
                }
            }

            $this->sendSegmentEvents();
        }
    }

    protected function sendSegmentEvents()
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

    protected function sendAutoNeedsClarificationEvent($merchant, $source = null)
    {
        if(empty($this->validationId) === true)
        {
            return;
        }

        try
        {
            $validation = (new BvsValidation\Core)->getValidation($this->validationId);

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

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::UPDATE_MERCHANT_CONTEXT_JOB_DELETE, [
                Entity::MERCHANT_ID => $this->merchantId,
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();

            $this->trace->count(Metrics::UPDATE_CONTEXT_JOB_MAX_RETRIED_TOTAL);
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
