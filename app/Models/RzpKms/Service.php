<?php

namespace RZP\Models\RzpKms;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action;
use RZP\Models\RzpKms\Validator;
use RZP\Services\RzpKms\KeyManagementService;


class Service extends Base\Service
{
    protected $ba;

    public function __construct($app = null)
    {
        $this->app = $app ?? App::getFacadeRoot();

        parent::__construct();

        $this->ba = $this->app['basicauth'];

        $this->core = new Core;
    }

    public function createL1Workflow($input)
    {
        $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_L1_WORKFLOW_CREATE_REQUEST, [
            Constants::INPUT  => $input,
            Constants::STEP => Constants::CreateL1Workflow
        ]);

        (new Validator)->validateInput('createl1workflow', $input);

        $orgId = $input[Constants::ORG_ID];

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $org = $this->repo->org->find($orgId);

        if (isset($org) === true)
        {

            $prev = [
              "keyRotation" => "use old key for org_id:" .$orgId,

            ];
            $now = [
                "keyRotation" => "use new key for org_id: ".$orgId,
            ];

            try
            {
                $this->app['workflow']
                    ->setPermission(Permission\Name::RZP_KMS_KEY_ROTATION_L1_APPROVAL)
                    ->setInput($input)
                    ->setController(Constants::L1_WORKFLOW_EXECUTE_CONTROLLER)
                    ->setEntityAndId(Constants::Org_KEY, $org->getId())
                    ->handle($prev,$now);
            }
            catch (Exception\EarlyWorkflowResponse $ex)
            {
                $workflowActionData = json_decode($ex->getMessage(), true);

                $workflowActionId = $workflowActionData['id'];

                $workflowActionId = Action\Entity::verifyIdAndStripSign($workflowActionId);

                $this->postProcessOnSuccessL1WorkflowCreation($workflowActionId,$orgId);

                throw $ex;
            }
        }
        else
        {
            throw new Exception\ServerErrorException(
                'KMS L1 workflow creation',

                ErrorCode::SERVER_ERROR_KMS_WORKFLOW_FAILURE,
                [Constants::ORG_ID => $orgId]);
        }

    }

    private function postProcessOnSuccessL1WorkflowCreation($workflowActionId, $orgID)
    {
        $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_L1_WORKFLOW_CREATE_REQUEST, [
            Constants::WORKFLOW_ACTION_ID  => $workflowActionId,
            Constants::STEP => 'postProcessOnSuccessL1WorkflowCreation'
        ]);

        $payload = [
            Constants::ORG_ID => $orgID,
            Constants::ORG_WORKFLOW_ID => $workflowActionId,
        ];

        $resp = (new KeyManagementService)->sendRequest(Constants::CREATE_L1_ENTITY_KMS_PATH,'POST',$payload);

        $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_WORKFLOW_KMS_RESPONSE, [
            'response' => $resp,
            Constants::STEP => Constants::L1EntityCreationKmsRequest
        ]);

    }

    public function executeL1Workflow($input)
    {
        $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_L1_WORKFLOW_CREATE_REQUEST, [
            Constants::INPUT  => $input,
            Constants::STEP => Constants::ExecuteL1Workflow
        ]);

        $orgId = $input[Constants::ORG_ID];

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $org = $this->repo->org->find($orgId);

        $action = $this->getWorkflowAction($orgId,Permission\Name::RZP_KMS_KEY_ROTATION_L1_APPROVAL);

        if(isset($action) === true)
        {
            $payload = [
                Constants::ORG_ID => $org->getId(),
                Constants::ORG_WORKFLOW_ID => $action->getId(),
            ];

            $resp = (new KeyManagementService)->sendRequest(Constants::APPROVE_L1_ENTITY_KMS_PATH,'POST',$payload);

            $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_WORKFLOW_KMS_RESPONSE, [
                'response' => $resp,
                'step' => Constants::UpdateStatusL1KmsEntity
            ]);


            $this->createL2WorkflowTerminal($input);

        }
        else
        {
            throw new Exception\ServerErrorException(
                'KMS L1 workflow execution failed, unable to get action',

                ErrorCode::SERVER_ERROR_KMS_WORKFLOW_FAILURE,
                $input[Constants::ORG_ID]);
        }
    }

    public function createL2WorkflowTerminal($input)
    {
        $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_L2_WORKFLOW_CREATE_REQUEST, [
            Constants::INPUT  => $input,
            Constants::STEP => Constants::CreateL2Workflow,
            Constants::SERVICE_NAME => Constants::TERMINAL
        ]);

        (new Validator)->validateInput('createl2workflow', $input);

        $orgId = $input[Constants::ORG_ID];

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $org = $this->repo->org->find($orgId);

        if (isset($org) === true)
        {
            try
            {

                $prev = [
                    "keyRotation" => "use old key for terminal service and org_id:" .$orgId,

                ];
                $now = [
                    "keyRotation" => "use new key for terminal service and org_id: ".$orgId,
                ];

                $this->app['workflow']
                    ->setPermission(Permission\Name::RZP_KMS_KEY_ROTATION_TERMINAL_L2_APPROVAL)
                    ->setRouteName(Constants::CREATE_L2_TERMINAL_WORKFLOW_ROUTE_NAME)
                    ->setInput($input)
                    ->setRouteParams($input)
                    ->setMethod('POST')
                    ->setController(Constants::L2_WORKFLOW_EXECUTE_CONTROLLER_TERMINAL)
                    ->setEntityAndId(Constants::Org_KEY, $org->getId())
                    ->handle($prev,$now,false,true);
            }
            catch (Exception\EarlyWorkflowResponse $ex)
            {
                $workflowActionData = json_decode($ex->getMessage(), true);

                $workflowActionId = $workflowActionData['id'];

                $workflowActionId = Action\Entity::verifyIdAndStripSign($workflowActionId);

                $this->postProcessOnSuccessL2WorkflowCreationTerminal($workflowActionId,$org);

                $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_L2_WORKFLOW_CREATE_REQUEST, [
                    'L2 workflow' => json_decode($ex->getMessage(),true),
                    Constants::STEP => Constants::AfterL2WorkflowCreation,
                    Constants::SERVICE_NAME => Constants::TERMINAL
                ]);
            }

        }
        else
        {
            throw new Exception\ServerErrorException(
                'KMS L2 workflow creation',

                ErrorCode::SERVER_ERROR_KMS_WORKFLOW_FAILURE,
                [Constants::ORG_ID => $orgId,
                    Constants::SERVICE_NAME => Constants::TERMINAL]);
        }

    }

    private function postProcessOnSuccessL2WorkflowCreationTerminal($workflowActionId, $org)
    {
        $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_L2_WORKFLOW_CREATE_REQUEST, [
            'workflowActionId'  => $workflowActionId,
            Constants::STEP => 'postProcessOnSuccessL2WorkflowCreation',
            Constants::SERVICE_NAME =>Constants::TERMINAL
        ]);

        $action = $this->getWorkflowAction($org->getId(),Permission\Name::RZP_KMS_KEY_ROTATION_L1_APPROVAL);

        if(isset($action) === true)
        {
            $payload = [
                Constants::ORG_ID => $org->getId(),
                Constants::SERVICE_WORKFLOW_ID => $workflowActionId,
                Constants::ORG_WORKFLOW_ID => $action->getId(),
                Constants::SERVICE_NAME =>Constants::TERMINAL,
            ];

            $resp = (new KeyManagementService)->sendRequest(Constants::CREATE_L2_ENTITY_KMS_PATH,'POST',$payload);

            $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_WORKFLOW_KMS_RESPONSE, [
                'response' => $resp,
                Constants::STEP => Constants::L2EntityCreationKmsRequest
            ]);
        }
        else
        {
            throw new Exception\ServerErrorException(
                'KMS L2 workflow creation postprocess failed, unable to get action',

                ErrorCode::SERVER_ERROR_KMS_WORKFLOW_FAILURE,
                [Constants::ORG_ID => $org->getId(),
                    Constants::SERVICE_WORKFLOW_ID => $workflowActionId,
                    Constants::SERVICE_NAME => Constants::TERMINAL]);
        }

    }

    public function executeL2WorkflowTerminal($input)
    {
        $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_L2_WORKFLOW_CREATE_REQUEST, [
            Constants::INPUT  => $input,
            Constants::STEP => Constants::ExecuteL2Workflow,
            Constants::SERVICE_NAME => Constants::TERMINAL
        ]);

        $orgId = $input[Constants::ORG_ID];

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $org = $this->repo->org->find($orgId);

        $actionL1 = $this->getWorkflowAction($orgId,Permission\Name::RZP_KMS_KEY_ROTATION_L1_APPROVAL);
        $actionL2 = $this->getWorkflowAction($orgId,Permission\Name::RZP_KMS_KEY_ROTATION_TERMINAL_L2_APPROVAL);

        if(isset($actionL1) === true and isset($actionL2) === true)
        {
            $payload = [
                Constants::ORG_ID => $org->getId(),
                Constants::SERVICE_WORKFLOW_ID => $actionL2->getId(),
                Constants::ORG_WORKFLOW_ID => $actionL1->getId(),
                Constants::SERVICE_NAME =>Constants::TERMINAL,
            ];

            $resp = (new KeyManagementService)->sendRequest(Constants::APPROVE_L2_ENTITY_KMS_PATH,'POST',$payload);

            $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_WORKFLOW_KMS_RESPONSE, [
                'response' => $resp,
                'step' => Constants::UpdateStatusL2KmsEntity
            ]);


            $l2entityId = $resp['id'];
            $keyRotationPayload = [
                Constants::ID => $l2entityId,
            ];

            $keyRotationResp = (new KeyManagementService)->sendRequest(Constants::INITIATE_KEY_ROTATION_KMS_PATH,'POST',$keyRotationPayload);

            $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_WORKFLOW_KMS_RESPONSE, [
                'response' => $keyRotationResp,
                'step' => Constants::KeyRotationInitiation
            ]);

            return $keyRotationResp;
        }
        else
        {
            throw new Exception\ServerErrorException(
                'KMS L2 workflow execution failed, unable to get action',

                ErrorCode::SERVER_ERROR_KMS_WORKFLOW_FAILURE,
                [Constants::ORG_ID => $org->getId(),
                    Constants::SERVICE_NAME => Constants::TERMINAL]);
        }

    }

    private function getWorkflowAction($orgId, $permissionName)
    {
        $workflowActions = (new Action\Core)->fetchLastUpdatedWorkflowActionInPermissionList(
            $orgId, Constants::Org_KEY, [$permissionName]);

        $this->trace->info(TraceCode::KEY_MANAGEMENT_SERVICE_L2_WORKFLOW_CREATE_REQUEST, [
            "workflowActions"  => $workflowActions,
            Constants::STEP => "getWorkflowAction",
        ]);

        return $workflowActions;
    }

}
