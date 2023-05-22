<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Comment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Admin\Admin\Entity as AdminEntity;

class Service extends Base\Service
{
    public function getRiskAttributes()
    {
        $riskTags = explode(',', Constants::RISK_TAGS_CSV);

        $riskSources = explode(',', Constants::RISK_SOURCES_CSV);

        return [
            'risk_tags'     => $riskTags,
            'risk_reasons'  => Constants::RISK_REASONS_MAP,
            'risk_sources'  => $riskSources,
        ];
    }

    private function createBulkWorkflowDetailsComment($bulkActionId, $workflowAction, $riskWorkflowMaker)
    {
        $publicId = sprintf('%s_%s', Action\Entity::getSign(), $bulkActionId);

        try
        {
            $actionEntity = (new Action\Service())->getActionDetails($publicId);

            $makerDetails = sprintf('%s(%s)', $actionEntity['maker']['name'], $actionEntity['maker']['email']);

            // using state changer as checkers array was found to be empty.
            $checkerDetails = sprintf('%s(%s)', $actionEntity['state_changer']['name'], $actionEntity['state_changer']['email']);

            $link = sprintf('https://admin-dashboard.razorpay.com/admin/requests/%s', $publicId);

            $comment = sprintf(Constants::BULK_WORKFLOW_DETAILS_TPL, $makerDetails, $checkerDetails, $link);

            if (isset($comment) === true)
            {
                (new Comment\Core())->createForWorkflowAction([
                                                                  'comment'   => $comment,
                                                              ], $workflowAction, $riskWorkflowMaker);
            }
        }
        catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::BULK_RISK_ACTION_COMMENT_DETAILS_CREATION_FAILED,
                [
                    'workflow_action_id'  => $publicId,
                ]);
        }
    }

    protected function closeWorkflowIfApplicable($workflowAction, $riskWorkflowMaker)
    {
        try {
            if (isset($workflowAction) === false)
            {
                return;
            }
            if ($workflowAction->isExecuted() === false)
            {
                (new Action\Core())->close($workflowAction, $riskWorkflowMaker, true);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::BULK_RISK_ACTION_CLOSE_WORKFLOW_FAILED,
                [
                    'workflow_action_id'  => $workflowAction->getId(),
                ]);
        }
    }

    public function createAndExecuteRiskAction($input)
    {
        $merchantId = $input[Constants::MERCHANT_ID];

        $riskWorkflowMaker = $this->getIndividualRiskWorkflowMaker();
        try
        {
            $diff = (new Differ\Core)->get($input[Constants::BULK_WORKFLOW_ACTION_ID]);

            $diff['new'][Constants::MERCHANT_ID] = $merchantId;

            $diff['new'][Constants::BULK_WORKFLOW_ACTION_ID] = $input[Constants::BULK_WORKFLOW_ACTION_ID];

            $workflowActionId = (new Core)->createRiskWorkflowAction($diff['new'], $riskWorkflowMaker)['id'];

            $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
                $merchantId, 'merchant', Permission\Name::$actionMap[$diff['new'][Constants::ACTION]]);

            // note: sleep required because it can take upto 1 second for documents to become available for search in ES.
            // this is acceptable since we are doing this in batch service.
            sleep(1);

            $input['workflow_action_id'] = $workflowActionId;

            foreach ($workflowActions as $workflowAction)
            {
                (new Action\Core)->approveActionForcefully($workflowAction, $riskWorkflowMaker);

                (new Action\Core)->executeAction($workflowAction, $riskWorkflowMaker, $riskWorkflowMaker->getSuperAdminRole());

                $this->createBulkWorkflowDetailsComment($input[Constants::BULK_WORKFLOW_ACTION_ID], $workflowAction, $riskWorkflowMaker);
            }

            $status = Constants::EXECUTED;
        }
        catch (Exception\BadRequestValidationFailureException | Exception\BadRequestException $e)
        {
            $status = Constants::INVALIDATED;

            $input['failure_message'] = $e->getMessage();

            if (isset($workflowAction) === true)
            {
                $this->closeWorkflowIfApplicable($workflowAction, $riskWorkflowMaker);
            }

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::BULK_RISK_ACTION_CREATE_AND_EXECUTE_WORKFLOW_FAILED,
                [
                    'merchantId'            => $merchantId,
                    'execution_status'       => $status,
                ]);
        }
        catch (\Throwable $e)
        {
            $status = Constants::FAILED;

            $input['failure_message'] = $e->getMessage();

            if (isset($workflowAction) === true)
            {
                $this->closeWorkflowIfApplicable($workflowAction, $riskWorkflowMaker);
            }

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::BULK_RISK_ACTION_CREATE_AND_EXECUTE_WORKFLOW_FAILED,
                [
                    'merchantId'            => $merchantId,
                    'execution_status'       => $status,
                ]);
        }

        $input['workflow_action_status'] = $status;

        return $input;
    }

    public function createRiskWorkflowAction($input)
    {
        (new Validator())->validateInput('create_risk_action', $input);

        (new Core())->validateRiskAttributes($input);

        return (new Core())->createRiskWorkflowAction($input);
    }

    public function createRiskWorkflowActionRas($input)
    {
        (new Validator())->validateInput('create_risk_action_ras', $input);

        (new Core())->validateRiskAttributes($input);

        $maker = $this->getMaker();

        return (new Core())->createRiskWorkflowAction($input, $maker);
    }

    public function createRiskWorkflowActionInternal($input)
    {
        (new Validator())->validateInput('create_risk_action_internal', $input);

        (new Core())->validateRiskAttributes($input);

        $maker = $this->repo->admin->findOrFailPublic(AdminEntity::stripDefaultSign($input[Constants::MAKER_ADMIN_ID]));

        $routeName = app('request.ctx')->getRoute();

        return (new Core())->createRiskWorkflowAction($input, $maker, $routeName);
    }

    public function getIndividualRiskWorkflowMaker()
    {
        $makerOrgId = Org\Entity::RAZORPAY_ORG_ID;

        // NOTE: maker_email (both maker and checker) should be superadmin
        $makerEmail = env(Constants::BULK_RISK_ACTION_INDIVIDUAL_WORKFLOW_MAKER_EMAIL);

        $maker = $this->repo->admin->findByOrgIdAndEmail($makerOrgId, $makerEmail);

        return $maker;
    }

    private function getMaker()
    {
        //using default razorpay org for now, to be fixed by code owner
        $makerOrgId = Org\Entity::RAZORPAY_ORG_ID;

        // NOTE: maker_email (both maker and checker) should be superadmin
        $makerEmail = $this->app['config']->get('applications.merchant_risk_alerts.maker_email');

        if (empty($makerEmail) === true)
        {
            throw new Exception\LogicException('Merchant Risk Alert Workflow Maker is not initialized');
        }

        $maker = $this->repo->admin->findByOrgIdAndEmail($makerOrgId, $makerEmail);

        return $maker;
    }
}
