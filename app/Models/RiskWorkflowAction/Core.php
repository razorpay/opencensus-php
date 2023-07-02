<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant\Action;
use RZP\Models\Merchant\Balance;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Merchant\Action as MerchantAction;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Validator as MerchantValidator;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;
use RZP\Models\Merchant\Stakeholder\Core  as StakeholderCore;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;
use RZP\Models\Workflow\Action as WorkflowAction;
use RZP\Models\Comment;
use RZP\Models\Admin\Org;

class Core extends Base\Core
{
    /**
     * Validates the merchant wrt action
     * @param $action
     * @param $merchantId
     * @return string
     */
    public function validateMerchantForAction($action, $merchant)
    {
        $validator =  new MerchantValidator($merchant);

        switch ($action)
        {
            case MerchantAction::SUSPEND:
                $validator->validateSuspend();
                break;
            case MerchantAction::UNSUSPEND:
                $validator->validateUnsuspend();
                break;
            case MerchantAction::HOLD_FUNDS:
                $validator->validateHoldFunds();
                break;
            case MerchantAction::RELEASE_FUNDS:
                $validator->validateReleaseFunds();
                break;
            case MerchantAction::LIVE_DISABLE:
                $validator->validateLiveDisable();
                break;
            case MerchantAction::LIVE_ENABLE:
                $validator->validateLiveEnable();
                break;
            case MerchantAction::ENABLE_INTERNATIONAL:
                $validator->validateEnableInternational();
                break;
            case MerchantAction::DISABLE_INTERNATIONAL:
                $validator->validateDisableInternational();
                break;
        }
    }

    protected function getParamsForMerchantAction($riskAction, $riskAttributes)
    {
        if (in_array($riskAction, Merchant\Constants::RISK_CONSTRUCTIVE_ACTION_LIST) === true)
        {
            return [
                Constants::CLEAR_RISK_TAGS => $riskAttributes[Constants::CLEAR_RISK_TAGS],
            ];
        }

        if ($riskAction == Action::ENABLE_INTERNATIONAL)
        {
            return [];
        }

        $params = [
            Constants::TRIGGER_COMMUNICATION => $riskAttributes[Constants::TRIGGER_COMMUNICATION],
        ];

        if (isset($riskAttributes[Constants::RISK_TAG]) === true)
        {
            $params[Constants::RISK_TAG] = $riskAttributes[Constants::RISK_TAG];
        }

        return $params;

    }

