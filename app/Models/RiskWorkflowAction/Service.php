<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Comment;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action\Differ;

class Service extends Base\Service
{
    public function getRiskAttributes()
    {
        $riskTags = explode(',', Constants::RISK_TAGS_CSV);

        $riskReasons = explode(',', Constants::RISK_REASONS_CSV);

        $riskSources = explode(',', Constants::RISK_SOURCES_CSV);

        return [
            'risk_tags'     => $riskTags,
            'risk_reasons'  => $riskReasons,
            'risk_sources'  => $riskSources,
        ];
    }

    private function getBulkWorkflowDetailsComment($actionId)
    {
        $publicId = sprintf('%s_%s', Action\Entity::getSign(), $actionId);

        try
        {
            $actionEntity = (new Action\Service())->getActionDetails($publicId);

            $makerDetails = sprintf('%s(%s)', $actionEntity['maker']['name'], $actionEntity['maker']['email']);

            // using state changer as checkers array was found to be empty.
            $checkerDetails = sprintf('%s(%s)', $actionEntity['state_changer']['name'], $actionEntity['state_changer']['email']);

            $link = sprintf('https://admin-dashboard.razorpay.com/admin/requests/%s', $publicId);

            return sprintf(Constants::BULK_WORKFLOW_DETAILS_TPL, $makerDetails, $checkerDetails, $link);
        }
        catch (Exception\BadRequestException $e) {
            return null;
        }
    }

    public function createAndExecuteRiskAction($input)
    {
        try
        {
            $diff = (new Differ\Core)->get($input[Constants::BULK_WORKFLOW_ACTION_ID]);

            $riskWorkflowMaker = $this->getIndividualRiskWorkflowMaker();

            $workflowActionId = (new Core)->createRiskWorkflowAction($input[Constants::MERCHANT_ID], $riskWorkflowMaker, $diff['new']);

            $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
                $input[Constants::MERCHANT_ID], 'merchant', Permission\Name::$actionMap[$diff['new'][Constants::ACTION]]);

            // note: sleep required because it can take upto 1 second for documents to become available for search in ES.
            // this is acceptable since we are doing this in batch service.
            sleep(1);

            $input['workflow_action_id'] = $workflowActionId;

            foreach ($workflowActions as $workflowAction)
            {
                (new Action\Core)->approveActionForcefully($workflowAction, $riskWorkflowMaker);

                (new Action\Core)->executeAction($workflowAction, $riskWorkflowMaker, $riskWorkflowMaker->getSuperAdminRole());

                $comment = $this->getBulkWorkflowDetailsComment($input[Constants::BULK_WORKFLOW_ACTION_ID]);

                if (isset($comment) === true)
                {
                    (new Comment\Core())->createForWorkflowAction([
                        'comment'   => $comment,
                    ], $workflowAction, $riskWorkflowMaker);
                }
            }

            $status = Constants::EXECUTED;
        }
        catch (Exception\BadRequestException $e)
        {
            $status = Constants::INVALIDATED;
        }
        catch (Exception\BadRequestValidationFailureException $e)
        {
            $status = Constants::INVALIDATED;
        }
        catch (\Throwable $e)
        {
            $status = Constants::FAILED;
        }

        $input['workflow_action_status'] = $status;

        return $input;
    }

    public function getIndividualRiskWorkflowMaker()
    {
        // NOTE: maker_email (both maker and checker) should be superadmin
        $makerEmail = env(Constants::BULK_RISK_ACTION_INDIVIDUAL_WORKFLOW_MAKER_EMAIL);

        // todo: we can cache the result
        $maker = $this->repo->admin->findByEmail($makerEmail);

        return $maker;
    }
}
