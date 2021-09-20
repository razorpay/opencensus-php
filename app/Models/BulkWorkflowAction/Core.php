<?php


namespace RZP\Models\BulkWorkflowAction;

use RZP\Exception;
use Monolog\Logger;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Comment;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\Permission;
use RZP\Models\RiskWorkflowAction;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Workflow\Action as Action;

class Core extends Base\Core
{
    public function handleBulkAction(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_BULK_RISK_ACTION_UPDATE_REQUEST, ['data' => $input]);

        $this->validateBulkActionInput($input);

        $this->createBulkActionWorkflow($input);
    }

    private function createBulkActionWorkflow(array $input)
    {
        $action = $input['action'];

        $entityId = UniqueIdEntity::generateUniqueId();

        $tags[] = sprintf("%s%s", Constants::BULK_WORKFLOW_GROUP_TAG_PREFIX, $entityId);

        $this->trace->info(TraceCode::CREATE_BULK_EDIT_WORKFLOW,
            [
                'data'          => $input,
                'action'        => $action,
                'tags'          => $tags,
                'entity_id'     => $entityId,
            ]);

        $input['entity_id'] = $entityId;

        $this->app['workflow']
            ->setPermission(Constants::BULK_WORKFLOW_ACTION_PERMISSION_NAME[$action])
            ->setRouteName(Constants::BULK_WORKFLOW_ACTION_ROUTE_NAME)
            ->setController(Constants::BULK_WORKFLOW_ACTION_ROUTE_CONTROLLER)
            ->setMakerFromAuth(true)
            ->setEntityAndId(E::BULK_WORKFLOW_ACTION, $entityId)
            ->setTags($tags)
            ->setInput($input)
            ->handle(null, $input);
    }

    /**
     * handle the bulk action call
     * @param array $input
     */
    public function validateBulkActionInput(array $input)
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
                Constants::CREATE_CONSTRUCTIVE_BULK_RISK_ATTRIBUTES_VALIDATOR,
                $riskAttributes);
        }
        else
        {
            (new Validator())->validateInput(
                Constants::CREATE_DESTRUCTIVE_BULK_RISK_ATTRIBUTES_VALIDATOR,
                $riskAttributes);
        }
    }

    public function executeBulkAction(array $input)
    {
        $this->trace->info(TraceCode::EXECUTE_BULK_RISK_ACTION,
           [
               'input'=> $input,
           ]);

        $entityId = $input['entity_id'];

        $action = (new Action\Core)
                ->fetchOpenActionOnEntityOperation(
                $entityId, E::BULK_WORKFLOW_ACTION, Constants::BULK_WORKFLOW_ACTION_PERMISSION_NAME[$input['action']])
                ->first();

        $this->editBulkAction($action, $input);

        return ['success' => true];
    }

    private function editBulkAction(Action\Entity $action, array $input)
    {
        $merchantIds = $input['merchant_ids'];

        $actionId = $action->getId();

        $actionStatuses = [];

        $individualRiskWorkflowMaker = $this->getIndividualRiskWorkflowMaker();

        foreach ($merchantIds as $merchantId)
        {
            // create individual workflow action that when executed, performs action and sends notification
            $status = Constants::APPROVED;

            $workflowActionId = null;

            try
            {
                $workflowActionId = (new RiskWorkflowAction\Core())->createRiskWorkflowAction(
                    $merchantId, $individualRiskWorkflowMaker, $input);
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

            $actionStatuses[$merchantId] = [
                'status'                => $status,
                'workflow_action_id'    => $workflowActionId,
            ];
        }

        $bulkActionStatusComment = sprintf(Constants::MERCHANTS_STATUS_COMMENT_TPL, json_encode($actionStatuses));

        (new Comment\Service())->createForWorkflowAction([
             'comment'   => $bulkActionStatusComment,
         ], Action\Entity::getSignedId($actionId));

        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityListOperation(
            $merchantIds, 'merchant', Permission\Name::$actionMap[$input['action']]);

        // todo: the sleep part to be removed in V2, where we perform the workflow actions execution in async
        // we could add a short random delay while dispatching in queue to avoid the sleep part.
        sleep(2);

        foreach ($workflowActions as $workflowAction)
        {
            (new Action\Core)->approveActionForcefully($workflowAction, $individualRiskWorkflowMaker);

            (new Action\Core)->executeAction(
                $workflowAction,
                $individualRiskWorkflowMaker,
                $individualRiskWorkflowMaker->getSuperAdminRole());
        }

        return [
            'success' => true
        ];
    }

    private function getIndividualRiskWorkflowMaker()
    {
        // NOTE: maker_email (both maker and checker) should be superadmin
        $makerEmail = env(Constants::BULK_RISK_ACTION_INDIVIDUAL_WORKFLOW_MAKER_EMAIL);

        $maker = $this->repo->admin->findByEmail($makerEmail);

        return $maker;
    }
}
