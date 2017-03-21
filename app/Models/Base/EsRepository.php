<?php

namespace RZP\Models\Base;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;

class EsRepository extends \Razorpay\Spine\Repository
{
    use EsQuery;
    use EsHydrator;

    protected $esDao;
    protected $indexName;
    protected $trace;

    protected static $table;

    const MAX_JOB_ATTEMPTS = 10;
    const JOB_RELEASE_WAIT = 120;

    //
    // Different actions on es document
    //
    const UPSERT           = 'upsert';
    const DELETE           = 'delete';

    //
    // Some common query params which searching for es
    //
    const QUERY            = 'q';
    const SEARCH_HITS      = 'search_hits';

    //
    // Fields indexed in es and their mappings.
    // These fields will be queried to db and will be indexed.
    //
    protected $fields         = [];
    protected $fieldMappings  = [];

    protected $queryFields    = [];

    protected $mode;

    public function __construct()
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->mode = $app['rzp.mode'] ?? Mode::LIVE;

        $this->trace = $app['trace'];

        $this->indexName = $app['config']->get('database.es_index');

        $this->setFieldMappings();

        $this->esDao = new EsDao();
    }

    /**
     * Sets field mappings for es index
     */
    protected function setFieldMappings() {}

    /**
     * Updates the default query for fetch of models for indexing.
     * Eg. In case of merchant, it needs join with merchant_detail, etc.
     *
     * @param object $query
     *
     * @return null
     */
    protected function updateQuery(& $query) {}

    /**
     * Serializes a given model for indexing
     * Please override this per need to avoid unnecessary MySQL queries.
     *
     * @param Base\PublicEntity $entity
     *
     * @return array
     */
    protected function serialize(Base\PublicEntity $entity)
    {
        return $entity->setVisible($this->fields)->toArray();
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns list of fields (possible) that can appear in fetch query params.
     *
     * This is generally controlled in fetch rules vars in Repo class of entity,
     * but this is here to be consumed in RepositoryFetch->isEsFetch method.
     *
     * @return array
     */
    public function getPossibleFieldsInParam()
    {
        return array_merge($this->fields, [self::QUERY, self::SEARCH_HITS]);
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
            $this->trace->info(TraceCode::ES_SAVE_REQUEST, $data);

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

    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;

        $this->esDao->setIndexNameByValue($this->indexName);

        return $this;
    }

    public function createIndexIfNotExists()
    {
        $settings = EsMapping::$indexSettings;

        $mappings = EsMapping::mappings($this->fields, $this->fieldMappings);

        $this->esDao->createIndexIfNotExistsInDefaultHost($this->indexName, $settings, $mappings);
    }

    /**
     * Makes search in ES on this model with given params.
     *
     * @param string      $entity
     * @param array       $params
     * @param string|null $merchantId
     * @param array       $groups
     *
     * @return PublicCollection
     */
    public function buildQueryAndSearch(
        string $entity,
        array $params,
        string $merchantId = null,
        array $groups = [])
    {
        $this->setIndexName($this->mode . '_' . $entity);

        $this->addMerchantIdInEsParamsIfSet($params, $merchantId);

        $this->buildQuery($this->indexName, $this->indexName, [], $params);

        return $this->search();
    }

    public function addMerchantIdInEsParamsIfSet(array & $params, string $merchantId = null)
    {
        if ($merchantId !== null)
        {
            $params['merchant_id'] = $merchantId;
        }
    }

    /**
     * Actually does the search, once query is built.
     *
     * @return PublicCollection
     */
    public function search()
    {
        //
        // Gets request params and calls search method of EsDao's class
        //
        $requestParams = $this->getEsRequestParams();

        $response = $this->esDao->search($requestParams);

        //
        // Plucks the source fields if set, else ids and forms an uniform array
        //
        $hits = array_map(
            function ($res)
            {
                return $res['_source'] ?? ['id' => $res['_id']];
            },
            $response['hits']['hits']);

        //
        // Returns empty collection if no hits
        //
        if (count($hits) === 0)
        {
            return new PublicCollection;
        }

        //
        // If only es search hits expected then hydrates the array into
        // PublicCollection and returns.
        //
        if ($this->searchHitsOnly === true)
        {
            return $this->hydrate($hits);
        }

        //
        // Else, gets the ids and queries db and returns the PublicCollection
        //
        $ids = collect($hits)->pluck('id')->all();

        $entities = $this->newQuery()->findMany($ids, array('*'));

        //
        // Raises and alert if there are entities in ES which are not in MySQL
        //
        if (count($ids) !== $entities->count())
        {
            $this->trace->error(TraceCode::ES_MYSQL_RESULTS_MISMATCH, ['ids' => $ids]);
        }

        return $entities;
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

        return $this->esDao->bulkUpdate($params);
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

        return $this->serialize($entity);
    }

    public function fetchForIndex(int $skip = 0, int $take = 100)
    {
        $query = $this->newQuery();

        $this->updateQuery($query);

        $collection = $query->skip($skip)->take($take)->get();

        return array_map(
            function ($v)
            {
                return $this->serialize($v);
            },
            $collection->all());
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
        $tracePayload = [
            'job_attempts' => $job->attempts(),
            'class'        => get_class($this),
            'data'         => $data,
        ];

        $this->trace->debug(TraceCode::ES_SAVE_REQUEST, $tracePayload);

        $id     = $data['id'];
        $mode   = $data['mode'];
        $action = $data['action'];

        \Database\DefaultConnection::set($mode);

        $class = $this->getEntityClass();

        $model = new $class;

        $this->setIndexName($mode . '_' . $model->getEntity());

        $this->createIndexIfNotExists();

        try
        {
            $this->sync($id, $action);

            $job->delete();
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e, Trace::ERROR, TraceCode::ES_SAVE_FAILED, $tracePayload);

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
            case self::UPSERT:

                $document = $this->findForIndex($id);

                $this->bulkUpdate([$document]);

                break;

            case self::DELETE:

                $this->deleteDocument($id);

                break;

            default:

                break;
        }
    }
}
