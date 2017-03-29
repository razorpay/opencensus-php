<?php

namespace RZP\Models\Base\Es;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;

class Repository extends \Razorpay\Spine\Repository
{
    use Base\Traits\Es\QueryBuilder;

    const MAX_JOB_ATTEMPTS = 10;
    const JOB_RELEASE_WAIT = 120;
    // Different actions on es document
    const UPSERT           = 'upsert';
    const DELETE           = 'delete';
    // Some common query params which searching for es
    const QUERY            = 'q';
    const SEARCH_HITS      = 'search_hits';

    // @deprecated - Will not be required later and will be removed.
    protected static $table;

    protected $esDao;
    protected $trace;
    // Name of the index to which this repo might correspond to.
    protected $indexName = null;
    // Fields indexed in es and their mappings.
    protected $fields         = [];
    protected $fieldMappings  = [];
    protected $queryFields    = [];
    // Mode of the application
    protected $mode;

    /**
     * Constructor
     *
     * @param string|null $indexName
     *
     * @return
     */
    public function __construct(string $indexName = null)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];

        $this->setFieldMappings();

        $this->esDao = new Base\EsDao;

        // If index name is set as part of constructor arg, assign it to class
        // member and also set the same for es dao object.
        if ($indexName !== null)
        {
            $this->indexName = $this->mode . '_' . $indexName;

            $this->esDao->setIndexNameByValue($this->indexName);
        }
    }

    /**
     * Sets field mappings for the es type.
     *
     * This method to be overridden in the entity's EsRepository which should
     * set $fieldMappings var.
     */
    protected function setFieldMappings() {}

    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns list of fields (possible) that can appear in fetch query params.
     *
     * Used in RepositoryFetch->getMysqlAndEsParams, please refer.
     *
     * @return array
     */
    public function getPossibleFieldsInParam(): array
    {
        return array_merge($this->fields, [self::QUERY, self::SEARCH_HITS]);
    }

    // DEPRECATED METHODS STARTS ----------------------------------------------
    // TODO: Needs to be cleaned once old entities are migrated to new generic flow.

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

        $entities = new Base\PublicCollection;

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
            $this->trace->info(TraceCode::ES_SAVE_REQUEST, $data);

            // Creating a new EsDao object because,
            // in the queue flow, the mode needs to be passed
            // to the constructor.
            $esDao = new Base\EsDao($mode);
            // Calls the entity es repository
            $this->storeEntity($esType, $entityArray, $esDao);

            $job->delete();
        }
        catch (\Exception $ex)
        {
            $data['job_attempts'] = $job->attempts();

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ES_SAVE_FAILED,
                [
                    $data
                ]
            );

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

    // DEPRECATED METHODS ENDS ------------------------------------------------

    /**
     * Creates index corresponding to this repo if it not exists already.
     *
     * @return
     */
    public function createIndexIfNotExists()
    {
        $settings = Mapping::$indexSettings;

        $mappings = Mapping::mappings($this->fields, $this->fieldMappings);

        $this->esDao->createIndexIfNotExistsInDefaultHost($this->indexName, $settings, $mappings);
    }

    /**
     * Makes search in ES on this model with given params.
     *
     * @param array       $params
     * @param string|null $merchantId
     * @param array       $groups
     *
     * @return array
     */
    public function buildQueryAndSearch(
        array $params,
        string $merchantId = null,
        array $groups = []): array
    {
        $this->addMerchantIdInEsParamsIfSet($params, $merchantId);

        $esRequestParams = $this->buildQueryAndGetEsRequestParams($params);

        $response = $this->esDao->search($esRequestParams);

        // Plucks the source fields if set, else ids and forms an uniform array
        // to be returned to callee.
        return array_map(
                    function ($res)
                    {
                        return $res['_source'] ?? ['id' => $res['_id']];
                    },
                    $response['hits']['hits']);
    }

    /**
     * Adds merchant_id in params for es to consider the same while forming query.
     *
     * @param array       $params
     * @param string|null $merchantId
     *
     * @return
     */
    public function addMerchantIdInEsParamsIfSet(array & $params, string $merchantId = null)
    {
        if ($merchantId !== null)
        {
            $params['merchant_id'] = $merchantId;
        }
    }

    /**
     * Builds es query using the params and methods defined in QueryBuilder
     *
     * @param array $params
     *
     * @return array
     */
    public function buildQueryAndGetEsRequestParams(array $params): array
    {
        // Extracts from, size and source value from params and unset them.
        $from   = ($params['skip']) ?? 0;
        $size   = ($params['count']) ?? 10;
        $source = boolval(($params['search_hits']) ?? false);

        unset($params['skip']);
        unset($params['count']);
        unset($params['search_hits']);

        // Initializes query to empty array, which follows formation of the same
        // using methods defined in QueryBuilder.
        $query = [];

        foreach ($params as $field => $value)
        {
            $f = 'buildQueryFor' . studly_case($field);

            if (method_exists($this, $f))
            {
                $this->$f($query, $value);
            }
            else
            {
                $this->buildQueryForFieldDefaultImpl($query, $field, $value);
            }
        }

        return [
            'index' => $this->indexName,
            'type'  => $this->indexName,
            'body'  => [
                '_source' => $source,
                'from'    => $from,
                'size'    => $size,
                'query'   => $query,
            ],
        ];
    }

    /**
     * Builds es payload and makes bulk upsert request to es.
     *
     * @param array $documents
     *
     * @return array
     */
    public function bulkUpdate(array $documents): array
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

        return $this->esDao->bulkUpdate($params);
    }

    /**
     * Deletes document with given id from index.
     *
     * @param  string $id
     *
     * @return
     */
    public function deleteDocument(string $id)
    {
        $params = [
            'index' => $this->indexName,
            'type'  => $this->indexName,
            'id'    => $id,
        ];

        $this->esDao->delete($params);
    }
}
