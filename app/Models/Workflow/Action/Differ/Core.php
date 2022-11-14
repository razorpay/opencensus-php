<?php

namespace RZP\Models\Workflow\Action\Differ;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\EsDao;
use RZP\Models\State\Name;
use RZP\Events\DifferEvent;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Helper;
use RZP\Models\Admin\Permission;
use \RZP\Models\Workflow\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Workflow\Action\Differ;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Merchant\Document\FileHandler\Factory;
use RZP\Models\Merchant\Document\Type as DocumentType;

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

        $diff[Entity::CREATED_AT] = Carbon::now()->getTimestamp();

        $diff = $this->makerAction($diff);

        (new Service())->performActionOnObserver($action->getId(), Name::OPEN, $diff);

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

        $merchantId = null;

        if ((array_key_exists(Entity::MAKER_TYPE, $esResponse[0]['_source']) === true) and
            (array_key_exists(Entity::MAKER_ID, $esResponse[0]['_source']) === true))
        {
            if ($esResponse[0]['_source'][Entity::MAKER_TYPE] === E::MERCHANT)
            {
                $merchantId = $esResponse[0]['_source'][Entity::MAKER_ID];
            }
        }

        $diff = [];

        if (array_key_exists(Entity::DIFF, $esResponse[0]['_source']))
        {
            $diff = $esResponse[0]['_source'][Entity::DIFF];
        }

        $diff['old'] = $this->transformFileIdsToUrls($diff['old'], $merchantId);
        $diff['new'] = $this->transformFileIdsToUrls($diff['new'], $merchantId);

        return $diff;
    }


    /**
     * Code for getting the expiring URLs for the files
     * Transforming those urls inline
     *
     * @param $diff
     * @param $merchantId
     *
     * @return mixed
     */
    private function transformFileIdsToUrls($diff, $merchantId)
    {
        $detailService = new Detail\Service();

        foreach ($diff as $key => $value)
        {
            if (DocumentType::isValid($key) === true)
            {
                $diff[$key] = (function($value) use ($detailService, $merchantId) {

                    if (empty($value) === false)
                    {
                        $source = (new Factory())->getDocumentSource($value, $merchantId);

                        return $detailService->getSignedUrl($value, $merchantId, $source);
                    }

                    return '';

                })($value);
            }
        }

        return $diff;
    }

    public function fetchRequest(string $actionId)
    {
        $esResponse = $this->esDao->searchByIndexTypeAndActionId(
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
            Entity::ROUTE_PARAMS            => $esObject[Entity::ROUTE_PARAMS],
            Entity::PAYLOAD                 => $esObject[Entity::PAYLOAD],
            Entity::CONTROLLER              => $controllerSplit[0],
            Entity::FUNCTION_NAME           => $controllerSplit[1],
            Entity::AUTH_DETAILS            => $esObject[Entity::AUTH_DETAILS] ?? [],
            Entity::ROUTE                   => $esObject[Entity::ROUTE],
            Entity::WORKFLOW_OBSERVER_DATA  => $esObject[Entity::WORKFLOW_OBSERVER_DATA] ?? [],
            Entity::ENTITY_ID               => $esObject[Entity::ENTITY_ID] ?? '',
            Entity::ENTITY_NAME             => $esObject[Entity::ENTITY_NAME] ?? '',
            Entity::PERMISSION              => $esObject[Entity::PERMISSION] ?? '',
            Entity::DIFF                    => $esObject[Entity::DIFF] ?? null,
        ];
    }

    public function saveToES(array $differ)
    {
        try
        {
            $mock = $this->config->get('database.es_workflow_action_mock');

            if ($mock === false)
            {
                //
                // ES does not store sequential array, so if notes comes as sequential array,
                // need to convert it to associative array prepending "notes_key_"
                // If the key is "" then also do this transformation
                //
                // Input:
                // "notes": [
                //    	"Testing approve workflow"
                //	]
                //
                // Output:
                // "notes": {
                //    	"notes_key_0": "Testing approve workflow"
                //	}
                //
                $notes = $differ[Differ\Entity::DIFF][Differ\Entity::NEW][Payout\Entity::NOTES] ?? [];

                $notesDict = [];

                $pos = 0;

                foreach ($notes as $key => $val)
                {
                    if ((empty($key) === true) or
                        (is_numeric($key) === true))
                    {
                        $notesDict['notes_key_' . ($pos++)] = $val;
                    }
                    else
                    {
                        $notesDict[$key] = $val;
                    }
                }

                if (empty($notesDict) === false)
                {
                    $differ[Differ\Entity::PAYLOAD][Payout\Entity::NOTES] = $notesDict;

                    $differ[Differ\Entity::DIFF][Differ\Entity::NEW][Payout\Entity::NOTES] = $notesDict;
                }

                /*
                  * Encrypt the keys like password before pushing to ES
                  * If the request is approved the request is replayed.
                  * Before replay these keys are decrypted in
                  * app/Models/Workflow/Action/Core.php decryptFields
                 */
                $differ[Differ\Entity::PAYLOAD] = (new Helper())->encryptSensitiveFields($differ[Differ\Entity::PAYLOAD]);

                $this->esDao->storeAdminEvent(
                        strtolower($this->baseIndex), self::ES_TYPE, $differ);

            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::HEIMDALL_ACTION_LOG_FAIL,
                $differ);

            throw $e;
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
        //
        // The diff will be present when workflow is triggered
        // from within the code. Absense of diff means the workflow
        // is being triggered right from Middleware\Workflow.
        //
        // Check EntityValidator to get a list of the ones being triggered
        // from the middleware.

        if (empty($differ->getDiff()) === false)
        {
            $this->saveToEs($differ->toArray());

            return $differ;
        }

        // Flow triggered from Middleware\Workflow

        $op = null;

        $entity = $differ->getEntityName();

        $entityId = $differ->getEntityId();

        $diff = [];

        $validator = EntityValidator::getValidator($differ->getRoute());

        $oldEntityData = $newEntityData = [];

        if (empty($validator) === false)
        {
            try
            {
                // EDIT or DELETE op

                $oldEntity = $this->repo->$entity->findByPublicId($entityId);

                $newEntity = clone $oldEntity;

                // Run validator
                $newEntity = $newEntity->edit($differ->getPayload(), $validator);

                $oldEntityData = $oldEntity->toArray();

                $newEntityData = $newEntity->toArray();
            }
            catch (\RZP\Exception\BadRequestException $e)
            {
                $errorCode = $e->getCode();

                if ($errorCode === ErrorCode::BAD_REQUEST_INVALID_ID)
                {
                    // CREATE op

                    $oldEntity = new \stdClass();

                    $oldEntityData = [];

                    $entityClass = ConstantsEntity::getEntityClass($entity);

                    $newEntity = new $entityClass;

                    // Run validator
                    $newEntity = $newEntity->build($differ->getPayload(), $validator);

                    $newEntityData = $newEntity->toArray();
                }
            }
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ENTITY_VALIDATOR_RULE_NOT_FOUND);
        }

        $diff = $this->createDiff(
            $oldEntityData, $newEntityData);

        $relations = EntityValidator::getRelations($differ->getRoute());

        if (method_exists($oldEntity, 'toArray') === true)
        {
            foreach ($relations as $relation)
            {
                $oldEntityData[$relation] = $oldEntity->$relation()->allRelatedIds()->toArray();
            }
        }

        // Have to use $differ->getPayload() as new entity data
        // because build() or edit() wouldn't set the associations
        $this->createAllRelationsDiff(
            $diff,
            $oldEntityData,
            $differ->getPayload(),
            $entity,
            $relations);

        $diff = (new Helper())->redactFields($diff);

        $differ->setDiff($diff);

        $this->saveToEs($differ->toArray());

        return $differ;
    }

    // TODO: Recursion
    public function createDiff(array $original, array $dirty)
    {
        $diff = [];

        $keys = array_merge(array_keys($original), array_keys($dirty));
        $keys = array_values(array_unique($keys));

        $diffKeys = array_diff($keys, self::SKIP_DIFF_FIELDS);

        foreach ($diffKeys as $key)
        {
            // Can be scalar or an array
            $originalData = $original[$key] ?? null;

            // Can be scalar or an array
            $dirtyData = $dirty[$key] ?? null;

            $originalDataIsIndexedArray = $dirtyDataIsIndexedArray = false;

            if ((is_array($originalData) === true) and
                (is_associative_array($originalData) === false))
            {
                $originalDataIsIndexedArray = true;
            }

            if ((is_array($dirtyData) === true) and
                (is_associative_array($dirtyData) === false))
            {
                $dirtyDataIsIndexedArray = true;
            }

            if (($originalDataIsIndexedArray === true) or
                ($dirtyDataIsIndexedArray === true))
            {
                $originalData = $originalData ?? [];

                $dirtyData = $dirtyData ?? [];

                // array_values() is used to re-set indexes
                // [54 => 'YESB'] => [0 => 'YESB']

                $orgDirtyDiff = array_values(array_diff(array_map('serialize',$originalData),
                                                        array_map('serialize', $dirtyData)));
                $dirtyOrgDiff = array_values(array_diff(array_map('serialize',$dirtyData),
                                                        array_map('serialize', $originalData)));

                $orgDirtyDiff = array_map('unserialize', $orgDirtyDiff);
                $dirtyOrgDiff = array_map('unserialize', $dirtyOrgDiff);

                if ((empty($orgDirtyDiff) === false) or
                    (empty($dirtyOrgDiff) === false))
                {
                    $diff['old'][$key] = $orgDirtyDiff;

                    $diff['new'][$key] = $dirtyOrgDiff;
                }
            }
            else
            {
                // Compute scalar value differences (first level)
                if ($originalData !== $dirtyData)
                {
                    $diff['old'][$key] = $originalData;

                    $diff['new'][$key] = $dirtyData;
                }
            }
        }

        return $diff;
    }

    public function getDocumentsFromEs(string $actionId)
    {
        $searchTerms = [
            'action_id' => $actionId
        ];

        return $this->esDao->getDocumentByFields(
            strtolower($this->baseIndex), self::ES_TYPE, $searchTerms);
    }

    public function updateStateInEs(string $actionId, string $state)
    {
        $documents = $this->getDocumentsFromEs($actionId);

        if (empty($documents) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND);
        }

        $document = current($documents);

        $documentId = $document['_id'];

        (new Service())->performActionOnObserver($actionId, $state);

        $esResponse = $this->esDao->updateActionState(
            strtolower($this->baseIndex), self::ES_TYPE, $documentId, $state);

        return $esResponse;
    }

    public function updateObserverDataForActionId(string $actionId, array $observerData)
    {
        $documents = $this->getDocumentsFromEs($actionId);

        if (empty($documents) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND);
        }

        $document = current($documents);

        $documentId = $document['_id'];

        $esResponse = $this->esDao->updateObserverDataInEs(
            strtolower($this->baseIndex), self::ES_TYPE, $documentId, $observerData);

        return $esResponse;
    }

    public function updateDiffForActionId(string $actionId, array $diff)
    {
        $documents = $this->getDocumentsFromEs($actionId);

        if (empty($documents) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND);
        }

        $document = current($documents);

        $documentId = $document['_id'];

        $esResponse = $this->esDao->updateDiffForActionId(
            strtolower($this->baseIndex), self::ES_TYPE, $documentId, $diff);

        return $esResponse;
    }

    public function createAllRelationsDiff(
        &$diff,
        $originalDataArray,
        $dirtyDataArray,
        $mainEntity,
        $relations)
    {
        if (E::isValidEntity($mainEntity) === false)
        {
            return;
        }

        $entityOb = ConstantsEntity::getEntityObject($mainEntity);

        if (empty($relations) === false)
        {
            foreach ($relations as $relation)
            {
                // We want to show empty values for relation as it means we
                // want to reset the m2m fields.
                if (isset($dirtyDataArray[$relation]) === true)
                {
                    $relatedEntityName = $entityOb->$relation()->getModel()->getEntityName();

                    $oldRelationIds = $originalDataArray[$relation] ?? [];
                    $newRelationIds = $dirtyDataArray[$relation] ?? [];

                    if ((empty($oldRelationIds) === true) and
                        (empty($newRelationIds) === true))
                    {
                        continue;
                    }

                    $relationDiff = $this->createRelationDiff(
                        $oldRelationIds,
                        $newRelationIds,
                        $relatedEntityName);

                    $diff['old'][$relation] = $relationDiff['old'];

                    $diff['new'][$relation] = $relationDiff['new'];
                }
            }
        }
    }

    protected function createRelationDiff(
        $oldIds,
        $newIds,
        $relatedEntityName)
    {
        $relatedEntityOb = ConstantsEntity::getEntityObject($relatedEntityName);

        $relatedEntityOb::verifyIdAndSilentlyStripSignMultiple($oldIds);
        $relatedEntityOb::verifyIdAndSilentlyStripSignMultiple($newIds);

        $removedEntities = array_diff($oldIds, $newIds);

        $addedEntities = array_diff($newIds, $oldIds);

        $oldRelatedEntities = $this->repo
                                   ->$relatedEntityName
                                   ->findMany($removedEntities)
                                   ->toArrayDiff();

        $newRelatedEntities = $this->repo
                                   ->$relatedEntityName
                                   ->findMany($addedEntities)
                                   ->toArrayDiff();

        $diff = [
            'old' => $oldRelatedEntities,
            'new' => $newRelatedEntities,
        ];

        return $diff;
    }
}
