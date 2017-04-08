<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\EsDao;
use RZP\Events\DifferEvent;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    protected $esDao;

    protected $baseIndex;

    protected $config;

    const ES_TYPE = 'action';

    const SKIP_DIFF_FIELDS = [
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->esDao = new EsDao();

        $this->config = $this->app['config'];

        $mode = empty($this->app['rzp.mode']) ? Mode::TEST : $this->app['rzp.mode'];

        $this->baseIndex = $this->config->get('database.es_workflow_action')[$mode];
    }

    public function create(Action\Entity $action, array $input)
    {
        $diff = (new Entity)->generateId();

        $input[Entity::ACTION_ID] = $action->getId();

        $diff->build($input);

        $diff[Entity::CREATED_AT] = Carbon::now('Asia/Kolkata')->timestamp;

        // makerAction
        $function = $input['type'] . 'Action';

        $diff = $this->$function($diff);

        return $diff;
    }

    public function get(string $actionId)
    {
        $esResponse = $this->esDao->search(
            strtolower($this->baseIndex), self::ES_TYPE, $actionId);

        if ($esResponse === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND);
        }

        $diff = [];

        if (array_key_exists(Entity::DIFF, $esResponse[0]['_source']))
        {
            $diff = $esResponse[0]['_source'][Entity::DIFF];
        }

        return $diff;
    }

    public function fetchRequest(Action\Entity $action)
    {
        $esResponse = $this->esDao->search(
            strtolower($this->baseIndex), self::ES_TYPE, $action->getId());

        if ($esResponse === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND);
        }

        $esObject = $esResponse[0]['_source'];

        $controller = $esObject[Entity::CONTROLLER];

        $controllerSplit = explode('@', $controller);

        return [
            Entity::ROUTE_PARAMS  => $esObject[Entity::ROUTE_PARAMS],
            Entity::PAYLOAD       => $esObject[Entity::PAYLOAD],
            Entity::CONTROLLER    => $controllerSplit[0],
            Entity::FUNCTION_NAME => $controllerSplit[1],
        ];
    }

    public function saveToES(array $action)
    {
        try
        {
            $mock = $this->config->get('database.es_workflow_action_mock');

            if ($mock === false)
            {
                $this->esDao->storeAdminEvent(
                    strtolower($this->baseIndex), self::ES_TYPE, $action);
            }
        }
        catch(\Exception $e)
        {
            $this->trace->warning(TraceCode::HEIMDALL_ACTION_LOG_FAIL, ['msg' => $e]);
        }
    }

    /*
        This method is responsible for:
        1. Running Validator
        2. Creating Diff
        3. Storing Diff in ES
    */
    protected function makerAction(Entity $differ)
    {
        $entity = $differ->getEntityName();

        $entityId = $differ->getEntityId();

        $oldEntity = $this->repo->$entity->findByPublicId($entityId);

        $newEntity = clone $oldEntity;

        // Get the appropriate validator
        $validator = EntityValidator::getValidator($differ->getRoute());

        if ($validator !== null)
        {
            // Run validator
            $newEntity = $newEntity->edit($differ->getPayload(), $validator);

            $diff = $this->createDiff($oldEntity->toArray(), $newEntity->toArray());

            $differ->setDiff($diff);
        }

        // Calls `saveToEs` above
        event(new DifferEvent($differ->toArray()));

        return $differ;
    }

    // TODO: Recursion
    protected function createDiff(array $oldEntity, array $newEntity)
    {
        $diff = [];

        $keys = array_keys($oldEntity);

        $diffKeys = array_diff($keys, self::SKIP_DIFF_FIELDS);

        foreach ($diffKeys as $key)
        {
            if ($oldEntity[$key] !== $newEntity[$key])
            {
                $diff['old'][$key] = $oldEntity[$key];

                $diff['new'][$key] = $newEntity[$key];
            }
        }

        return $diff;
    }

    public function fetchByEntityAndActionIds(
        string $entity,
        string $entityId)
    {
        $openStates = State\Entity::OPEN_STATES;

        $matchParams = [
            Entity::ENTITY_NAME => $entity,
            Entity::ENTITY_ID   => $entityId,
        ];

        $esResponse = $this->esDao->search(
            strtolower($this->baseIndex),
            self::ES_TYPE, $matchParams, $openStates);

        if ($esResponse === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND);
        }

        $diffs = [];

        foreach ($esResponse as $document)
        {
            $diffs = $document[0]['_source'][Entity::DIFF];
        }

        return $diffs;
    }

    public function updateStateInEs(
        string $actionId,
        string $state)
    {
        $esResponse = $this->esDao->updateActionState(
            strtolower($this->baseIndex), self::ES_TYPE, $action->getId(), $state);

        if ($esResponse === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND);
        }


    }
}
