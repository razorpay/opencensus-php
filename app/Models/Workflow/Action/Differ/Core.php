<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Error;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\EsDao;
use RZP\Events\DifferEvent;

class Core extends Base\Core
{
    protected $esDao;

    protected $baseIndex;

    protected $config;

    protected $factory;

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

        $this->factory = $this->app->make('httplug.message_factory.default');
    }

    public function create(string $actionId, array $input)
    {
        $diff = (new Entity)->generateId();

        $input[Entity::ACTION_ID] = $actionId;

        $diff->build($input);

        $diff[Entity::CREATED_AT] = Carbon::now('Asia/Kolkata')->timestamp;

        $function = $input['type'] . 'Action';

        $diff = $this->$function($diff);

        return $action;
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

    public function fetchRequest(string $actionId)
    {
        $esResponse = $this->esDao->search(
            strtolower($this->baseIndex), self::ES_TYPE, $actionId);

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
            if ($this->config->get('database.es_workflow_action_mock') === false)
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

    protected function makerAction(Entity $differ)
    {
        $entity = $differ->getEntityName();

        $entityId = $differ->getEntityId();

        $oldEntity = $this->repo->$entity->findByPublicId($entityId);

        $newEntity = clone $oldEntity;

        $validator = EntityValidator::getValidator($differ->getRoute());

        if ($validator !== null)
        {
            $newEntity = $newEntity->edit($differ->getPayload(), $validator);

            $diff = $this->createDiff($oldEntity->toArray(), $newEntity->toArray());

            $differ->setDiff($diff);
        }

        event(new DifferEvent($differ->toArray()));

        return $differ;
    }

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
}