    public function validateRiskAttributes(array $input)
    {
        if(isset($input[Constants::RISK_ATTRIBUTES]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Risk Attributes are not provided', null, $input);
        }

        $riskAttributes = $input[Constants::RISK_ATTRIBUTES];

        if(is_array($riskAttributes) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Risk Attributes provided is malformed', null, $input);
        }

        // assuming that this is already validated at the bulk merchant action layer
        $riskAction = $input['action'];

        if (in_array($riskAction, Merchant\Constants::RISK_CONSTRUCTIVE_ACTION_LIST) === true)
        {
            (new Validator())->validateInput(
                Constants::CREATE_CONSTRUCTIVE_RISK_ATTRIBUTES_VALIDATOR,
                $riskAttributes);
        }
        else
        {
            if ($riskAction == Action::ENABLE_INTERNATIONAL)
            {
                (new Validator())->validateInput(
                    Constants::CREATE_ENABLE_INTERNATIONAL_RISK_ATTRIBUTES_VALIDATOR,
                    $riskAttributes);
            }
            else
            {
                if ($riskAction == Action::DISABLE_INTERNATIONAL)
                {
                    (new Validator())->validateInput(
                        Constants::CREATE_DISABLE_INTERNATIONAL_RISK_ATTRIBUTES_VALIDATOR,
                        $riskAttributes);
                }
                else
                {
                    (new Validator())->validateInput(
                        Constants::CREATE_DESTRUCTIVE_RISK_ATTRIBUTES_VALIDATOR,
                        $riskAttributes);
                }

                (new Validator())->validateRiskReasonAndSubReason($riskAttributes[Constants::RISK_REASON],
                                                                  $riskAttributes[Constants::RISK_SUB_REASON]);
            }
        }
    }

    public function createRiskWorkflowAction($input, $maker = null, $routeName = null, bool $settlementClearance = false)
    {
        try {
            $riskAction = $input[Constants::ACTION];

            $merchantId= $input[Constants::MERCHANT_ID];

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $merchantDetails = $merchant->merchantDetail;

            $this->validateMerchantForAction($riskAction, $merchant);

            $riskAttributes = $input[Constants::RISK_ATTRIBUTES];

            $workflowTags = $input['workflow_tags'] ?? null;

            $this->trace->info(TraceCode::CREATE_RISK_ACTION_REQUEST,
                [
                    'merchant_id'     => $merchantId,
                    'risk_action'     => $riskAction,
                    'risk_attributes' => $riskAttributes,
                    'workflow_tags'   => $workflowTags
                ]);

            $bulkActionId = $input[Constants::BULK_WORKFLOW_ACTION_ID] ?? null;

            $tags = $this->getTags($riskAttributes, $workflowTags, $settlementClearance);

            if (isset($input[Constants::RISK_ATTRIBUTES]) && isset($input[Constants::RISK_ATTRIBUTES][Constants::RISK_SOURCE])
                && $input[Constants::RISK_ATTRIBUTES][Constants::RISK_SOURCE] === Constants::RISK_SOURCE_MERCHANT_RISK_ALERTS)
            {
                $this->app['basicauth']->setOrgId(Org\Entity::RAZORPAY_ORG_ID);

                $workflowActions = (new WorkflowAction\Core)->fetchOpenActionOnEntityOperation(
                    $merchant->getId(), 'merchant', Permission\Name::EDIT_MERCHANT_DISABLE_INTERNATIONAL);

                if ($workflowActions->isNotEmpty() === true)
                {
                    $this->addTagsForOpenWorkflowIfApplicable($workflowTags, $workflowActions, $maker);
                }

            }


            $riskAttributesParams = $this->getParamsForMerchantAction($riskAction, $riskAttributes);

            if (isset($input['entity_id']) === true)
            {
                $tags[] = sprintf("%s%s", Constants::BULK_WORKFLOW_GROUP_TAG_PREFIX, $input['entity_id']);
            }

            $routePermission = Permission\Name::$actionMap[$riskAction];

            $input = [
                Constants::ACTION          => $riskAction,
                'use_workflows'            => false,
                Constants::RISK_ATTRIBUTES => $riskAttributesParams,
            ];

            if ($riskAction === Action::ENABLE_INTERNATIONAL)
            {
                $input[ProductInternationalMapper::INTERNATIONAL_PRODUCTS] = $riskAttributes[ProductInternationalMapper::INTERNATIONAL_PRODUCTS];
                unset($input[Constants::RISK_ATTRIBUTES]);
            }

            if (isset($bulkActionId))
            {
                $input[Constants::BULK_WORKFLOW_ACTION_ID] = $bulkActionId;
            }

            $diffData = $this->getDiffData($merchantDetails, $riskAction, $riskAttributes, $settlementClearance);

            // NOTE: given the use case can generate the diff payload directly,
            // but for consistency reasons calling createDiff
            // No need for redacting fields as no sensitive field is being used

            $diff = (new Differ\Core)->createDiff([], $diffData);

            $workflowAction = $this->app['workflow'];

            if (isset($maker) === true)
            {
                $workflowAction = $workflowAction
                    ->setMakerFromAuth(false)
                    ->setWorkflowMaker($maker);
            }

            if (isset($routeName) == false)
            {
                $routeName = Constants::RISK_ACTION_ROUTE_NAME;
            }

            $workflowAction = $workflowAction
                ->setPermission($routePermission)
                ->setTags($tags)
                ->setWorkflowMakerType(MakerType::ADMIN)
                ->setRouteName($routeName)
                ->setController(Constants::RISK_ACTION_ROUTE_CONTROLLER)
                ->setRouteParams(['id' => $merchantId])
                ->setEntityAndId($merchant->getEntity(), $merchantId)
                ->setInput($input)
                ->setDiff($diff)
                ->trigger();

            $this->trace->info(TraceCode::CREATE_RISK_ACTION,
               [
                   'merchant_id'    => $merchantId,
                   'wf_action_id'   => $workflowAction['id'],
               ]);

            $this->trackEvents($workflowAction, $merchant, $diffData, $settlementClearance);

            return $workflowAction;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::BULK_RISK_ACTION_CREATE_MERCHANT_WORKFLOW_FAILED,
                [
                    'merchantId' => $merchantId
                ]);

            throw $e;
        }
    }

    protected function addTagsForOpenWorkflowIfApplicable($workflowTags, $workflowActions, $maker)
    {
        $action = $workflowActions->first();

        $action->tag($workflowTags);

        $actionId = $action->getId();

        (new Comment\Service())->createForWorkflowAction([
            'comment'   => sprintf('NEW_TRIGGER: %s', json_encode($workflowTags)),
        ], WorkflowAction\Entity::getSignedId($actionId), $maker);


        $this->repo->workflow_action->saveOrFail($action);
    }

    protected function trackEvents($workflowAction, $merchant, $diffData, bool $settlementClearance = false)
    {
        if ($settlementClearance === true)
        {
            $this->trackSettlementClearanceSegmentEvent($workflowAction, $diffData, $merchant);
        }
    }

    protected function trackSettlementClearanceSegmentEvent($workflowAction, $diffData, $merchant)
    {
        $merchantId = $merchant->getId();

        $diffData['wf_action_id'] = $workflowAction['id'];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent($merchant, $diffData, SegmentEvent::ACQ_SETTLEMENT_CLEARANCE_WORKFLOW_CREATED);

        $this->trace->info(TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_SUCCESS, [
            'MerchantId' => $merchantId,
            'WorkflowId' => $workflowAction['id']
        ]);
    }


