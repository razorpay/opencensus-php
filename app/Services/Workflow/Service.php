<?php

namespace RZP\Services\Workflow;

use RZP\Exception;
use RZP\Models\State;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Helper;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Workflow\Action\Differ;
use RZP\Exception\EarlyWorkflowResponse;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Workflow\PayoutAmountRules;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Models\Workflow\Action\Differ\EntityValidator;

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

    protected $controller;

    protected $routeName;

    protected $routeParams;

    protected $method;

    protected $input;

    protected $diff = [];

    protected $originalData;

    protected $dirtyData;

    protected $tags = [];

    private $workflowMaker;

    private $workflowMakerType;

    /**
     * @var bool This variable decides from where to fetch workflow maker
     * i.e from auth context or from the instance variable $workflowMaker;
     * check method initWorkflowMaker
     */
    private $makerFromAuth = true;

    /**
     * @var bool
     */
    protected $skipWorkflow = false;
    /**
     * @var BasicAuth
     */
    protected $ba;

    public function __construct($app)
    {
        $this->app = $app;

        $this->router = $this->app['router'];

        $this->config = $this->app['config'];

        $this->request = $this->app['request'];

        $this->ba = $this->app['basicauth'];

        $this->routeName = $this->router->currentRouteName();

        if(empty($this->router->current()) === false)
        {
            $this->routeParams = $this->router->current()->parameters();
        }

        $this->controller = $this->router->currentRouteAction();

        $this->initWorkflowMaker();
    }

    public function getRouteParams()
    {
        return $this->routeParams;
    }

    public function setRouteParams($routeParams)
    {
        $this->routeParams = $routeParams;

        return $this;
    }

    public function getRouteName()
    {
        return $this->routeName;
    }

    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;

        return $this;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    public function getTags()
    {
        return $this->tags;
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
            $routeParams = $this->getRouteParams();
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

        $params[Action\Entity::TAGS] = $this->getTags();

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
        $this->setRequestParametersWhereApplicable();

        $input = $this->getInput();

        $routeName = $this->getRouteName();

        $controller = $this->getController();

        $routeParams = $this->getRouteParams();

        $permission = $this->getPermission();

        $maker = $this->getWorkflowMaker();

        $makerName = $maker->getName() ?? $maker->getEmail();

        $differEntity = [
            Differ\Entity::ENTITY_NAME              => $entity,
            Differ\Entity::ENTITY_ID                => $entityId,
            Differ\Entity::MAKER                    => $makerName,
            Differ\Entity::MAKER_ID                 => $maker->getId(),
            Differ\Entity::MAKER_TYPE               => $this->getWorkflowMakerType(),
            Differ\Entity::TYPE                     => Differ\Type::MAKER,
            Differ\Entity::URL                      => $request->getUri(),
            Differ\Entity::ROUTE_PARAMS             => $routeParams,
            Differ\Entity::METHOD                   => $this->getMethod(),
            Differ\Entity::PAYLOAD                  => $input,
            Differ\Entity::STATE                    => State\Name::OPEN,
            Differ\Entity::CONTROLLER               => $controller,
            Differ\Entity::ROUTE                    => $routeName,
            Differ\Entity::PERMISSION               => $permission,
            Differ\Entity::WORKFLOW_OBSERVER_DATA   => $input[Differ\Entity::WORKFLOW_OBSERVER_DATA] ?? [],
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

    public function getInput()
    {
        return $this->input;
    }

    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    public function setDiff($diff)
    {
        $this->diff = $diff;

        return $this;
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

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    protected function permissionHasWorkflow()
    {
        $permission = $this->getPermission();

        if (empty($permission) === true)
        {
            return false;
        }

        $maker = $this->getWorkflowMaker();

        if (empty($maker) === true)
        {
            return false;
        }

        $merchantId = null;

        //
        // For merchant app permissions, maker=merchant, we send the merchant ID for fetching
        // only workflows defined for the merchant
        //
        if (Permission\Name::isMerchantPermission($permission) === true)
        {
            $merchantId = $maker->getId();
        }

        $permissionHasWorkflow = (new WorkflowService)->permissionHasWorkflow(
            $permission, $maker->getOrgId(), $merchantId);

        return $permissionHasWorkflow;
    }

    /**
     * In case of an edit operation both $originalData
     * and $dirtyData should be set.
     *
     * In case of an "add" operation pass an empty stdClass
     * object as $originalData.
     *
     * In case of a "delete" operation pass an empty stdClass
     * object as $dirtyData.
     *
     * Allowed types for both: object, array
     *
     * @param null $originalData
     * @param null $dirtyData
     *
     * @throws Exception\BadRequestException
     */
    public function handle($originalData = null, $dirtyData = null, $nextWorkflowPresent = false)
    {
        // 0. If workflows need to be skipped for some reason, skip
        if ($this->skipWorkflow === true)
        {
            return;
        }

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
            if(isset($originalData) === true and is_array($originalData))
            {
                $originalDataArray =  $originalData;
            }

            else if (isset($originalData) === true and method_exists($originalData, 'toArray') === true)
            {
                $originalDataArray = $originalData->toArray();

                //Unsetting Merchant Website and Merchant business detail (in case of Merchant Detail Entity), so these aren't synced with ES.
                //Sending merchant_website and merchant_business_detail is causing unprecedented growth of fields on this ES index
                //because every website and business_detail.plugin_details is considered as a new field in ES (because of JSON structure).
                unset($originalDataArray['merchant_website']);

                unset($originalDataArray['business_detail']['plugin_details']);


                // Set entity
                $this->setEntity($originalData->getEntityName());
            }
            // If stdClass()
            else
            {
                $originalDataArray = (array) $originalData;
            }

            // If eloquent model
            if (is_object($dirtyData) and method_exists($dirtyData, 'toArray') === true)
            {
                $dirtyDataArray = $dirtyData->toArray();

                //Unsetting Merchant Website and Merchant business detail (in case of Merchant Detail Entity), so these aren't synced with ES.
                //Sending merchant_website and merchant_business_detail is causing unprecedented growth of fields on this ES index
                //because every website and business_detail.plugin_details is considered as a new field in ES (because of JSON structure).
                unset($dirtyDataArray['merchant_website']);

                unset($dirtyDataArray['business_detail']['plugin_details']);

                // Set entity (redundant if it already got set above from $originalData)
                $this->setEntity($dirtyData->getEntityName());
            }
            // If stdClass()
            else
            {
                $dirtyDataArray = (array) $dirtyData;
            }
        }

        // Custom code for payout workflow handling here
        if ($this->getPermission() === Permission\Name::CREATE_PAYOUT)
        {
            $shouldProcess = $this->shouldProcessPayoutWorkflow($dirtyDataArray, $this->getWorkflowMaker());

            if ($shouldProcess === false)
            {
                return;
            }
        }

        // Calculate diff
        $diff = $differCore->createDiff(
            $originalDataArray, $dirtyDataArray);

        // Logic to calculate diff for nested relations
        $mainEntity = $this->getEntity();

        $routeName = $this->router->currentRouteName();

        $relations = EntityValidator::getRelations($routeName);

        if (is_object($originalData) === true
               and method_exists($originalData, 'toArray') === true)
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

        $diff = (new Helper())->redactFields($diff);

        $this->setDiff($diff);

        // Trigger the entity maker/checker (workflow) flow
        $workflowAction = $this->trigger();

        $workflowAction = json_encode($workflowAction);


        // When there's a workflow action throw an exception
        // to abort further flow of code (services, controllers, etc.)

        if($nextWorkflowPresent === false)
        {
            throw new EarlyWorkflowResponse(
                200,
                $workflowAction,
                null,
                ['Content-Type' => 'application/json']
            );
        }
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
            $data['tags']= $this->getTags();
            $action = $core->create($data, $retry = true, $maker);
        }
        else
        {
            $action = $workflowAction->first();
        }

        return $action->toArrayPublic();
    }

    public function setMakerFromAuth(bool $val)
    {
        $this->makerFromAuth = $val;

        return $this;
    }

    public function initWorkflowMaker()
    {
        // If admin auth then return Admin
        if ($this->ba->isAdminAuth() === true)
        {
            $this->workflowMaker = $this->ba->getAdmin();
            $this->workflowMakerType = MakerType::ADMIN;
            return;
        }

        // If any other auth but admin then return merchant
        if ($this->ba->getMerchant() !== null)
        {
            $this->workflowMaker = $this->ba->getMerchant();
            $this->workflowMakerType = MakerType::MERCHANT;
            return;
        }
    }

    public function getWorkflowMaker()
    {
        if($this->makerFromAuth or empty($this->workflowMaker) === true)
        {
            $this->initWorkflowMaker();
        }
        return $this->workflowMaker;
    }

    public function setWorkflowMaker($maker)
    {
        $this->workflowMaker = $maker;

        return $this;
    }

    public function setWorkflowMakerType($makerType)
    {
        $this->workflowMakerType = $makerType;

        return $this;
    }

    public function getWorkflowMakerType()
    {
        if ($this->makerFromAuth or empty($this->workflowMakerType) === true)
        {
            $this->initWorkflowMaker();
        }

        return $this->workflowMakerType;
    }

    /**
     * Execute the callable while skipping defined workflows
     *
     * @param callable $callback
     *
     * @return mixed
     */
    public function skipWorkflows(callable $callback)
    {
        $this->skipWorkflow = true;

        $result = $callback();

        $this->skipWorkflow = false;

        return $result;
    }

    private function shouldProcessPayoutWorkflow(array $input, Merchant\Entity $merchant): bool
    {
        //
        // If the code reaches here, there was atleast one workflow defined for the create_payout
        // permission.
        // We now need to check specific amount rules via workflow_payout_amount_rules.
        // There may be cases where no workflow has been defined for a certain amount range,
        // and hence, we should not trigger any workflow.
        //

        // The amount param will always exist here, Payout validators ensure this before reaching here.
        $amount = $input[Payout\Entity::AMOUNT];

        $workflow = (new PayoutAmountRules\Core)->fetchWorkflowForMerchantIfDefined($amount, $merchant);

        if ($workflow === null)
        {
            return false;
        }

        return true;
    }

    protected function setRequestParametersWhereApplicable()
    {
        if ($this->getInput() === null)
        {
            $this->setInput($this->request->input());
        }

        if ($this->getMethod() === null)
        {
            $this->setMethod($this->request->getMethod());
        }

        if ($this->getRouteName() === null)
        {
            $this->setRouteName($this->router->currentRouteName());
        }

        if ($this->getController() === null)
        {
            $this->setController($this->router->currentRouteAction());
        }

        if ($this->getRouteParams() === null)
        {
            $this->setRouteParams($this->router->current()->parameters());
        }
    }
}
