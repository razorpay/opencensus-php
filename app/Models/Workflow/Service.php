<?php

namespace RZP\Models\Workflow;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;

use RZP\Trace\TraceCode;
use RZP\Models\State\Name;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Workflow\Observer as Observer;
use RZP\Models\Workflow\Action\Differ\Core as DifferCore;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Models\Workflow\Action\Differ\Service as DifferService;
use RZP\Models\Workflow\Observer\Constants as WorkflowObserverConstants;


class Service extends Base\Service
{
    public function create(array $input)
    {
        Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);

        Permission\Entity::verifyIdAndStripSignMultiple($input[Entity::PERMISSIONS]);

        $this->validateIfPayoutWorkflow($input);

        $workflow = $this->core()->create($input, $this->merchant);

        return $this->convertDataToDashboardFormat(
            $workflow->toArrayPublic());
    }

    public function fetch(string $orgId, string $id)
    {
        $workflow = $this->repo->workflow
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        $data = $workflow->toArrayPublic();

        $data['isEditable'] = $this->core()->workflowHasOpenActions($workflow);

        // Dashboard requires the API in certain format
        $response = $this->convertDataToDashboardFormat($data);

        return $response;
    }

    public function fetchMultiple(string $orgId, array $input)
    {
        Org\Entity::verifyIdAndStripSign($orgId);

        $workflows = $this->repo->workflow->findByOrgIdAndPermissionName($orgId, $input);

        return $workflows->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::PERMISSIONS]);
        }

        if (empty($input[Entity::ORG_ID]) === false)
        {
            Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);
        }

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        $workflow = $this->core()->update($workflow, $input);

        $data = $this->convertDataToDashboardFormat($workflow->toArrayPublic());

        return $data;
    }

    public function delete(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        $workflow = $this->core()->delete($workflow);

        return $workflow->toArrayPublic();
    }

    public function permissionHasWorkflow(string $routePermission, string $orgId, string $merchantId = null)
    {
        $workflows = (new Action\Core)->getWorkflowsForPermission($routePermission, $orgId, $merchantId);

        return ($workflows->isEmpty() === false);
    }

    public function convertDataToDashboardFormat(array $data)
    {
        $steps = $data[Entity::STEPS] ?? [];

        // Get all the levels in the steps.

        $levels = array_map(function($step){
            return $step[Step\Entity::LEVEL];
        }, $steps);

        $levels = array_unique($levels, SORT_NUMERIC);

        $levelData = [];

        foreach ($levels as $level)
        {
            $levelDetails = [];

            $levelSteps = [];

            foreach ($steps as $step)
            {
                if ($step[Step\Entity::LEVEL] === $level)
                {
                    $levelDetails[Step\Entity::OP_TYPE] = $step[Step\Entity::OP_TYPE];
                    $levelDetails[Step\Entity::LEVEL] = $level;

                    unset($step[Step\Entity::OP_TYPE]);
                    unset($step[Step\Entity::LEVEL]);

                    $levelSteps[] = $step;
                }
            }

            $levelDetails['steps'] = $levelSteps;

            $levelData[] = $levelDetails;
        }

        // returns null if the key is not present
        unset($data[Entity::STEPS]);

        $data[Entity::LEVELS] = $levelData;

        return $data;
    }

    public function validateIfPayoutWorkflow($input)
    {
        $orgId = $input[Entity::ORG_ID];

        $permissions = $input[Entity::PERMISSIONS];

        // Ensure that merchant id is also passed if create_payout permission is attached, otherwise not required
        $createPayoutPerm = $this->repo
                                 ->permission
                                 ->retrieveIdsByNamesAndOrg(Permission\Name::CREATE_PAYOUT, $orgId)
                                 ->first();

        $hasCreatePayoutPermission =  (in_array($createPayoutPerm, $permissions, true) === true);

        if ($hasCreatePayoutPermission === true)
        {
            if (empty($this->merchant) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PASSED);
            }
        }
    }

    public function performActionOnObserver(string $actionId, string $state, $workflowData=[])
    {

        $this->trace->info(TraceCode::PERFORM_ACTION_OBSERVER_DATA, [
            'actionId' => $actionId,
            'state'    => $state,
            'workflowData' => $workflowData
        ]);

        try
        {
            // fetch workflow data from ES
            $workflowRequestData = (new DifferService)->fetchRequest($actionId);
        }
        catch (\Throwable $err) {
            $this->trace->info(TraceCode::ACTION_OBSERVER_TRACE, [
                'exception_caught' => $actionId
            ]);
            // Data not found in ES, assign the fallback payload
            if (empty($workflowData) === true)
            {
                $this->trace->warning(TraceCode::WORKFLOW_ACTION_NOT_FOUND, [
                    'actionId' => $actionId
                ]);
                return;
            }
            else
            {
                $workflowRequestData = [
                    DifferEntity::ROUTE_PARAMS            => $workflowData[DifferEntity::ROUTE_PARAMS],
                    DifferEntity::PAYLOAD                 => $workflowData[DifferEntity::PAYLOAD],
                    DifferEntity::CONTROLLER              => $workflowData[DifferEntity::CONTROLLER ],
                    DifferEntity::FUNCTION_NAME           => $workflowData[DifferEntity::FUNCTION_NAME ],
                    DifferEntity::AUTH_DETAILS            => $workflowData[DifferEntity::AUTH_DETAILS] ?? [],
                    DifferEntity::ROUTE                   => $workflowData[DifferEntity::ROUTE],
                    DifferEntity::WORKFLOW_OBSERVER_DATA  => $workflowData[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? [],
                    DifferEntity::ENTITY_ID               => $workflowData[DifferEntity::ENTITY_ID] ?? '',
                    DifferEntity::ENTITY_NAME             => $workflowData[DifferEntity::ENTITY_NAME] ?? '',
                    DifferEntity::PERMISSION              => $workflowData[DifferEntity::PERMISSION] ?? '',
                    DifferEntity::DIFF                    => $workflowData[DifferEntity::DIFF],
                ];
            }
        }

        $routeName = $workflowRequestData[DifferEntity::ROUTE];

        $this->trace->info(TraceCode::ACTION_OBSERVER_TRACE, [
            'route_name' => $routeName
        ]);

        $observerClass = $this->getWorkflowObserverClassName($routeName);

        if (empty($observerClass) === true)
        {
            $this->trace->info(TraceCode::ACTION_OBSERVER_TRACE, [
                'empty_observer_class' => 'empty_observer_class'
            ]);
            return;
        }

        if (key_exists($routeName,Observer\Constants::ROUTE_VS_RAZORX_EXPERIMENT) === true)
        {
            $this->trace->info(TraceCode::ACTION_OBSERVER_TRACE, [
                'experiment_check' => $actionId
            ]);
            $variant  = $this->app['razorx']->getTreatment(
                $actionId,
                Observer\Constants::ROUTE_VS_RAZORX_EXPERIMENT[$routeName],
                $this->app['rzp.mode'] ?? Mode::LIVE);

            if ($variant === 'control')
            {
                $this->trace->info(TraceCode::ACTION_OBSERVER_TRACE, [
                    'experiment_failure' => $actionId
                ]);
                return;
            }
        }

        $this->trace->info(TraceCode::ACTION_OBSERVER_TRACE, [
            'state' => $state
        ]);

        $observerData = $workflowRequestData[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? [];
        // action id at times is not present in $workflowRequestData, pass it explicitly
        $observerData[DifferEntity::ACTION_ID] = $actionId;
        $observerClassInstance = new $observerClass($workflowRequestData);

        $this->trace->info(TraceCode::PERFORM_ACTION_OBSERVER_DATA, [
            DifferEntity::WORKFLOW_OBSERVER_DATA    => $observerData,
            DifferEntity::ACTION_ID                 => $actionId,
            'observer_class'                        => $observerClass
        ]);

        if ($state === Name::REJECTED)
        {
            $observerClassInstance->onReject($observerData);
        }
        else if ($state === Name::APPROVED)
        {
            $observerClassInstance->onApprove($observerData);
        }
        else if ($state === Name::CLOSED)
        {
            $observerClassInstance->onClose($observerData);
        }
        else if ($state === Name::EXECUTED)
        {
            $observerClassInstance->onExecute($observerData);
        }
        else if ($state === Name::OPEN)
        {
            $observerClassInstance->onCreate($observerData);
        }
    }

    protected function getWorkflowObserverClassName($routeName)
    {
        if (empty(Observer\Constants::WORKFLOW_VS_OBSERVER[$routeName]) === false)
        {
            return Observer\Constants::WORKFLOW_VS_OBSERVER[$routeName];
        }

        return "";
    }

    public function getWorkflowObserverData(string $actionId)
    {
        $orgId = $this->app['workflow']->getWorkflowMaker()->getOrgId();

        Action\Entity::verifyIdAndStripSign($actionId);

        // findByIdAndOrgId returns a collection so extracting the first element.
        // Cannot use firstorfailPublic here because findByIdAndOrgId returns collection.
        $action = $this->repo
            ->workflow_action
            ->getActionDetails($actionId, $orgId)
            ->first();

        // $action can be null because we don't validate the result after fetching from the collection.
        if (empty($action) === false)
        {
            $differService = new DifferService();

            $differEntity = $differService->fetchRequest($actionId);

            $this->trace->info(TraceCode::GET_OBSERVER_DATA, [
                DifferEntity::WORKFLOW_OBSERVER_DATA    => $differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? [],
                DifferEntity::ACTION_ID                 => $actionId,
            ]);

            return ($differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? new \stdClass());
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, $actionId);
        }
    }

    public function updateWorkflowObserverData($actionId, $input)
    {
        $core = new Action\Differ\Core();

        $this->trace->info(TraceCode::UPDATE_OBSERVER_DATA, [
            DifferEntity::WORKFLOW_OBSERVER_DATA    => $input,
            DifferEntity::ACTION_ID                 => $actionId,
        ]);

        $orgId = $this->app['workflow']->getWorkflowMaker()->getOrgId();

        $actionId = Action\Entity::verifyIdAndStripSign($actionId);

        // findByIdAndOrgId returns a collection so extracting the first element.
        // Cannot use firstorfailPublic here because findByIdAndOrgId returns collection.
        $action = $this->repo
            ->workflow_action
            ->getActionDetails($actionId, $orgId)
            ->first();

        // $action can be null because we don't validate the result after fetching from the collection.
        if (empty($action) === false)
        {
            $differEntity = (new DifferService())->fetchRequest($actionId);

            (new Observer\Validator())->validateWorkflowObserverData($differEntity, $input);

            if (empty($input[Observer\Constants::REJECTION_REASON]) === false)
            {
                $input[Observer\Constants::REJECTION_REASON] = json_encode($input[Observer\Constants::REJECTION_REASON]);
            }

            $previousObserverData = $differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? [];

            $updatedObserverData = array_filter(array_merge($previousObserverData , $input));

            $core->updateObserverDataForActionId($actionId, $updatedObserverData);
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, $actionId);
        }

        return [
            DifferEntity::WORKFLOW_OBSERVER_DATA    => $input,
        ];
    }

    protected function getWorkflowObserverDataByActionId(Action\Entity $actionEntity)
    {
        $differEntity = (new DifferCore)->fetchRequest($actionEntity->getId());

        $this->trace->info(TraceCode::GET_WORKFLOW_REJECTION_REASON, [
            DifferEntity::WORKFLOW_OBSERVER_DATA    => $differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? [],
            DifferEntity::ACTION_ID                 => $actionEntity->getId(),
        ]);

        return $differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? [];
    }

    public function getWorkflowDetailsWithRejectionMessage(?Action\Entity $actionEntity)
    {
        if (empty($actionEntity) === true)
        {
            return [Observer\Constants::WORKFLOW_EXISTS => false];
        }

        $response = [Observer\Constants::WORKFLOW_EXISTS => true, Observer\Constants::WORKFLOW_STATUS => $actionEntity->getState()];

        if ($actionEntity->isOpen() === true)
        {
            $response[Observer\Constants::WORKFLOW_CREATED_AT] = $actionEntity->getAttribute(Action\Entity::CREATED_AT);
        }

        if ($actionEntity->getState() === Name::REJECTED)
        {
            $observerData = $this->getWorkflowObserverDataByActionId($actionEntity);

            $showRejection =  $observerData[WorkflowObserverConstants::SHOW_REJECTION_REASON_ON_DASHBOARD] ?? 'true';

            if (($showRejection === 'true') and
                (isset($observerData[WorkflowObserverConstants::REJECTION_REASON])))
            {
                $rejectionReason = json_decode($observerData[WorkflowObserverConstants::REJECTION_REASON], true);

                if (key_exists(Observer\Constants::MESSAGE_BODY, $rejectionReason))
                {
                    $response = array_merge($response, [
                        Observer\Constants::REJECTION_REASON_MESSAGE => $rejectionReason[Observer\Constants::MESSAGE_BODY],
                        Observer\Constants::WORKFLOW_REJECTED_AT => $actionEntity->getAttribute(Action\Entity::UPDATED_AT),
                    ]);
                }
            }
        }

        return $response;
    }

}