    protected function getDiffData($merchantDetails, $riskAction, $riskAttributes, bool $settlementClearance = false)
    {
        $merchantId = $merchantDetails->getId();

        if ($settlementClearance === true)
        {
            return $this->getDiffDataForSettlementClearanceWorkflow($merchantDetails);
        }

        $diffData = [
            'id'                       => $merchantId,
            Constants::ACTION          => $riskAction,
            Constants::RISK_ATTRIBUTES => $riskAttributes,
        ];

        return $diffData;
    }


    protected function getTags($riskAttributes, $workflowTags, bool $settlementClearance = false)
    {
        if ($settlementClearance === true)
        {
            return $this->getTagsForSettlementClearance();
        }

        return $this->getTagsFromRiskAttributes($riskAttributes, $workflowTags);
    }


    protected function getTagsForSettlementClearance()
    {
        $tag[] = Merchant\AutoKyc\Escalations\Constants::ONBOARDING_REJECTED_SETTLEMENT_CLEARANCE;

        return $tag;
    }

    protected function getTagsFromRiskAttributes($riskAttributes, $workflowTags): array
    {
        $tag = [];

        if (isset($riskAttributes[Constants::RISK_TAG]) === true)
        {
            $tag[] = Constants::RISK_TAG_PREFIX . $riskAttributes[Constants::RISK_TAG];
        }

        if (isset($riskAttributes[Constants::RISK_SOURCE]) === true)
        {
            $tag[] = Constants::RISK_SOURCE_PREFIX . $riskAttributes[Constants::RISK_SOURCE];
        }

        if(isset($riskAttributes[Constants::RISK_REASON]) === true)
        {
            $tag[] = Constants::RISK_REASON_PREFIX . $riskAttributes[Constants::RISK_REASON];
        }

        if(isset($riskAttributes[Constants::RISK_SUB_REASON]) === true)
        {
            $tag[] = Constants::RISK_SUB_REASON_PREFIX . $riskAttributes[Constants::RISK_SUB_REASON];
        }

        foreach ($workflowTags as $tagName => $tagValue) {
            $tag[] = $tagName . ':' . $tagValue;
        }

        return $tag;
    }
    protected function getDiffDataForSettlementClearanceWorkflow($merchantDetails)
    {
        $balanceAmount = 0;

        $dateOfRejection = null;

        $poaStatusAadhaarEkyc = DetailConstant::NOT_VERIFIED;

        $rejectionsReason = [];

        $merchantId = $merchantDetails->getId();

        /* At this state merchant will always have positive primary balance as negative primary balance merchants are
         already filtered still keeping a null check if in case this gets called from other places */

        // Fetching merchant balance
        $balance = $this->repo->balance->getMerchantBalanceByType($merchantId, Balance\Type::PRIMARY) ?? null;

        if (empty($balance) === false)
        {
            $balance = $balance->toArrayPublic();

            $balanceAmount = $balance[Balance\Entity::BALANCE] ?? 0;

            // Balance amount is in paisa and hence will convert it to INR later in use
        }

        // Fetch rejection category and reason for rejection
        $actionStateReasons = $this->repo->state_reason->getRejectionReasonAndRejectionCode($merchantId);

        if (empty($actionStateReasons) === false)
        {
            foreach ($actionStateReasons as $reason)
            {
                $rejectionsReason[$reason['reason_category'] ?? ' '] = $reason['reason_code'] ?? ' ';
            }
        }

        // Fetch date of rejection
        $merchantState = $this->repo->state->fetchByEntityIdAndState($merchantId, DetailEntity::REJECTED);

        if (empty($merchantState) === false)
        {
            $dateOfRejection = $merchantState[0][Merchant\Detail\Entity::CREATED_AT] ?? null;
        }

        // poa status from stakeholder entity

        $isStakeholderPresent = (new StakeholderCore())->checkIfStakeholderExists($merchantDetails);

        if ($isStakeholderPresent === true)
        {
            $isAadhaarEsignStatus = ($merchantDetails->stakeholder->getAadhaarEsignStatus() === DetailConstant::VERIFIED);

            $isAadhaarVerificationStatusWithPan = ($merchantDetails->stakeholder->getAadhaarVerificationWithPanStatus() === DetailConstant::VERIFIED);

            if (($isAadhaarEsignStatus === true) and ($isAadhaarVerificationStatusWithPan === true))
            {
                $poaStatusAadhaarEkyc = DetailConstant::VERIFIED;
            }
        }

        $diffData = [
            'merchant_id'                      => $merchantId,
            'merchant_name'                    => $merchantDetails->getContactName(),
            'poi_status'                       => $merchantDetails->getPoiVerificationStatus(),
            'poa_status'                       => $merchantDetails->getPoaVerificationStatus(),
            'poa_status_aadhaar_ekyc'          => $poaStatusAadhaarEkyc,
            'bank_account_verification_status' => $merchantDetails->getBankDetailsVerificationStatus(),
            'date_of_rejection'                => $dateOfRejection,
            'reason_category : reason_code'    => $rejectionsReason,
            'total_unsettled_amount'           => $balanceAmount,
            'action'                           => Action::RELEASE_FUNDS,
            'risk_attributes'                  => array(Constants::CLEAR_RISK_TAGS => '0'),
        ];

        return $diffData;
    }
}
