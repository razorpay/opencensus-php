<?php

namespace RZP\Models\Partner\Activation;

use Mail;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Partner\Metric as PartnerMetrics;
use RZP\Models\State;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Services\Workflow;
use RZP\Models\State\Reason;
use RZP\Models\Partner\Metric;
use RZP\Models\Merchant\Detail;
use RZP\Models\Partner\Activation;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Mail\Merchant\PartnerActivationRejection as RejectionMail;
use RZP\Mail\Merchant\PartnerActivationConfirmation as ActivationMail;
use RZP\Mail\Merchant\PartnerNeedsClarificationEmail as ClarificationEmail;
use RZP\Services\Segment\EventCode as SegmentEvent;

class Core extends Base\Core
{
    const PARTNER_ACTIVATION_CREATE_MUTEX_PREFIX = 'api_partner_activation_create_';

    /**
     * Creates a partner activation entity for a partner merchant
     * case 1: partner merchant is activated and $considerActivatedMerchant = true
     *      - will be used currently when partner merchant becomes partner
     *      - create partner activation entity with merchant activation details
     * case 2: partner merchant is not activated and $considerActivatedMerchant = true
     *      - To restrict creation of partner activation entity for current partners who are not activated
     *      - Do not create partner activation entity
     * case 3: partner merchant is not activated and $considerActivatedMerchant = false
     *      - This will be used in future once partner kyc goes live and for back fill as well
     *      - create partner activation entity without any merchant activation(if not activated) details and fill partner activation as per partner KYC
     *      - create partner activation entity with merchant activation details if partner merchant is activated
     *
     * @param Merchant\Entity $merchant
     * @param bool            $considerActivatedMerchant
     *
     * @return Entity
     */
    public function createOrFetchPartnerActivationForMerchant(Merchant\Entity $merchant, bool $considerActivatedMerchant = true)
    {
        $partnerActivation = $merchant->partnerActivation;

        $merchantDetails = $merchant->merchantDetail;

        if ($merchant->isPartner() === true and empty($partnerActivation) === true and empty($merchantDetails) === false)
        {
            $this->trace->count(Metric::PARTNERS_KYC_STARTED_TOTAL);
            $partnerActivation = $this->createPartnerActivationForMerchant($merchant, $merchantDetails, $considerActivatedMerchant);

            $merchant->setRelation(Merchant\Entity::PARTNER_ACTIVATION, $partnerActivation);
        }

        return $partnerActivation;
    }

