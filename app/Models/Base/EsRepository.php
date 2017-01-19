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
    const JOB_RELEASE_WAIT = 120; // In seconds

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

        // TODO: Fix this!
        $this->mode = 'test';

        $this->trace = $app['trace'];

        $this->indexName = $app['config']->get('database.es_index');

        $this->setFieldMappings();

        $this->esDao = new EsDao();
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

    public function setFieldMappings() {}

    public function updateQuery(& $query) {}

    public function search(string $entity, array $params, string $merchantId = null)
    {
        $this->setIndexName($this->mode . '_' . $entity);

        $clauses = [];                       // Boolean query clauses
        $filters = [];                       // Filters

        $from    = ($params['skip']) ?? 0;
        unset($params['skip']);

        $size    = ($params['count']) ?? 10;
        unset($params['count']);

        $this->buildSearchQuery($params, $merchantId, $clauses, $filters);

        $query = [
            'bool' => [
                'must'   => $clauses,
                'filter' => $filters
            ],
        ];

        $searchParams = [
            'index' => $this->indexName,
            'type'  => $this->indexName,
            'body'  => [
                '_source' => false,
                'from'    => $from,
                'size'    => $size,
                'query'   => $query,
                'highlight' => [
                    'fields' => [
                        '*' => new \stdClass,
                    ],
                ],
            ],
        ];

        $searchResult = $this->esDao->search($searchParams);

        //
        // TODO:
        // What to do about highlights, Need to discuss in what format those will be
        // returned in the api respose.
        //

        $hits = $searchResult['hits']['hits'];

        $ids = collect($hits)->pluck('_id')->all();

        if (count($ids) === 0)
        {
            return (new PublicCollection);
        }

        $entities = $this->newQuery()->findMany($ids, array('*'));

        //
        // We throw and error if there is mismatch between es & mysql count
        // TODO: Should we not do that? Just log and error but return whatever
        //       results found in mysql?
        //
        if (count($ids) !== $entities->count())
        {
            throw new Exception\ServerErrorException(
                'Did not find corresponding entity data in MySQL',
                ErrorCode::SERVER_ERROR_MYSQL_ENTRY_NOT_FOUND,
                [
                    'ids' => $ids,
                ]);
        }

        return $entities;
    }

    /**
     * Builds search clauses(should) and filters for forming the query.
     *
     * @param array  $params
     * @param string $merchantId
     * @param array  $clauses
     * @param array  $filters
     *
     * @return null
     */
    public function buildSearchQuery(
        array   $params = [],
        string  $merchantId = null,
        array & $clauses,
        array & $filters)
    {
        $params = array_dot($params);

        //
        // If merchand id is available, add it to filters
        //
        if ($merchantId !== null)
        {
            $filters[] = [
                'term' => [
                    'merchant_id' => [
                        'value' => $merchantId,
                    ],
                ],
            ];
        }

        //
        // If 'q' is set, form a multi_match clause on search fields
        //
        if (empty($params['q']) === false)
        {
            $clauses[] = [
                'multi_match' => [
                    'query'  => $params['q'],
                    'type'   => 'best_fields',
                    'fields' => $this->queryFields,
                    'boost'  => 1,
                ],
            ];

            unset($params['q']);
        }

        //
        // If notes is set, form a multi_match clause on notes.* fields
        //
        if (empty($params['notes']) === false)
        {
            $clauses[] = [
                'multi_match' => [
                    'query'  => $params['notes'],
                    'type'   => 'best_fields',
                    'fields' => 'notes.*',
                    'boost'  => 2,
                ],
            ];

            unset($params['notes']);
        }

        //
        // For all other params add a term clause
        //
        foreach ($params as $key => $value)
        {
            $clauses[] = [
                'term' => [
                    $key => [
                        'value' => $value,
                        'boost' => 3,
                    ],
                ]
            ];
        }
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

        $collection = $query->skip($skip)
                            ->take($take)
                            ->get();

        $serialized = $collection->toArray();

        $mapper = function($v)
                  {
                      $projected = array_only(array_dot($v), $this->fields);

                      return $projected;
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
