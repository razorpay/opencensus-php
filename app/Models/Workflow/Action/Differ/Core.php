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

    public function create(Action\Entity $action, array $differInput)
    {
        $diff = (new Entity)->generateId();

        $differInput[Entity::ACTION_ID] = $action->getId();

        $diff->build($differInput);

        $diff[Entity::CREATED_AT] = Carbon::now('Asia/Kolkata')->timestamp;

        // makerAction
        $function = $differInput['type'] . 'Action';

        $diff = $this->$function($diff);

        return $diff;
    }

    public function get(string $actionId)
    {
        $esResponse = $this->esDao->searchByIndexTypeAndActionId(
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
        $esResponse = $this->esDao->searchByIndexTypeAndActionId(
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

    public function saveToES(array $differ)
    {
        try
        {
            $mock = $this->config->get('database.es_workflow_action_mock');

            if ($mock === false)
            {
                $this->esDao->storeAdminEvent(
                    strtolower($this->baseIndex), self::ES_TYPE, $differ);
            }
        }
        catch(\Exception $e)
        {
            $this->trace->warning(
                TraceCode::HEIMDALL_ACTION_LOG_FAIL,
                ['msg' => $e]);
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
        // If the diff is already present, no need to run
        // the validators and compute it again.
        if (empty($differ->getDiff()) === false)
        {
            $this->saveToEs($differ->toArray());

            return $differ;
        }

        $entity = $differ->getEntityName();

        $entityId = $differ->getEntityId();

        $oldEntity = $this->repo->$entity->findByPublicId($entityId);

        $diff = [];

        $validator = EntityValidator::getValidator($differ->getRoute());

        if (empty($validator) === false)
        {
            $newEntity = clone $oldEntity;

            // Run validator
            $newEntity = $newEntity->edit($differ->getPayload(), $validator);

            $diff = $this->createDiff(
                $oldEntity->toArray(), $newEntity->toArray());
        }

        $relations = EntityValidator::getRelations($differ->getRoute());

        if (empty($relations) === false)
        {
            foreach ($relations as $relation)
            {
                // We want to show empty values for relation as it means we
                // want to reset the m2m fields.
                if (isset($differ->getPayload()[$relation]) === true)
                {
                    $relationDiff = $this->createDiffForRelations(
                        $oldEntity,
                        $relation,
                        $differ->getPayload()[$relation]);

                    $diff['old'][$relation] = $relationDiff['old'];

                    $diff['new'][$relation] = $relationDiff['new'];
                }
            }
        }

        $differ->setDiff($diff);

        $this->saveToEs($differ->toArray());

        return $differ;
    }

    // TODO: Recursion
    public function createDiff(array $oldEntity, array $newEntity)
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

    public function fetchByEntityAndEntityId(string $entity, string $entityId)
    {
        $openStates = State\Entity::OPEN_STATES;

        $matchParams = [
            Entity::ENTITY_NAME => $entity,
            Entity::ENTITY_ID   => $entityId,
        ];

        $esResponse = null;

        try
        {
            $mock = $this->config->get('database.es_workflow_action_mock');

            if ($mock === false)
            {
                $esResponse = $this->esDao->searchDifferByParams(
                    strtolower($this->baseIndex), self::ES_TYPE, $matchParams, $openStates);
            }
        }
        catch(\Exception $e)
        {
            $this->trace->warning(TraceCode::HEIMDALL_ACTION_LOG_FAIL, ['msg' => $e]);
        }

        return $esResponse;
    }

    public function updateStateInEs(string $actionId, string $state)
    {
        $searchTerms = [
            'action_id' => $actionId
        ];

        $documents = $this->esDao->getDocumentByFields(
            strtolower($this->baseIndex), self::ES_TYPE, $searchTerms);

        if (empty($documents) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND);
        }

        $document = current($documents);

        $documentId = $document['_id'];

        $esResponse = $this->esDao->updateActionState(
            strtolower($this->baseIndex), self::ES_TYPE, $documentId, $state);

        return $esResponse;
    }

    protected function createDiffForRelations($entity, $relation, $input)
    {
        $model = $entity->$relation()->getModel();

        $relatedEntityName = $model->getEntityName();

        $oldIds = $entity->$relation()->getRelatedIds()->toArray();

        $model::getSignedIdMultiple($oldIds);

        $removedEntities = array_diff($oldIds, $input);

        $addedEntities = array_diff($input, $oldIds);

        $newRelatedEntities = $this->repo
                                   ->$relatedEntityName
                                   ->findManyByPublicIds($addedEntities)
                                   ->toArrayDiff();

        $oldRelatedEntities = $this->repo
                                   ->$relatedEntityName
                                   ->findManyByPublicIds($removedEntities)
                                   ->toArrayDiff();

        $diff = [
            'old' => $oldRelatedEntities,
            'new' => $newRelatedEntities,
        ];

        return $diff;
    }
}
