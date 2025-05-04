<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Entity;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Workflow\Action\Differ\Service as ActionDifferService;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasonDetail;
use RZP\Models\RiskWorkflowAction\Constants as RiskWorkflowActionConstants;
use RZP\Models\Workflow\Action\Service as ActionService;
use RZP\Models\Admin\Permission\Name as AdminPermission;

class Service extends Base\Service
{
    public function create(string $actionId, array $input)
    {
        $actionIdInput = $actionId;

        $actionId = Action\Entity::verifyIdAndStripSign($actionId);

        $workflowAction = $this->repo->workflow_action->findOrFailPublic($actionId);

        $this->app['config']['workflow_guard.current_workflow_id'] = $workflowAction->getWorkflowId();

        $permissionName = $workflowAction->permission->getName();

        if (($permissionName === Constants::EDIT_ACTIVATE_MERCHANT) and (isset($input[Constants::APPROVED_WITH_FEEDBACK]) === true) and ($input[Constants::APPROVED_WITH_FEEDBACK] == 1))
        {
            $workflowAction->tag(Constants::APPROVED_WITH_FEEDBACK);
        }

        if (isset($input[Constants::APPROVED_WITH_FEEDBACK]) === true)
        {
            unset($input[Constants::APPROVED_WITH_FEEDBACK]);
        }

        if (($permissionName === Constants::EDIT_ACTIVATE_MERCHANT) && (isset($input['approved']) === true) && ($input['approved'] == 1))
        {
            //checking for comment validations only if Activation Form Status is Rejected and action is approve
           $this->validateCommentsOnRejection($actionIdInput);
        }

        // Add risk attributes to tags with appropriate prefixes
        $tags = [];

        $riskAttributes = $input[RiskWorkflowActionConstants::RISK_ATTRIBUTES] ?? [];

        if (isset($riskAttributes[RiskWorkflowActionConstants::RISK_REASON]) === true)
        {
            $tags[] = RiskWorkflowActionConstants::RISK_REASON_PREFIX . $riskAttributes[RiskWorkflowActionConstants::RISK_REASON];
        }

        if (isset($riskAttributes[RiskWorkflowActionConstants::RISK_SUB_REASON]) === true)
        {
            $tags[] = RiskWorkflowActionConstants::RISK_SUB_REASON_PREFIX . $riskAttributes[RiskWorkflowActionConstants::RISK_SUB_REASON];
        }

        // Handle workflowTags if provided
        $workflowTags = $input['workflow_tags'] ?? [];

        foreach ($workflowTags as $tagName => $tagValue)
        {
            $tags[] = $tagName . ':' . $tagValue;
        }

        // Add the tags to the workflow action
        if (!empty($tags)) {
            $this->trace->info(TraceCode::WORKFLOW_TAGS_TRACE_INFO,
                [
                    'tags'   => $tags
                ]);
            $workflowAction->tag($tags);
        }

        $input[Entity::ACTION_ID] = $actionId;
        if (isset($input[RiskWorkflowActionConstants::RISK_ATTRIBUTES]))
        {
            unset($input[RiskWorkflowActionConstants::RISK_ATTRIBUTES]);
        }
        $checker = $this->core()->create($input);

        $workflowDetails = (new ActionService())->getActionDetails($actionIdInput);
        $state = $workflowDetails['state'];
        if(strtolower($state) == strtolower(RiskWorkflowActionConstants::EXECUTED) && in_array($permissionName, RiskWorkflowActionConstants::FOH_SETTELMENT_PERMISSIONS)){
            $this->trace->info(TraceCode::WORKFLOW_STATE_AND_PERMISSION_MATCHED,
                [
                    'state'    => $state,
                    'permission' => $permissionName,
                ]);
            $this->callSettlementServiceForFOHToggle($workflowDetails);
        }

        if (empty($checker))
        {
            return [];
        }

        return $checker->toArrayPublic();
    }

