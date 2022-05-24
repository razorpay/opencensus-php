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

class Service extends Base\Service
{
    public function create(string $actionId, array $input)
    {
        $actionIdInput = $actionId;

        $actionId = Action\Entity::verifyIdAndStripSign($actionId);

        $workflowAction = $this->repo->workflow_action->findOrFailPublic($actionId);

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

        $input[Entity::ACTION_ID] = $actionId;

        $checker = $this->core()->create($input);

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
}
