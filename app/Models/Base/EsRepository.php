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

    //
    // Default mapping for most of the es fields
    //
    protected $defaultFieldMapping = [
        'type'            => 'text',
        'analyzer'        => 'edge_ngram_analyzer',
        'search_analyzer' => 'standard',
        'index_options'   => 'offsets',
    ];

    //
    // Fields indexed in es and their mappings.
    // These fields will be queried to db and will be indexed.
    //
    protected $fields         = [];
    protected $fieldsMappings = [];

    //
    // TODO: Need to do something about this hack.
    //
    protected $hideFields     = [];

    //
    // These fields will be searched on 'q' (the query string).
    // These can be different from all the fields(above) indexed.
    //
    protected $searchFields   = [];

    public function __construct()
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        //
        // TODO: Fix this, how to get app mode?
        //
        $this->mode = 'test';

        $this->trace = $app['trace'];

        $this->indexName = $app['config']->get('database.es_index');

        $this->esDao = new EsDao();
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getFieldsMappings()
    {
        return $this->fieldsMappings;
    }

    public function getSearchFields()
    {
        return $this->searchFields;
    }

    public function getDefaultFieldMapping()
    {
        return $this->defaultFieldMapping;
    }

    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;

        $this->esDao->setIndexNameByValue($this->indexName);

        return $this;
    }

    public function createIndexIfNotExists()
    {
        $settings = [
            'analysis' => [
                'analyzer' => [
                    'edge_ngram_analyzer' => [
                        'tokenizer' => 'edge_ngram_tokenizer',
                        'filter'    => ['lowercase_filter'],
                    ],
                ],
                'tokenizer' => [
                    'edge_ngram_tokenizer' => [
                        'type'        => 'edge_ngram',
                        'min_gram'    => 2,
                        'max_gram'    => 50,
                        'token_chars' => [
                            'letter',
                            'digit',
                        ],
                    ],
                ],
                'filter' => [
                    'lowercase_filter' => [
                        'type' => 'lowercase',
                    ],
                ],
            ]
        ];

        $mappings = [
            '_default_' => [
                'properties' => $this->getFieldsMappings(),
                'dynamic_templates' => [
                    [
                        'default' => [
                            'match' => '*',
                            'match_mapping_type' => 'string',
                            'mapping' => $this->getDefaultFieldMapping(),
                        ],
                    ],
                ],
            ],
        ];

        $this->esDao->createIndexIfNotExistsSane($this->indexName, $settings, $mappings);
    }

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
                'should'               => $clauses,
                'minimum_should_match' => 1,
                'filter'               => $filters
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
                    'fields' => $this->getSearchFields(),
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

    /**
     * Fetches data from mysql for indexing the entity.
     * If list of ids given then usage that to run findMany else will find all
     * with given skip and take param.
     *
     * @param array|null  $ids
     * @param int|integer $skip
     * @param int|integer $take
     *
     * @return PublicCollection
     */
    public function fetchForIndex(array $ids = null, int $skip = 0, int $take = 100)
    {
        $query = $this->newQuery();

        $fields = $this->getFields();

        if ($ids !== null)
        {
            return $query->findMany($ids, $fields)
                         ->makeHidden($this->hideFields);
        }

        return $query->skip($skip)
                     ->take($take)
                     ->select($fields)
                     ->get()
                     ->makeHidden($this->hideFields);
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

                $documents = $this->fetchForIndex([$id])->toArray();

                $this->bulkUpdate($documents);

                break;

            case 'delete':

                $this->deleteDocument($id);

                break;

            default:

                break;
        }
    }
}