    /**
     * Creates a partner associated to merchant. We use this entity for processing partner activation
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param bool            $considerActivatedMerchant
     *
     * @return Entity
     */
    protected function createPartnerActivationForMerchant(Merchant\Entity $merchant, Detail\Entity $merchantDetails, bool $considerActivatedMerchant)
    {
        $mutexResource = self::PARTNER_ACTIVATION_CREATE_MUTEX_PREFIX . $merchant->getId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function() use ($merchant, $merchantDetails, $considerActivatedMerchant) {

            return $this->createPartnerActivation($merchant, $merchantDetails, $considerActivatedMerchant);
        });
    }

    /**
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param bool            $considerActivatedMerchant
     *
     * @return Activation\Entity
     */
    private function createPartnerActivation(Merchant\Entity $merchant, Detail\Entity $merchantDetails, bool $considerActivatedMerchant)
    {

        if ($merchantDetails->getActivationStatus() !== Constants::ACTIVATED and $considerActivatedMerchant === true)
        {
            return null;
        }
        // this is required if another thread gets the lock immediately
        // after the previous thread releases the lock. So we refresh the relation and if found, we return

        $merchant->load(Merchant\Entity::PARTNER_ACTIVATION);

        $partnerActivation = $merchant->partnerActivation;

        if (empty($partnerActivation) === false)
        {
            return $partnerActivation;
        }

        $partnerActivation = new Activation\Entity;

        $partnerActivation->merchant()->associate($merchant);

        $input = $this->populateCommonActivationFields($merchant, $merchantDetails);

        $partnerActivation->build($input);

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_CREATION_DETAILS, $partnerActivation->toArrayPublic());

        $this->repo->partner_activation->saveOrFail($partnerActivation);

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_CREATION_SUCCESS, [
            'merchant_id' => $merchant->getId()
        ]);

        $this->trace->count(Metric::PARTNER_ACTIVATION_CREATE_TOTAL, ['partner_type' => $merchant->getPartnerType()]);

        if($partnerActivation->getActivationStatus() === Constants::ACTIVATED)
        {
            $this->trace->info(TraceCode::PARTNER_AUTO_ACTIVATION_FROM_MERCHANT_SUCCESS, [
                'merchant_id' => $merchant->getId()
            ]);

            $this->trace->count(Metric::PARTNER_ACTIVATION_AUTO_ACTIVATE_SUCCESS_TOTAL, ['partner_type' => $merchant->getPartnerType()]);
        }

        return $partnerActivation;
    }

    /**
     * @param string $reviewerId
     * @param array  $merchants
     *
     * @return array
     */
    public function bulkAssignReviewer(string $reviewerId, array $merchants): array
    {
        $success     = 0;

        $failedItems = [];

        try
        {
            AdminEntity::verifyIdAndStripSign($reviewerId);

            $reviewer = $this->repo->admin->findOrFailPublic($reviewerId);
        }
        catch (\Exception $e)
        {
            $response = [
                'success' => 0,
                'failed'  => count($merchants),
                'error'   => $e->getMessage(),
            ];

            return $response;
        }

        foreach ($merchants as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                (new Merchant\Validator())->validateIsPartner($merchant);

                $partnerActivation = $this->createOrFetchPartnerActivationForMerchant($merchant, false);

                $partnerActivation->edit([Entity::REVIEWER_ID => $reviewerId]);

                $partnerActivation->reviewer()->associate($reviewer);

                $this->repo->partner_activation->saveOrFail($partnerActivation);

                $success = $success + 1;
            }
            catch (\Exception $e)
            {
                $failedItems[] = [
                    Entity::MERCHANT_ID => $merchantId,
                    'error'             => $e->getMessage()
                ];
            }
        }

        $response = [
            'success'     => $success,
            'failed'      => count($failedItems),
            'failedItems' => $failedItems,
        ];

        return $response;
    }

    /**
     * In case the merchant wants to convert to a partner after merchant is activated, mark partner activation as
     * activated
     * Reason: partner activation is a subset of merchant activation
     * 1. we will not consider workflow management for partner activation in this case.
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     *
     * @return array
     */
    public function populateCommonActivationFields(Merchant\Entity $merchant, Detail\Entity $merchantDetails): array
    {
        $input = [];

        if ($merchantDetails->getActivationStatus() === Constants::ACTIVATED)
        {
            $this->populateCommonFields($input, $merchantDetails, Constants::COMMON_ACTIVATION_FIELDS_MERCHANT_DETAILS);

            $this->populateCommonFields($input, $merchant, Constants::COMMON_ACTIVATION_FIELDS_MERCHANT);

            $now = Carbon::now()->getTimestamp();

            $input[Entity::ACTIVATED_AT] = $now;

            $input[Entity::SUBMITTED_AT] = $now;
        }

        return $input;
    }

    private function populateCommonFields(array &$input, $sourceArr, $commonFields)
    {
        foreach ($commonFields as $key => $val)
        {
            if (empty($sourceArr[$val]) === false)
            {
                $input[$key] = $sourceArr[$val];
            }
        }
    }

    /**
     * This function auto activates a partner merchant when merchant is getting activated by admin
     * Case 1: Partner activation entity has not been created (old partners or new partners who got created when merchant is not activated)
     *      - Create partner activation and auto activate partner activation.
     *      - This would become an invalid case after back fill job is completed
     * Case 2: Partner activation is under review
     *       - Auto activate partner and do not create workflow for the same. Send partner activation related events
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity $merchantDetails
     * @param Base\PublicEntity $maker
     *
     * @throws \Throwable
     */
    public function autoActivatePartnerIfApplicable(Merchant\Entity $merchant, Detail\Entity $merchantDetails,
                                                    Base\PublicEntity $maker)
    {
        try
        {
            if (($merchantDetails->getActivationStatus() === Constants::ACTIVATED) and ($merchant->isPartner() === true)
                and ($maker->getEntityName() === Entity::ADMIN))
            {
                $partnerActivation = $this->createOrFetchPartnerActivationForMerchant($merchant);

                $partnerActivationStatus = $partnerActivation->getActivationStatus();

                if (empty($partnerActivationStatus) === true)
                {
                    $commonFields = $this->populateCommonActivationFields($merchant, $merchantDetails);

                    $partnerActivation->edit($commonFields);

                    $this->repo->partner_activation->saveOrFail($partnerActivation);

                    $this->trace->info(TraceCode::PARTNER_AUTO_ACTIVATION_FROM_MERCHANT_SUCCESS, [
                        'merchant_id' => $merchant->getId()
                    ]);
                }
                else if ($partnerActivationStatus === Constants::UNDER_REVIEW)
                {
                    $activationStatusData = [
                        Detail\Entity::ACTIVATION_STATUS => Constants::ACTIVATED,
                        Constants::TRIGGER_WORKFLOW      => false,
                    ];

                    $this->updatePartnerActivationStatus($merchant, $partnerActivation, $maker, $activationStatusData);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::PARTNER_AUTO_ACTIVATION_FROM_MERCHANT_FAILED, [
                'merchant_id' => $merchant->getId()
            ]);

            $this->trace->count(Metric::PARTNER_ACTIVATION_AUTO_ACTIVATE_FAILURE_TOTAL);
        }
    }

    /**
     * This function marks partner form as NC which was under review if merchant form is marked as NC by the admin
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity $merchantDetails
     * @param Base\PublicEntity $maker
     *
     * @throws \Throwable
     */
    public function markPartnerFormAsNCIfApplicable(Merchant\Entity $merchant, Detail\Entity $merchantDetails, Base\PublicEntity $maker)
    {
        try
        {
            if (($merchantDetails->getActivationStatus() === Constants::NEEDS_CLARIFICATION) and ($merchant->isPartner() === true)
                and ($maker->getEntityName() === Entity::ADMIN))
            {
                $partnerActivation = $this->createOrFetchPartnerActivationForMerchant($merchant);

                if ($partnerActivation->getActivationStatus() === Constants::UNDER_REVIEW)
                {
                    $partnerCore = (new \RZP\Models\Partner\Core);

                    $kycClarificationReasonsData = [
                        Detail\Entity::KYC_CLARIFICATION_REASONS => $merchantDetails->getKycClarificationReasons()
                    ];

                    $partnerKycClarificationReasons = $partnerCore->
                    fetchCommonFieldsFromMerchantKycClarificationReasons($kycClarificationReasonsData, $merchant);

                    if (empty($partnerKycClarificationReasons) === true)
                    {
                        return;
                    }

                    $kycClarificationReasonsData[Detail\Entity::KYC_CLARIFICATION_REASONS] = $partnerKycClarificationReasons;

                    $partnerKycClarificationReasons = $partnerCore->
                    getUpdatedPartnerKycClarificationReasons($kycClarificationReasonsData, $merchant->getId());

                    $partnerActivation->setKycClarificationReasons($partnerKycClarificationReasons);

                    $activationStatusData = [
                        Detail\Entity::ACTIVATION_STATUS => Constants::NEEDS_CLARIFICATION,
                        Constants::TRIGGER_WORKFLOW      => false
                    ];

                    $this->updatePartnerActivationStatus($merchant, $partnerActivation, $maker, $activationStatusData);

                    $this->trace->info(TraceCode::PARTNER_AUTO_NC_FROM_MERCHANT_NC_SUCCESS, [
                        'merchant_id' => $merchant->getId()
                    ]);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::PARTNER_AUTO_NC_FROM_MERCHANT_NC_FAILED, [
                'merchant_id' => $merchant->getId()
            ]);
        }
    }

    /**
     * This function supports the admin to hold commissions/ release commissions for a partner
     * Supported actions: ['hold_commissions', 'release_commissions']
     * @param Entity $partnerActivation
     * @param array  $actionData
     *
     * @return Entity
     * @throws Exception\LogicException
     */
    public function performAction(Entity $partnerActivation, array $actionData)
    {
        $partnerActivationValidator = $partnerActivation->getValidator();

        $partnerActivationValidator->validateInput('action', $actionData);

        $action = $actionData[Activation\Constants::ACTION];

        $validationFunction = 'validate'.studly_case($action);

        $partnerActivationValidator->$validationFunction($partnerActivation);

        $holdFunds = ($action === Activation\Action::HOLD_COMMISSIONS);

        $input = [Activation\Entity::HOLD_FUNDS => $holdFunds];

        $partnerActivation->edit($input);

        $this->repo->partner_activation->saveOrFail($partnerActivation);

        return $partnerActivation;
    }

    /**
     * This function does the following
     * 1. updates the activation status of a partner
     * 2. Maintains the state transition for the partner_activation status
     * 3. In case of needs clarification status change, sends a needs clarification email
     * @param Merchant\Entity   $merchant
     * @param Entity            $partnerActivation
     * @param Base\PublicEntity $maker
     * @param array             $input
     *
     * @return array
     * @throws \Throwable
     */
    public function updatePartnerActivationStatus(Merchant\Entity $merchant, Entity $partnerActivation, Base\PublicEntity $maker, array $input)
    {
        $triggerWorkflow = true;

        if (isset($input[Constants::TRIGGER_WORKFLOW]) === true)
        {
            $triggerWorkflow = $input[Constants::TRIGGER_WORKFLOW];

            unset($input[Constants::TRIGGER_WORKFLOW]);
        }

        $partnerActivation->getValidator()->validateInput('activationStatus', $input);

        $currentActivationStatus = $partnerActivation->getActivationStatus();

        $partnerActivation->getValidator()
                          ->validateActivationStatusChange(
                              $currentActivationStatus,
                              $input[Entity::ACTIVATION_STATUS]);

        $this->trace->info(
            TraceCode::PARTNER_UPDATE_ACTIVATION_STATUS,
            ['input' => $input]);

        $rejectionReasons = [];

        if (empty($input[Entity::REJECTION_REASONS]) === false)
        {
            $rejectionReasons = $input[Entity::REJECTION_REASONS];

            unset($input[Entity::REJECTION_REASONS]);
        }

        $oldPartnerActivation = clone $partnerActivation;

        $partnerActivation->edit($input);

        $newPartnerActivation = clone $partnerActivation;

        $partnerActivation->edit($input);

        $this->repo->transactionOnLiveAndTest(function() use ($input, $merchant) {
            switch ($input[Entity::ACTIVATION_STATUS])
            {
                case Constants::ACTIVATED:
                    $this->trace->count(PartnerMetrics::PARTNERS_ACTIVATED_TOTAL);
                    // If merchant gets Activated, onboarding WF's should get auto-approved
                    (new ActionCore)->handleOnboardingWorkflowActionIfOpen(
                        $merchant->getId(), 'partner_activation', State\Name::APPROVED);
                    break;
                case Constants::REJECTED:
                    // If merchant gets Rejected, onboarding WF's should get auto-closed
                    (new ActionCore)->handleOnboardingWorkflowActionIfOpen(
                        $merchant->getId(), 'partner_activation', State\Name::CLOSED);
                    break;
                case Constants::NEEDS_CLARIFICATION:
                    // If merchant goes to NC, onboarding WF's should get auto-rejected
                    (new ActionCore)->handleOnboardingWorkflowActionIfOpen(
                        $merchant->getId(), 'partner_activation', State\Name::REJECTED);
                    break;
            }
        });

        $this->repo->transactionOnLiveAndTest(function() use (
            $partnerActivation,
            $input,
            $maker, $merchant, $rejectionReasons,
            $oldPartnerActivation, $newPartnerActivation, $triggerWorkflow
        ) {

            $detailCore = (new Detail\Core());

            if ($input[Entity::ACTIVATION_STATUS] === Constants::ACTIVATED)
            {
                /*
                 * Setup workflow for activation_status change in partner_activation entity,
                 * which will be triggered once all the validations are checked in the activate method.
                 */
                $original = $oldPartnerActivation->toArrayPublic();
                $dirty    = $newPartnerActivation->toArrayPublic();

                unset($original[Activation\Entity::ALLOWED_NEXT_ACTIVATION_STATUSES]);
                unset($dirty[Activation\Entity::ALLOWED_NEXT_ACTIVATION_STATUSES]);

                $this->app['workflow']
                    ->setEntity($partnerActivation->getEntity())
                    ->setEntityId($partnerActivation->getMerchantId())
                    ->setOriginal($original)
                    ->setDirty($dirty);

                $this->activate($partnerActivation, $merchant, $triggerWorkflow);
            }

            if ($input[Entity::ACTIVATION_STATUS] === Constants::REJECTED)
            {
                $partnerActivation->deactivate();

                $this->sendRejectionEmail($merchant);
            }

            if ($input[Entity::ACTIVATION_STATUS] === Constants::NEEDS_CLARIFICATION)
            {
                if (empty($partnerActivation->getKycClarificationReasons()) === false)
                {
                    $partnerActivation->setLocked(false);

                    $this->sendNeedsClarificationEmail($merchant, $partnerActivation);
                }
            }

            $stateData = [
                State\Entity::NAME => $input[Entity::ACTIVATION_STATUS],
            ];

            $state = (new State\Core)->createForMakerAndEntity($stateData, $maker, $partnerActivation);

            $this->repo->saveOrFail($partnerActivation);

            $properties = [
                'partner_id'  =>  $merchant->getId(),
                'kyc_status'  =>  $input[Entity::ACTIVATION_STATUS]
            ];

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $properties, SegmentEvent::KYC_STATUS_CHANGE);

            if (empty($rejectionReasons) === false)
            {
                (new Reason\Core)->addRejectionReasons($rejectionReasons, $state);
            }

        });

        return $partnerActivation->toArrayPublic();
    }

    /**
     * The following function activates the requirements needed for a partner to earn commissions
     * It does creating bankaccount, release funds, setting activatedAt and creating balance config for the partner
     *
     * @param Entity          $partnerActivation
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     * @throws \Throwable
     */
    protected function activate(Entity $partnerActivation, Merchant\Entity $merchant, bool $triggerWorkflow = true): Entity
    {
        $merchantDetail = $merchant->merchantDetail;

        if ($this->shouldCreateBankAccount($merchantDetail) === true)
        {
            (new Detail\Core)->setBankAccountForMerchant($merchant);

            $merchant->getValidator()->validateHasBankAccount();
        }

        $partnerActivation->releaseFunds();

        $partnerActivation->setActivatedAt(time());

        if ($triggerWorkflow === true)
        {
            // Triggering workflow for the activation_status change in partner_activation entity
            $this->app['workflow']
                ->handle();
        }

        $merchantCore = new Merchant\Core;

        $merchantBalance = $merchantCore->createBalance($merchant, 'live');

        $merchantCore->createBalanceConfig($merchantBalance, 'live');

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $partnerActivation, $merchantCore) {
            $this->repo->saveOrFail($merchant);

            $partnerActivation->setLocked(true);

            $this->repo->saveOrFail($partnerActivation);

        });

        $this->sendPartnerActivationEvents($merchant);

        return $partnerActivation;
    }

    private function shouldCreateBankAccount(Detail\Entity $merchantDetail): bool
    {
        return ($merchantDetail->hasBankAccountDetails() === true);
    }

    private function sendRejectionEmail(Merchant\Entity $merchant)
    {
        $partnerKycCommEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::PARTNER_KYC_COMMUNICATION);

        if ($partnerKycCommEnabled !== true)
        {
            return;
        }

        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $data = [
            'name'  => $merchant->getName(),
            'email' => $merchant->getEmail(),
            'id'    => $merchant->getId(),
        ];

        $rejectionMail = new RejectionMail($data, $org->toArray());

        Mail::queue($rejectionMail);
    }

    /**
     * This function would format the needs clarification reasons and sends an email to the partner
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $partnerActivation
     */
    private function sendNeedsClarificationEmail(Merchant\Entity $merchant, Entity $partnerActivation)
    {
        $partnerKycCommEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::PARTNER_KYC_COMMUNICATION);

        if ($partnerKycCommEnabled !== true)
        {
            return;
        }

        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $clarificationCore = new Detail\NeedsClarification\Core();

        $clarificationReasons = $clarificationCore->getFormattedKycClarificationReasons(
            $partnerActivation->getKycClarificationReasons());

        $data = (new Detail\Core)->getPayloadForClarificationEmail($merchant, $org, $clarificationReasons);

        $email = new ClarificationEmail($data, $org->toArray());

        Mail::queue($email);
    }

    /**
     * @param Merchant\Entity $merchant
     */
    private function sendPartnerActivationEvents(Merchant\Entity $merchant)
    {
        $partnerKycCommEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::PARTNER_KYC_COMMUNICATION);

        if ($partnerKycCommEnabled !== true)
        {
            return;
        }

        $email = new ActivationMail($merchant->getId());

        Mail::queue($email);
    }

    private function resetWorkflowSingleton()
    {
        $this->app['workflow'] =  new Workflow\Service($this->app);
    }
}

