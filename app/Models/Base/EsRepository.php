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

    protected $defaultFieldMapping = [
        'type'            => 'text',
        'analyzer'        => 'edge_ngram_analyzer',
        'search_analyzer' => 'standard',
        'index_options'   => 'offsets',
    ];

    //
    // Fields indexed in es and their mappings
    //
    protected $fields         = [];
    protected $fieldsMappings = [];

    public function __construct()
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        // TODO: Fix this, how to get app mode?
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
                        'notes' => [
                            'match' => '*',
                            'match_mapping_type' => 'string',
                            'mapping' => $this->getDefaultFieldMapping(),
                        ],
                    ],
                ],
            ],
        ];

        $this->esDao->createIndexIfNotExistsSane($this->indexName, $settings, $mappings);

        return $this;
    }

    public function search(array $params, string $merchantId = null)
    {
        //
        // Sets index name: Index name is current entity (eg. invoice|merchant)
        //

        $class = $this->getEntityClass();
        $model = new $class;

        $this->setIndexName($this->mode . '_' . $model->getEntity());

        $clauses = []; // Boolean query clauses
        $filters = []; // Filters
        $from    = ($params['skip']) ?? 0;
        $size    = ($params['count']) ?? 10;

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

        $hits = $searchResult['hits']['hits'];

        $ids = collect($hits)->pluck('_id')->all();

        // sd($ids);
        // sd(json_encode($searchParams['body']));

        if (count($ids) === 0)
        {
            return (new PublicCollection);
        }

        $entities = $this->newQuery()->findOrFailPublic($ids, array('*'));

        return $entities;
    }

    public function buildSearchQuery(
        array   $params,
        string  $merchantId,
        array & $clauses,
        array & $filters)
    {
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

        if (empty($params['q']) === false)
        {
            $clauses[] = [
                'multi_match' => [
                    'query'  => $params['q'],
                    'type'   => 'best_fields',
                    'fields' => $this->getFields(),
                    'boost'  => 1,
                ],
            ];

            unset($params['q']);
        }

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

    public function bulkUpdate(array $documents)
    {
        //
        // Builds es payload and makes bulk upsert request to es
        //

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

    public function fireSync($job, $data)
    {
        $id     = $data['id'];
        $mode   = $data['mode'];
        $action = $data['action'];

        $class = $this->getEntityClass();

        $model = new $class;

        $this->setIndexName($mode . '_' . $model->getEntity());

        switch ($action) {
            case 'upsert':

                $document = $model->find($id, $this->getFields())->toArray();

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
