<?php

namespace RZP\Models\Base;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class EsRepository extends \Razorpay\Spine\Repository
{
    protected $esDao;
    protected $indexName;
    protected $trace;

    protected static $table;

    const MAX_JOB_ATTEMPTS = 10;
    // This is in seconds
    const JOB_RELEASE_WAIT = 120;
    const QUERY            = 'q';

    /**
     * Fields indexed in es and their mappings.
     * These fields will be queried to db and will be indexed.
     */
    protected $fields         = [];
    protected $fieldMappings  = [];

    protected $queryFields    = [];

    public function __construct()
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        /**
         * TODO: Fix this!
         */
        $this->mode = 'test';

        $this->trace = $app['trace'];

        $this->indexName = $app['config']->get('database.es_index');

        $this->setFieldMappings();

        $this->esDao = new EsDao();
    }

    protected function setFieldMappings() {}
    protected function updateQuery(& $query) {}

    public function getFields()
    {
        return $this->fields;
    }

    public function fetch($params, $merchantId)
    {
        $entities = new Base\PublicCollection;

        if (isset($params['notes']))
        {
            $entities = $this->fetchNotes(static::$table, $params, $merchantId);
        }

        return $entities;
    }

    public function fetchNotes($typeName, $params, $merchantId)
    {
        $params['merchant_id'] = $merchantId;

        $entities = new PublicCollection;

        // Returns all the entity IDs matching the notes search.
        $entityIds = $this->esDao->getNotes($typeName, $params);

       if (empty($entityIds) === false)
        {
            // Get the entity data from MySQL.
            $entities = $this->newQuery()->findOrFailPublic($entityIds, array('*'));

            // MySQL should contain all entities present in ES.
            if ($entities->count() !== count($entityIds))
            {
                throw new Exception\ServerErrorException(
                    'Did not find corresponding entity data in MySQL' ,
                    ErrorCode::SERVER_ERROR_MYSQL_ENTRY_NOT_FOUND,
                    ['es_entity_ids' => $entityIds]);
            }
        }

        return $entities;
    }

    // Currently storing only notes and merchant ID.
    public function storeEntity($typeName, $entityArray, $esDao = null)
    {
        $params['notes'] = $entityArray['notes'];
        $params['merchant_id'] = $entityArray['merchant_id'];
        $params['entity_id'] = $entityArray['id'];

        if ($esDao === null)
        {
            $esDao = $this->esDao;
        }

        $esDao->storeNotes($typeName, $params);
    }

    // Called through queue
    // Called through the entity repository
    public function fireStoreEntity($job, $data)
    {
        $esType = $data['es_type'];
        $entityArray = $data['entity'];
        $mode = $data['mode'];

        try
        {
            $this->trace->info(
                TraceCode::ES_SAVE_REQUEST,
                $data);

            // Creating a new EsDao object because,
            // in the queue flow, the mode needs to be passed
            // to the constructor.
            $esDao = new EsDao($mode);
            // Calls the entity es repository
            $this->storeEntity($esType, $entityArray, $esDao);

            $job->delete();
        }
        catch (\Exception $ex)
        {
            $data['job_attempts'] = $job->attempts();

            $this->trace->error(
                TraceCode::ES_SAVE_FAILED,
                [
                    $data
                ]
            );

            $this->trace->traceException($ex);

            if ($job->attempts() > self::MAX_JOB_ATTEMPTS)
            {
                $job->delete();
            }
            else
            {
                $job->release(self::JOB_RELEASE_WAIT);
            }
        }
    }

    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;

        $this->esDao->setIndexNameByValue($this->indexName);

        return $this;
    }

    public function createIndexIfNotExists()
    {
        $settings = EsMappping::$defaultIndexSettings;

        $mappings = EsMappping::mappings($this->fields, $this->fieldMappings);

        $this->esDao->createIndexIfNotExistsSane($this->indexName, $settings, $mappings);
    }

    public function search(string $entity, array $params, string $merchantId = null, array $groups = [])
    {
        $this->setIndexName($this->mode . '_' . $entity);

        $query = [];

        list($from, $size) = $this->getFromAndSizeValue($params);

        $this->buildQuery($query, $params);

        $this->addAclFilters($query, $merchantId, $groups);

        $searchParams = [
            'index' => $this->indexName,
            'type'  => $this->indexName,
            'body'  => [
                '_source' => false,
                'from'    => $from,
                'size'    => $size,
                'query'   => $query,
            ],
        ];

        $searchResult = $this->esDao->search($searchParams);

        $ids = collect($searchResult['hits']['hits'])
                    ->pluck('_id')
                    ->all();

        if (count($ids) === 0)
        {
            return (new PublicCollection);
        }

        $entities = $this->newQuery()->findMany($ids, array('*'));

        if (count($ids) !== $entities->count())
        {
            $this->trace->error(
                TraceCode::ES_MYSQL_RESULTS_MISMATCH,
                [
                    'ids' => $ids,
                ]
            );
        }

        return $entities;
    }

    protected function getFromAndSizeValue(array & $params)
    {
        $from = ($params['skip']) ?? 0;
        $size = ($params['count']) ?? 10;

        unset($params['skip']);
        unset($params['count']);

        return [$from, $size];
    }

    protected function buildQuery(array & $query, array $params)
    {
        $mustClauses = [];

        foreach ($params as $field => $value)
        {
            $func = 'addMustClauseFor' . studly_case($field);

            if (method_exists($this, $func))
            {
                $this->$func($mustClauses, $value);
            }
            else
            {
                $this->addMustClauseDefault($mustClauses, $field, $value);
            }
        }

        $query = [
            'bool' => [
                'must'   => $mustClauses,
            ],
        ];
    }

    protected function addAclFilters(array & $query, $merchantId, array $groups)
    {
        $filterMustClauses = [];

        if ($merchantId !== null)
        {
            $filterMustClauses[] = [
                'term' => [
                    'merchant_id' => [
                        'value' => $merchantId,
                    ],
                ],
            ];
        }

        $query['bool']['filter'] = [
            'bool' => [
                'must' => $filterMustClauses,
            ],
        ];
    }

    protected function addMustClauseDefault(array & $mustClauses, string $field, $value)
    {
        $mustClauses[] = [
            'term' => [
                $field => [
                    'value' => $value,
                    'boost' => 3,
                ],
            ],
        ];
    }

    protected function addMustClauseForQ(array & $mustClauses, $value)
    {
        $mustClauses[] = [
            'multi_match' => [
                'query'  => $value,
                'type'   => 'best_fields',
                'fields' => $this->queryFields,
                'boost'  => 1,
            ],
        ];
    }

    protected function addMustClauseForNotes(array & $mustClauses, $value)
    {
        $mustClauses[] = [
            'multi_match' => [
                'query'  => $value,
                'type'   => 'best_fields',
                'fields' => 'notes.*',
                'boost'  => 2,
            ],
        ];
    }

    /**
     * Builds es payload and makes bulk upsert request to es.
     *
     * @param array $documents
     *
     * @return null
     */
    public function bulkUpdate(array $documents)
    {
        $params = [];

        foreach($documents as $document)
        {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_type'  => $this->indexName,
                    '_id'    => $document['id'],
                ]
            ];

            $params['body'][] = $document;
        }

        $this->esDao->bulkUpdate($params);
    }

    public function deleteDocument(string $id)
    {
        $params = [
            'index' => $this->indexName,
            'type'  => $this->indexName,
            'id'    => $id,
        ];

        $this->esDao->delete($params);
    }

    public function findForIndex(string $id)
    {
        $query = $this->newQuery();

        $this->updateQuery($query);

        $entity = $query->find($id);

        $serialized = array_dot($entity->toArray());

        return array_only($serialized, $this->fields);
    }

    public function fetchForIndex(int $skip = 0, int $take = 100)
    {
        $query = $this->newQuery();

        $this->updateQuery($query);

        $collection = $query->skip($skip)->take($take)->get();

        $serialized = $collection->toArray();

        $mapper = function($v)
                  {
                      return array_only(array_dot($v), $this->fields);
                  };

        return array_map($mapper, $serialized);
    }

    /**
     * Called through queue from Base/Repository's saveOrFail and deleteOfFail.
     *
     * @param mixed $job
     * @param array $data
     *
     * @return null
     */
    public function fireSync($job, $data)
    {
        $id     = $data['id'];
        $mode   = $data['mode'];
        $action = $data['action'];

        $class = $this->getEntityClass();

        $model = new $class;

        $this->setIndexName($mode . '_' . $model->getEntity());

        $this->createIndexIfNotExists();

        try
        {
            $this->sync($id, $action);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, null, [$data]);

            if ($job->attempts() > self::MAX_JOB_ATTEMPTS)
            {
                $job->delete();
            }
            else
            {
                $job->release(self::JOB_RELEASE_WAIT);
            }
        }
    }

    protected function sync(string $id, string $action)
    {
        switch ($action) {
            case 'upsert':

                $document = $this->findForIndex($id);

                $this->bulkUpdate([$document]);

                break;

            case 'delete':

                $this->deleteDocument($id);

                break;

            default:

                break;
        }
    }
}
