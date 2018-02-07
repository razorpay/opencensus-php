<?php

namespace RZP\Services\Workflow;

use RZP\Exception;
use RZP\Models\State;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Differ;
use RZP\Exception\EarlyWorkflowResponse;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Models\Workflow\Action\Differ\EntityValidator;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Constants\Entity as ConstantsEntity;

class Service
{
    const WILDCARD_PERMISSION = '*';

    const WORKFLOW_CONTROLLER = 'RZP\Http\Controllers\WorkflowController';

    const EXECUTE_ROUTE_NAME = 'action_request_execute';

    protected $router;

    protected $entity;

    protected $entityId;

    protected $config;

    protected $permission;

    protected $diff = [];

    protected $originalData;

    protected $dirtyData;

    public function __construct($app)
    {
        $this->app = $app;

        $this->router = $this->app['router'];

        $this->config = $this->app['config'];

        $this->request = $this->app['request'];

        $this->ba = $this->app['basicauth'];
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityId()
    {
        return $this->entityId;
    }

    public function setEntityAndId(string $entity, string $entityId)
    {
        $this->entity = $entity;

        $this->entityId = $entityId;

        return $this;
    }

    public function trigger()
    {
        // Main entity to act upon and calculate the diff
        $entity = $this->getEntity();

        if (empty($entity))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ENTITY_NOT_FOUND);
        }

        $entityId = $this->getEntityId();

        // This block works for "edit" operations only
        if (empty($entityId) === true)
        {
            $routeParams = $this->router->current()->parameters();
            // Pick the `id` first, if not then the first value
            // First value is not entirely robust though
            $entityId = $routeParams['id'] ?? (array_values($routeParams)[0] ?? null);
        }

        // If any actions are in open/approved (not executed) state
        // on the main $entity then prevent new workflows from being created.
        if (empty($entityId) === false)
        {
            (new Action\Validator)->validateLiveActionsOnEntity(
                $entityId,
                $entity,
                $this->getPermission());
        }

        // Input data for Differ\Entity (stored in ES)
        // It contains the diff entity to show on the UI + payload to trigger
        // the request on execute operation.
        $params = $this->createDifferEntity($this->request, $entity, $entityId);

        // returns Workflow\Action\Entity->toArrayPublic()
        $data = (new Action\Service)->create($params);

