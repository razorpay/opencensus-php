<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Error;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\EsDao;

class Service extends Base\Service
{
    protected $esDao;

    protected $baseIndex;

    protected $config;

    const ES_TYPE = 'action';

    public function __construct()
    {
        parent::__construct();

        $this->esDao = new EsDao();

        $this->config = $this->app['config'];

        $mode = empty($this->app['rzp.mode']) ? Mode::TEST : $this->app['rzp.mode'];

        $this->baseIndex = $this->config->get('database.es_action')[$mode];
    }

    public function createAction(string $entity, string $entityId, array $input)
    {
        $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

        $user = $dashboardInfo['admin_user'] ?: $dashboardInfo['merchant'];

        $input[Entity::ACTOR] = $user;

        $input[Entity::ENTITY_NAME] = $entity;

        $input[Entity::ENTITY_ID] = $entityId;

        $action = (new Entity)->generateId();

        $action->build($input);

        $action[Entity::CREATED_AT] = Carbon::now('Asia/Kolkata')->timestamp);

        $function = 'create' .$input['type'] .'Action';
        $E = $this->$function($action);

        return $E->toArrayPublic();
    }

    public function fetchDiffById(string $id)
    {
        $esResponse = $this->esDao->searchAction(strtolower($this->baseIndex), self::ES_TYPE, $id);

        if ($esResponse === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACTION_NOT_FOUND);
        }

        $diff = $esResponse[0]['_source'][Entity::DIFF];

        return $diff;
    }

    protected function createMakerAction(Entity $action)
    {
        $entity = $action[Entity::ENTITY_NAME];

        $entityId = $action[Entity::ENTITY_ID];

        $oldE = $this->repo->$entity->findByPublicId($entityId);

        $newE = clone $oldE;

        $newE->edit($action[Entity::PAYLOAD]);

        $diff = $this->createDiff($oldE->toArray(), $newE->toArray());

        $action->setDiff($diff);

        try
        {
            if ($this->config->get('database.es_action_mock') === false)
            {
                $this->esDao->storeAdminEvent(
                    strtolower($this->baseIndex), self::ES_TYPE, $action->toArray()
                );
            }
        }
        catch(\Exception $e)
        {
            $this->trace->warning(TraceCode::HEIMDALL_ACTION_LOG_FAIL, ['msg' => $e]);
        }

        return $newE;
    }

    protected function createDiff(array $oldE, array $newE)
    {
        $diff = [];

        $keys = array_keys($oldE);

        foreach ($keys as $key)
        {
            if ($oldE[$key] !== $newE[$key])
            {
                $diff['old'][$key] = $oldE[$key];

                $diff['new'][$key] = $newE[$key];
            }
        }

        return $diff;
    }

    public function execute(string $id)
    {
        $esResponse = $this->esDao->searchAction(strtolower($this->baseIndex), self::ES_TYPE, $id);
    }
}