    public function validateCommentsOnRejection(string $actionId)
    {
        $this->trace->info(
            TraceCode::WORKFLOW_COMMENT_TRACE_INFO, [
                "actionId"     => $actionId,
            ]
        );

        if($this->checkIfRejectionReasonContainsOtherOrSuspiciousReasonCode($actionId) === true)
        {
            Action\Entity::verifyIdAndStripSign($actionId);

            $comments = $this->repo
                ->comment
                ->fetchByActionIdWithRelations(
                    $actionId, [Entity::ADMIN]);

            if($this->validateComments($comments->toArray()) === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_COMMENT);
            }
        }
    }

    public function checkIfRejectionReasonContainsOtherOrSuspiciousReasonCode(string $actionId)
    {
        $diffResponse = (new ActionDifferService())->get($actionId);

        $this->trace->info(
            TraceCode::WORKFLOW_COMMENT_TRACE_INFO, [
                "actionId"     => $actionId,
                "diffResponse" => $diffResponse
            ]
        );

        $Description_Reason_Code_Mapping = RejectionReasonDetail::getDescriptionReasonCodeMapping();

        $rejection_reasons = $diffResponse['new']['rejection_reasons'] ?? '';

        //For 'others' and 'suspicious_online_presence' reason codes,
        // Adding Comments with minimum 50 characters is mandatory

        $reasonCodes = [
            RejectionReasonDetail::OTHERS,
            RejectionReasonDetail::SUSPICIOUS_ONLINE_PRESENCE,
            RejectionReasonDetail::RISK_RELATED_REJECTIONS_OTHERS,
            RejectionReasonDetail::PROHIBITED_BUSINESS_OTHERS,
            RejectionReasonDetail::UNREG_BLACKLIST_OTHERS,
            RejectionReasonDetail::OPERATIONAL_OTHERS,
            RejectionReasonDetail::HIGH_RISK_BUSINESS_OTHERS,
            RejectionReasonDetail::UNREG_HIGH_RISK_OTHERS
        ];

        $reasonCodesInput = [];

        if (empty($rejection_reasons) === false)
        {
            foreach($rejection_reasons as $rejection_reason)
            {
                array_push($reasonCodesInput, $Description_Reason_Code_Mapping[$rejection_reason]);
            }
        }

        $this->trace->info(
            TraceCode::WORKFLOW_COMMENT_TRACE_INFO, [
                "reasonCodesInput"     => $reasonCodesInput,
            ]
        );

        if(empty($reasonCodesInput) === false)
        {
            foreach ($reasonCodes as $reasonCode)
            {
                if(in_array($reasonCode, $reasonCodesInput) === true)
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function validateComments(array $comments)
    {
        //checking if any comment is of length 50 or more
        foreach ($comments as $comment)
        {
            if(strlen($comment['comment']) >= 50)
            {
                return true;
            }
        }
        return false;
    }

    private function callSettlementServiceForFOHToggle($workflowDetails)
    {
        $reasonData = $this->getReasonAndSubReason($workflowDetails);
        $this->trace->info(TraceCode::WORKFLOW_TAGS_REASON_SUB_REASON,
            [
                'reason'    => $reasonData,
            ]);
        $reasonCodeIdentifier = $this->fetchReasonCode($reasonData) ?? "";
        $this->trace->info(TraceCode::WORKFLOW_REASON_CODE_IDENTIFIER,
            [
                'codeIdentifier'    => $reasonCodeIdentifier
            ]);
        $this->sendReasonCodeIdentifier($workflowDetails, $reasonCodeIdentifier);
    }

    private function getReasonAndSubReason($workflowDetails): array
    {
        $reason = null;
        $subReason = null;
        if (isset($workflowDetails['tagged']) && is_array($workflowDetails['tagged'])) {
            foreach ($workflowDetails['tagged'] as $tag) {
                if (str_starts_with($tag, 'risk-reason-')) {
                    $reason = str_replace('-', ' ', str_replace('risk-reason-', '', $tag));
                } elseif (str_starts_with($tag, 'risk-sub-reason-')) {
                    $subReason = str_replace('-', ' ', str_replace('risk-sub-reason-', '', $tag));
                }
            }
        }
        return [
            'reason' => $reason,
            'subReason' => $subReason,
        ];
    }

    private function fetchReasonCode($reasonData)
    {
        $reasonCodeIdentifier = null;

        try {
            if (isset($reasonData['reason']) && isset($reasonData['subReason'])) {
                $input = [
                    'source' => 'FOH',
                    'category' => $reasonData['reason'],
                    'sub_reason' => $reasonData['subReason'],
                ];

                // API call to settlement reason code mapping to get data
                $apiResponse = (new \RZP\Models\Settlement\Service)->getHoldReasonCodeMappingsInternal($input);
                if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
                    foreach ($apiResponse['data'] as $entry) {
                        $reason = $entry['reason_categorization'] ?? null;
                        $subReason = $entry['sub_reason'] ?? null;
                        if ($reason && $subReason && strtolower($reason) === strtolower($reasonData['reason']) && strtolower($subReason) === strtolower($reasonData['subReason'])) {
                            $reasonCodeIdentifier = $entry['reason_identifier'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->trace->error(
                TraceCode::SETTLEMENT_REASON_CODE_API_ERROR,
                [
                    'message' => $e->getMessage(),
                ]
            );
        }

        return $reasonCodeIdentifier;
    }


    private function sendReasonCodeIdentifier($workflowDetails, string $reasonCodeIdentifier)
    {
        $pf_name = $workflowDetails['permission']['name'];
        $emailId = $this->app['basicauth']->getAdmin()->getEmail() ?? '';
        $diffData = (new ActionDifferService())->get($workflowDetails['id']);
        $merchantIDs = $this->getMIDs($workflowDetails, $pf_name, $diffData);
        $foh_toggle = $this->getFOHAction($pf_name, $diffData);
        if($foh_toggle == RiskWorkflowActionConstants::FOH_RELEASE){
            $remark = "Releasing funds on hold for merchant. For further information, please track the workflow: " . $workflowDetails['id'];
            $this-> updateFOHSettlementCall(false, $merchantIDs, $emailId, "", $remark);
        }
        if($foh_toggle == RiskWorkflowActionConstants::FOH_HOLD){
            $remark = "Putting funds on hold for merchant. For further information, please track the workflow: " . $workflowDetails['id'];
            $this->updateFOHSettlementCall(true, $merchantIDs, $emailId, $reasonCodeIdentifier, $remark);
        }
    }

    private function getMIDs($workflowDetails, $pf_name, $diffData)
    {
        if ($pf_name == AdminPermission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK) {
            $merchantIDs = $diffData['new']['merchant_ids'] ?? [];
        } else {
            $merchantIDs = isset($workflowDetails['entity_id'])
                ? (is_array($workflowDetails['entity_id'])
                    ? $workflowDetails['entity_id']
                    : [$workflowDetails['entity_id']])
                : [];
        }

        return $merchantIDs;
    }
    private function getFOHAction($pf_name, $diffData) : string
    {
        if($pf_name == AdminPermission::EDIT_MERCHANT_RELEASE_FUNDS){
            return "RELEASE";
        }
        if($pf_name == AdminPermission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK && $diffData['new']['action'] == "release_funds"){
            return "RELEASE";
        }
        return "HOLD";
    }

    private function updateFOHSettlementCall(bool $enable, $merchantIds, $emailId, $reasonCodeIdentifier, $remark)
    {
        $batches = array_chunk($merchantIds, 5);
        foreach ($batches as $batch) {
            $data = [];
            $updatedAt = time();

            foreach ($batch as $merchantId) {
                $data[] = [
                    'merchant_id'    => $merchantId,
                    'enable'         => $enable,
                    'reason_code_id' => $reasonCodeIdentifier,
                    'updated_at'     => $updatedAt,
                    'updated_by'     => $emailId,
                    'approved_by'    => $emailId,
                    'remarks'        => $remark,
                ];
            }

            // Prepare the input payload
            $input = ['data' => $data];

            try {
                // Call the settlement API
                $response = (new \RZP\Models\Settlement\Service)->updateFOH($input);
                $this->trace->info(TraceCode::SETTLEMENT_UPDATE_API_RESPONSE,
                    [
                        'request'     => $input,
                        'response'    => $response,
                    ]);
            } catch (\Exception $e) {
                $this->trace->error(
                    TraceCode::SETTLEMENT_REASON_CODE_API_ERROR,
                    [
                        'message' => $e->getMessage(),
                    ]
                );
            }
        }
    }
}