        return $data;
    }

    /*
        Resolve a bunch of data points through which we can
        compute a diff as well as later execute the actual
        action once all the checkers have approved this
        incoming request.

        This Differ Entity is stored in ES.
    */
    private function createDifferEntity($request, $entity, $entityId)
    {
        $input = $request->input();

        $routeName = $this->router->currentRouteName();

        $controller = $this->router->currentRouteAction();

        $routeParams = $this->router->current()->parameters();

        $permission = $this->getPermission();

        $maker = $this->getWorkflowMaker();

        $makerName = $maker->getName() ?? $maker->getEmail();

        $differEntity = [
            Differ\Entity::ENTITY_NAME  => $entity,
            Differ\Entity::ENTITY_ID    => $entityId,
            Differ\Entity::MAKER        => $makerName,
            Differ\Entity::MAKER_ID     => $maker->getId(),
            Differ\Entity::MAKER_TYPE   => $this->getWorkflowMakerType(),
            Differ\Entity::TYPE         => Differ\Type::MAKER,
            Differ\Entity::URL          => $request->getUri(),
            Differ\Entity::ROUTE_PARAMS => $routeParams,
            Differ\Entity::METHOD       => $request->getMethod(),
            Differ\Entity::PAYLOAD      => $input,
            Differ\Entity::STATE        => State\Name::OPEN,
            Differ\Entity::CONTROLLER   => $controller,
            Differ\Entity::ROUTE        => $routeName,
            Differ\Entity::PERMISSION   => $permission,
        ];

        $diff = $this->getDiff();

        // we will consider empty array as valid diff for now
        if ((is_array($diff) === true) or (empty($diff) === false))
        {
            $differEntity[Differ\Entity::DIFF] = $diff;
        }

        // Auth Details
        $authDetails = [];

        // If proxy auth set merchant ID
        if ($this->ba->isProxyAuth() === true)
        {
            $authDetails['merchant_id'] = $this->ba->getMerchant()->getId();
        }

        if (empty($authDetails) === false)
        {
            $differEntity[Differ\Entity::AUTH_DETAILS] = $authDetails;
        }

        return $differEntity;
    }

    public function setPermission($permission)
    {
        $this->permission = $permission;

        return $this;
    }

    public function getPermission()
    {
        return $this->permission;
    }

    public function setDiff($diff)
    {
        $this->diff = $diff;
    }

    public function getDiff()
    {
        return $this->diff;
    }

    public function setOriginal($entity)
    {
        $this->originalData = $entity;

        return $this;
    }

    public function getOriginal()
    {
        return $this->originalData;
    }

    public function setDirty($entity)
    {
        $this->dirtyData = $entity;

        return $this;
    }

    public function getDirty()
    {
        return $this->dirtyData;
    }

    protected function permissionHasWorkflow()
    {
        $permission = $this->getPermission();

        if (empty($permission) === true)
        {
            return false;
        }

        $maker = $this->getWorkflowMaker();

        if ($maker === false)
        {
            return false;
        }

        $permissionHasWorkflow = (new WorkflowService)->permissionHasWorkflow(
            $permission, $maker->getOrgId());

        return $permissionHasWorkflow;
    }

    /*
        In case of an edit operation both $originalData
        and $dirtyData should be set.

        In case of an "add" operation pass an empty stdClass
        object as $originalData.

        In case of a "delete" operation pass an empty stdClass
        object as $dirtyData.

        Allowed types for both: object, array
    */
    public function handle($originalData = null, $dirtyData = null)
    {
        // 1. If the permission has no workflow
        $permissionHasWorkflow = $this->permissionHasWorkflow();

        // 2. Workflow is mocked
        $workflowIsMocked = $this->config->get('heimdall.workflows.mock');

        // 3. Execute or approve call
        $executeOrApprovedCall = $this->app['api.route']->isWorkflowExecuteOrApproveCall();

        if (($permissionHasWorkflow === false) or
            ($workflowIsMocked === true) or
            ($executeOrApprovedCall === true))
        {
            return;
        }

        // Instantiate code for diff creation
        $differCore = new Differ\Core;

        // Fetch from getters if arguments are null
        if (($originalData === null) and ($dirtyData === null))
        {
            $originalData = $this->getOriginal();

            $dirtyData = $this->getDirty();
        }

        if ((is_array($originalData) === true) and (is_array($dirtyData) === true))
        {
            $originalDataArray = $originalData;

            $dirtyDataArray = $dirtyData;
        }
        else
        {
            // If eloquent model
            if (method_exists($originalData, 'toArray') === true)
            {
                $originalDataArray = $originalData->toArray();

                // Set entity
                $this->setEntity($originalData->getEntityName());
            }
            // If stdClass()
            else
            {
                $originalDataArray = (array) $originalData;
            }

            // If eloquent model
            if (method_exists($dirtyData, 'toArray') === true)
            {
                $dirtyDataArray = $dirtyData->toArray();

                // Set entity (redundant if it already got set above from $originalData)
                $this->setEntity($dirtyData->getEntityName());
            }
            // If stdClass()
            else
            {
                $dirtyDataArray = (array) $dirtyData;
            }
        }

        // Calculate diff
        $diff = $differCore->createDiff(
            $originalDataArray, $dirtyDataArray);

        // Logic to calculate diff for nested relations
        $mainEntity = $this->getEntity();

        $routeName = $this->router->currentRouteName();

        $relations = EntityValidator::getRelations($routeName);

        if (method_exists($originalData, 'toArray') === true)
        {
            foreach ($relations as $relation)
            {
                $originalDataArray[$relation] = $originalData->$relation()->allRelatedIds()->toArray();
            }
        }

        $differCore->createAllRelationsDiff(
            $diff,
            $originalDataArray,
            $dirtyDataArray,
            $mainEntity,
            $relations);

        $this->setDiff($diff);

        // Trigger the entite maker/checker (workflow) flow
        $workflowAction = $this->trigger();

        $workflowAction = json_encode($workflowAction);

        // When there's a workflow action throw an exception
        // to abort further flow of code (services, controllers, etc.)
        throw new EarlyWorkflowResponse(
            200,
            $workflowAction,
            null,
            ['Content-Type' => 'application/json']
        );
    }

    public function saveActionIfTransactionFailed(array $data)
    {
        $core = new Action\Core;

        $workflowAction = $core->getByIdAndOrgId($data['id'], $data['org_id']);

        $count = $workflowAction->count();

        $maker = $this->getWorkflowMaker();

        // Transaction failed and no entry was created
        if ($count === 0)
        {
            // Let's re-try creating workflow action and relevant entities

            $action = $core->create($data, $retry = true, $maker);
        }
        else
        {
            $action = $workflowAction->first();
        }

        return $action->toArrayPublic();
    }

    public function getWorkflowMaker()
    {
        if ($this->ba->isAdminAuth() === true)
        {
            return $this->ba->getAdmin();
        }

        if ($this->ba->getMerchant() !== null)
        {
            return $this->ba->getMerchant();
        }

        return false;
    }

    public function getWorkflowMakerType()
    {
        if ($this->ba->isAdminAuth() === true)
        {
            return MakerType::ADMIN;
        }

        if ($this->ba->getMerchant() !== null)
        {
            return MakerType::MERCHANT;
        }

        return false;
    }
}
