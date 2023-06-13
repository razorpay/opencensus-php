<?php

namespace RZP\Jobs;

use App;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\Middleware\EventTracker;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Website;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\NeedsClarification\Core;
use RZP\Models\Merchant\Detail\NeedsClarification\Metrics;
use RZP\Models\Partner\Metric as PartnerMetrics;
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

        $startTime = microtime(true);

        try
        {
            $tracePayload = [
                Entity::MERCHANT_ID => $this->merchantId,
                'bvs_validation_id' => $this->validationId,
                'start_time'        => $startTime
            ];

            $this->trace->info(TraceCode::TRIGGER_UPDATE_MERCHANT_CONTEXT_JOB, $tracePayload);

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
                    'bvs_validation_id' => $this->validationId,
                    'attempts'          => $this->attempts()
                ]);

            $this->checkRetry();
        }
        finally
        {
            $this->trace->info(TraceCode::TRIGGER_UPDATE_MERCHANT_CONTEXT_JOB, [
                Entity::MERCHANT_ID => $this->merchantId,
                'bvs_validation_id' => $this->validationId,
                'duration'          => (microtime(true) - $startTime) * 1000,
            ]);
        }

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
        $startTime = microtime(true);

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

            $app = App::getFacadeRoot();

            $splitzResult = $detailCore->getSplitzResponse($this->merchantId, 'merchant_automation_activation_exp_id');

            $businessDetailMetadata = optional($app['repo']->merchant_business_detail->getBusinessDetailsForMerchantId($this->merchantId))->getMetadata();

            if ((empty($businessDetailMetadata['activation_status']) === true) and
                (in_array($splitzResult, [Constants::SPLITZ_PILOT, Constants::SPLITZ_LIVE, Constants::SPLITZ_KQU]) === true))
            {
                try
                {
                    (new BusinessDetail\Service)->saveBusinessDetailsForMerchant($this->merchantId, [
                        BusinessDetail\Entity::METADATA => [
                            'activation_status' => $newActivationStatus
                        ]
                    ]);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->traceException($ex, Logger::ERROR, TraceCode::MERCHANT_EDIT_BUSINESS_DETAILS_FAILED);
                }
            }

            if (($newActivationStatus === Status::ACTIVATED and $splitzResult === Merchant\Constants::SPLITZ_LIVE) or
                ($newActivationStatus === Status::KYC_QUALIFIED_UNACTIVATED and $splitzResult === Merchant\Constants::SPLITZ_KQU))
            {
                // save website policy links
                $websitePolicy = $app['repo']->merchant_verification_detail->getDetailsForTypeAndIdentifierFromReplica(
                    $this->merchantId,
                    Constant::WEBSITE_POLICY,
                    MVD\Constants::NUMBER
                );

                $websitePolicyResult = $websitePolicy->getMetadata();

                $websitePolicyLinks = [];

                foreach ($websitePolicyResult as $policy => $value)
                {
                    if (empty($value['analysis_result']['links_found'][0]) === false)
                    {
                        $websitePolicyLinks[$policy]['url'] = $value['analysis_result']['links_found'][0];
                    }
                }

                $websiteDetail = $app['repo']->merchant_website->getWebsiteDetailsForMerchantId($this->merchantId);

                $adminWebsiteDetails = optional($websiteDetail)->getAdminWebsiteDetails() ?? [];

                $additionalData = optional($websiteDetail)->getAdditionalData() ?? [];

                $input = [
                    Website\Entity::ADDITIONAL_DATA => array_replace_recursive($additionalData, [
                        'admin_website_details' => $adminWebsiteDetails
                    ]),
                    Website\Entity::ADMIN_WEBSITE_DETAILS => array_replace_recursive($adminWebsiteDetails, [
                        'website' => [
                            $merchantDetail->getWebsite() => $websitePolicyLinks
                        ]
                    ]),
                ];

                (new Website\Core)->createOrEditWebsiteDetails($merchantDetail, $input);

                // save category & subcategory
                $businessDetailsInput = [
                    BusinessDetail\Entity::METADATA => [
                        DetailEntity::BUSINESS_CATEGORY     => $merchantDetail->getBusinessCategory(),
                        DetailEntity::BUSINESS_SUBCATEGORY  => $merchantDetail->getBusinessSubcategory(),
                        'mcc'                               => $merchant->getCategory()
                    ]
                ];

                try
                {
                    (new BusinessDetail\Service())->saveBusinessDetailsForMerchant($this->merchantId, $businessDetailsInput);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->traceException($ex, Trace::ERROR, TraceCode::MERCHANT_EDIT_BUSINESS_DETAILS_FAILED);
                }

                $mccCategorisation = $app['repo']->merchant_verification_detail->getDetailsForTypeAndIdentifierFromReplica(
                    $this->merchantId,
                    Constant::MCC_CATEGORISATION_WEBSITE,
                    MVD\Constants::NUMBER
                );

                $mccResult = $mccCategorisation->getMetadata();

                $merchantInput = [
                    MerchantEntity::CATEGORY    => strval($mccResult[MVD\Constants::PREDICTED_MCC]),
                    MerchantEntity::CATEGORY2   => $mccResult[MVD\Constants::CATEGORY]
                ];

                $merchant->edit($merchantInput);

                $app['repo']->merchant->saveOrFail($merchant);

                $merchantDetailInput = [
                    DetailEntity::BUSINESS_CATEGORY    => $mccResult[MVD\Constants::CATEGORY],
                    DetailEntity::BUSINESS_SUBCATEGORY => $mccResult[MVD\Constants::SUBCATEGORY],
                ];

                $merchantDetail->edit($merchantDetailInput);

                $app['repo']->merchant_detail->saveOrFail($merchantDetail);
            }

            if ($newActivationStatus !== Status::ACTIVATED_KYC_PENDING and $newActivationStatus !== Status::ACTIVATED)
            {
                $clarificationCore = new Core();

                if ($merchant->isLinkedAccount() === true)
                {
                    $this->trace->info(TraceCode::SHOULD_TRIGGER_NEEDS_CLARIFICATION,
                        [
                            'shouldTriggerNeedsClarification'   =>  $clarificationCore->shouldTriggerNeedsClarification($merchantDetail),
                            '$kycClarificationReasons'          =>  (new Core())->composeNeedsClarificationReason($merchantDetail)
                        ]);
                }

                if ($clarificationCore->shouldTriggerNeedsClarification($merchantDetail) === true)
                {
                    $kycClarificationReasons = (new Core())->composeNeedsClarificationReason($merchantDetail);

                    if (empty($kycClarificationReasons) === false)
                    {
                        $input[Entity::KYC_CLARIFICATION_REASONS] = $kycClarificationReasons;

                        $kycClarificationReasons = (new DetailCore())
                            ->getUpdatedKycClarificationReasons($input, $merchantDetail->getId(), DetailConstant::SYSTEM);

                        $this->trace->info(TraceCode::MERCHANT_CONTEXT_KYC_CLARIFICATION_REASON, [
                            'merchant_id'              => $merchant->getId(),
                            'kycClarificationReasons'  => $kycClarificationReasons,
                            'bvs_validation_id'        => $this->validationId,
                            'duration' =>  (microtime(true) - $startTime) * 1000,
                        ]);

                        $merchantDetail->setKycClarificationReasons($kycClarificationReasons);

                        $newActivationStatus = Status::NEEDS_CLARIFICATION;

                        $this->trace->count(Metrics::NEEDS_CLARIFICATION_TRIGGERED_TOTAL);
                    }

                    $clarificationCore->removeNoDocFeatureIfApplicable($merchant, $merchantDetail);
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

                //
                //  - When linked accounts are activated, there are set of other functions to be executed
                //  - which are defined in autoActivateMerchantIfApplicable.
                //  - Since this is the first time linked accounts are not directly activated and going throught
                //  - the stages of under_review, needs_clarification... We check if status is 'activated' then
                //  - call the auto activate method here.  Jira:EPA-168
                //
                if ($merchant->isLinkedAccount() === true and $newActivationStatus === Status::ACTIVATED)
                {
                    $detailCore->autoActivateMerchantIfApplicable($merchant);
                }
                else
                {
                    $detailCore->updateActivationStatus($merchant, $activationStatusData, $merchant);

                    $this->trace->info(TraceCode::UPDATE_ACTIVATION_STATUS_DURATION, [
                        'merchant_id'              => $merchant->getId(),
                        'bvs_validation_id'        => $this->validationId,
                        'duration' =>  (microtime(true) - $startTime) * 1000,
                    ]);
                }

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

        if($merchant->isResellerPartner())
        {
            $this->updatePartnerContext($merchant);
        }

        $this->pushJobProcessingTimeMetrics($merchant, $startTime);
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

            $properties = [
                'id'            => $merchant->getId(),
                'experiment_id' => $this->app['config']->get('app.partner_independent_kyc_exp_id'),
            ];

            $isSystemBasedNeedsClarificationEnabledForPartner = (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable', TraceCode::SYSTEM_BASED_NEEDS_CLARIFICATION_FOR_PARTNER_ERROR);

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
            $dimension = array("activation_status" => $activationStatus);
            $this->trace->count(PartnerMetrics::PARTNERS_KYC_ACTIVATION_STATUS_TOTAL, $dimension);

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

    private function pushJobProcessingTimeMetrics(Merchant\Entity $merchant, float $startTime)
    {
        try
        {
            $artefactType = $this->repoManager->bvs_validation->getArtefactTypeFromValidationId($this->validationId);

            $onboardingFlow = $merchant->isNoDocOnboardingEnabled()? 'no_doc_onboarding': 'normal_onboarding';

            if(sizeof($artefactType) === 1)
            {
                $dimensions = [
                    'artefact_type'     => $artefactType[0],
                    'onboarding_flow'   => $onboardingFlow
                ];

                $timeTaken = millitime() - $startTime;

                $this->trace->histogram(Metrics::UPDATE_CONTEXT_JOB_PROCESSING_IN_MS, $timeTaken, $dimensions);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPDATE_MERCHANT_CONTEXT_METRICS_FAILURE,
                [
                    'merchant_id'       => $this->merchantId,
                    'bvs_validation_id' => $this->validationId,
                ]);
        }
    }
}
