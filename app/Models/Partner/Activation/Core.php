<?php

namespace RZP\Models\Partner\Activation;

use Mail;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Partner\Metric;
use RZP\Models\State;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\State\Reason;
use RZP\Models\Merchant\Detail;
use RZP\Models\Partner\Activation;
use RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Mail\Merchant\NeedsClarificationEmail as ClarificationEmail;
use RZP\Models\Merchant\Detail\NeedsClarification\UpdateContextRequirements as Requirements;

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
     * This function auto activates a partner merchant when merchant is getting activated
     * Case 1: Partner activation entity has not been created (old partners or new partners who got created when merchant is not activated)
     *      - Create partner activation and auto activate partner activation.
     *      - This would become an invalid case after back fill job is completed
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     */
    public function autoActivatePartnerIfApplicable(Merchant\Entity $merchant, Detail\Entity $merchantDetails)
    {
        try
        {
            if (($merchantDetails->getActivationStatus() === Constants::ACTIVATED) and ($merchant->isPartner() === true))
            {
                $partnerActivation = $this->createOrFetchPartnerActivationForMerchant($merchant);

                if ($partnerActivation->getActivationStatus() !== Constants::ACTIVATED)
                {
                    $commonFields = $this->populateCommonActivationFields($merchant, $merchantDetails);

                    $partnerActivation->edit($commonFields);

                    $this->repo->partner_activation->saveOrFail($partnerActivation);

                    $this->trace->info(TraceCode::PARTNER_AUTO_ACTIVATION_FROM_MERCHANT_SUCCESS, [
                        'merchant_id' => $merchant->getId()
                    ]);
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
            $oldPartnerActivation, $newPartnerActivation
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

                $this->activate($partnerActivation, $merchant);
            }

            if ($input[Entity::ACTIVATION_STATUS] === Constants::REJECTED)
            {
                $partnerActivation->deactivate();

                $detailCore->sendRejectionEmail($merchant);
            }

            if ($input[Entity::ACTIVATION_STATUS] === Constants::NEEDS_CLARIFICATION)
            {
                if (empty($partnerActivation->getKycClarificationReasons()) === false)
                {
                    $partnerActivation->setLocked(false);

                    //TODO - once notifications are finalized, use the below function for needs clarification email
                    //$this->sendNeedsClarificationEmail($merchant, $partnerActivation);
                }
            }

            $stateData = [
                State\Entity::NAME => $input[Entity::ACTIVATION_STATUS],
            ];

            $state = (new State\Core)->createForMakerAndEntity($stateData, $maker, $partnerActivation);

            $this->repo->saveOrFail($partnerActivation);

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
    protected function activate(Entity $partnerActivation, Merchant\Entity $merchant): Entity
    {
        $merchantDetail = $merchant->merchantDetail;

        if ($this->shouldCreateBankAccount($merchantDetail) === true)
        {
            (new Detail\Core)->setBankAccountForMerchant($merchant);

            $merchant->getValidator()->validateHasBankAccount();
        }

        $partnerActivation->releaseFunds();

        $partnerActivation->setActivatedAt(time());

        // Triggering workflow for the activation_status change in partner_activation entity
        $this->app['workflow']
            ->handle();


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

    /**
     * This function would format the needs clarification reasons and sends an email to the partner
     * @param Merchant\Entity $merchant
     * @param Entity          $partnerActivation
     */
    public function sendNeedsClarificationEmail(Merchant\Entity $merchant, Entity $partnerActivation)
    {
        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $clarificationCore = new Detail\NeedsClarification\Core();

        $clarificationReasons = $clarificationCore->getFormattedKycClarificationReasons(
            $partnerActivation->getKycClarificationReasons());

        $data = (new Detail\Core)->getPayloadForClarificationEmail($merchant, $org, $clarificationReasons);

        $email = new ClarificationEmail($data, $org->toArray());

        Mail::queue($email);
    }

    /**
     * TODO:
     * 1. Add events specific to partner activation
     * 2. Send notifications to partner when partner gets activated.
     *    Once template text is finalized, will use the PartnerActivationMail accordingly
     *
     * @param Merchant\Entity $merchant
     */
    private function sendPartnerActivationEvents(Merchant\Entity $merchant)
    {
        //Mail::queue(new PartnerActivationMail($merchant->getId()));
    }

    protected function isPartnerActivationReqFulFilled(array $requiredVerificationFields, Detail\Entity $merchantDetails): bool
    {
        $allReqFulFilled = true;

        foreach ($requiredVerificationFields as $requirementGroup)
        {
            foreach ($requirementGroup as $requirements)
            {
                $statusKey = $requirements[Requirements::STATUS_KEY];

                $verificationStatus = $merchantDetails->getAttribute($statusKey);

                // ignore GSTIN verification status if it is not provided by the partner since it is optional
                if (($statusKey === Detail\Entity::GSTIN_VERIFICATION_STATUS) and
                    ($verificationStatus === null) and
                    ($merchantDetails->getAttribute(Detail\Entity::GSTIN) === null))
                {
                    continue;
                }

                $allReqFulFilled = ($allReqFulFilled and ($verificationStatus === Detail\Constants::VERIFIED));
            }
        }

        return $allReqFulFilled and !empty($requiredVerificationFields);
    }

    public function getApplicablePartnerActivationStatus(Entity $partnerActivation): ?string
    {
        if ($partnerActivation->isSubmitted() === false)
        {
            return null;
        }

        $merchantDetails = $partnerActivation->merchantDetail;

        $requiredVerificationFields = (new Requirements())->getUpdateContextRequirement($partnerActivation);

        $activationReqFulFilled = $this->isPartnerActivationReqFulFilled($requiredVerificationFields, $merchantDetails);

        if ($activationReqFulFilled === true)
        {
            return Detail\Status::ACTIVATED;
        }

        return Detail\Status::UNDER_REVIEW;
    }
}

