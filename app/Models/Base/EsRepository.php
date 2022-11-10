<?php

namespace RZP\Models\Base;

use App;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;

class EsRepository extends \Razorpay\Spine\Repository
{
    use Base\Traits\Es\QueryBuilder
    {
        getSortParameter as public getDefaultSortParameter;
        getFromAndToQueryAttribute as public getDefaultFromAndToQueryAttribute;
    }

    // Different actions on ES document
    const CREATE           = 'create';
    const UPDATE           = 'update';
    const DELETE           = 'delete';

    // Some common query params while searching in ES
    const SKIP             = 'skip';
    const COUNT            = 'count';
    const FROM             = 'from';
    const TO               = 'to';

    /**
     * A fetch param which holds the query string which gets searched in ES.
     */
    const QUERY            = 'q';

    /**
     * A param which specifies whether only ES payload can be returned
     * (auto-complete use case) or full model serialization by MySQL db call is required.
     */
    const SEARCH_HITS      = 'search_hits';

    protected $esDao;
    protected $trace;
    protected $mode;
    protected $entity;

    /**
     * Name of the index to which this repo might correspond to.
     *
     * @var null|string
     */
    protected $indexName = null;

    /**
     * Name of index's only type
     *
     * @var null|string
     */
    protected $typeName = null;

    /**
     * Fields indexed in ES
     *
     * @var array
     */
    protected $indexedFields  = [];

    /**
     * Fields which will be used to search against 'q' parameter.
     *
     * @var array
     */
    protected $queryFields    = [];

    /**
     * List of fields which are only query-able from ES.
     *
     * @var array
     */
    protected $esFetchParams  = [];

    /**
     * List of fields which can be queried from MySQL as well.
     * And are in ES mostly for assisting with combined queries.
     *
     * @var array
     */
    protected $commonFetchParams = [];

    /**
     * Constructor
     *
     * @param string $entity
     */
    public function __construct(string $entity)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->setMode($app);

        $this->entity = $entity;

        $this->trace = $app['trace'];

        $indexPrefix = $app['config']->get('database.es_entity_index_prefix');
        $typePrefix  = $app['config']->get('database.es_entity_type_prefix');

        $this->setIndexAndTypeName($indexPrefix, $typePrefix);

        $this->initEsDao();
    }

    /**
     * Initializes Elasticserach Dao which usually is just below implementation but in specific case e.g.
     * in NodalStatment's EsRepository class it is different.
     */
    protected function initEsDao()
    {
        $this->esDao = new Base\EsDao;
    }

    /**
     * Sets default index and type name for es
     * Format: <prefix_><entity>_<mode>
     *
     * @param string $indexPrefix
     * @param string $typePrefix
     */
    public function setIndexAndTypeName(string $indexPrefix, string $typePrefix)
    {
        $suffix = $this->getIndexSuffix();

        $this->indexName = $indexPrefix . $suffix;
        $this->typeName  = $typePrefix . $suffix;
    }

    /**
     * Sets index name to a new value with new prefix.
     *
     * Called from indexing command where in case of re-indexing we might choose
     * to use new index name (via new prefix).
     *
     * @param string $indexPrefix
     */
    public function setIndexName(string $indexPrefix)
    {
        $this->indexName = $indexPrefix . $this->getIndexSuffix();
    }

    /**
     * Returns suffix part of index name.
     * Format: <entity>_<mode>
     *
     * @return string
     */
    public function getIndexSuffix(): string
    {
        return "{$this->entity}_{$this->mode}";
    }

    public function getIndexedFields(): array
    {
        return $this->indexedFields;
    }

    public function getCommonFetchParams(): array
    {
        return $this->commonFetchParams;
    }

    public function getEsFetchParams(): array
    {
        return $this->esFetchParams;
    }

    public function redactSensitiveInformation($array, $sensitiveKeys)
    {
        foreach ($array as $key => $item)
        {
            if (in_array($key, $sensitiveKeys, true) === true)
            {
                $array[$key] = '***';

                return $array;
            }
            elseif (is_array($item))
            {
                $redactedArray = $this->redactSensitiveInformation($item, $sensitiveKeys);

                $array[$key] = $redactedArray;
            }
        }

        return $array;
    }

    /**
     * @param array       $params
     * @param string|null $merchantId
     *
     * @return array
     */
    public function buildQueryAndSearch(
        array $params,
        string $merchantId = null): array
    {
        $this->addMerchantIdInEsParamsIfSet($params, $merchantId);

        $esRequestParams = $this->buildQueryAndGetEsRequestParams($params);

        $sensitiveKeys = ['email', 'contact', 'customer_email', 'customer_contact'];

        $this->trace->info(TraceCode::ES_REQUEST_PARAMS, $this->redactSensitiveInformation($esRequestParams, $sensitiveKeys));

        return $this->esDao->search($esRequestParams);
    }

    /**
     * Yields Es search results until exhausted. Usage ES scroll endpoint.
     *
     * @param array       $params
     * @param string|null $merchantId
     *
     * @return Generator
     */
    public function buildQuerySearchAndScroll(
        array $params,
        string $merchantId = null): \Generator
    {
        $this->addMerchantIdInEsParamsIfSet($params, $merchantId);

        $esRequestParams = $this->buildQueryAndGetEsRequestParams($params);

        return $this->esDao->searchAndScroll($esRequestParams);
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
        $query = [];

        list($from, $size, $source) = $this->extractQueryMetaFromParams($params);

        $this->buildQueryForFromAndToIfApplies($query, $params);

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

        $this->buildQueryAdditional($query, $params);

        // If $query is [], this is considered as match all query.
        $query = $query ?: ['match_all' => new \stdClass];

        $sort = $this->getSortParameter();

        return [
            'index' => $this->indexName,
            'type'  => $this->typeName,
            'body'  => [
                '_source' => $source,
                'from'    => $from,
                'size'    => $size,
                'query'   => $query,
                'sort'    => $sort,
            ],
        ];
    }

    public function buildQueryAdditional(array & $query, array $params)
    {
    }

    public function getSortParameter(): array
    {
        return $this->getDefaultSortParameter();
    }

    /**
     * Returns the attribute on which range epoch parameters (i.e. from and to) are applied,
     * defaults to created_at.
     *
     * @return string
     */
    public function getFromAndToQueryAttribute(): string
    {
        return $this->getDefaultFromAndToQueryAttribute();
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

        $documents = array_values(array_filter($documents));

        if (empty($documents) === true)
        {
            return [];
        }

        foreach($documents as $document)
        {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_type'  => $this->typeName,
                    '_id'    => $document['id'],
                ]
            ];

            $params['body'][] = $document;
        }

        $res = $this->esDao->bulkUpdate($params);

        $this->checkForBulkUpdateOperationErrors($params, $res);

        return $res;
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
            'type'  => $this->typeName,
            'id'    => $id,
        ];

        $this->esDao->delete($params);
    }

    /**
     * @param string $id
     * @param string $index
     * @param string $onboardingSource
     * @param  $document
     *
     * This function updates the onboarding source if the record for a given mid already exists else
     *  it creates a new entry in the merchant_v3_index
     */
    public function storeOrUpdateDocument(string $merchantId, string $index, string $onboardingSource, $document)
    {

        $params = [
            'index'   => $index,
            'type'    => '_doc',
            'id'      => $merchantId,
            'refresh' => true
        ];

        $searchResponse = $this->esDao->searchByIdInDedupeEs($index, $merchantId);

        if (empty($document) === false and empty($searchResponse) === true)
        {
            $params['body'] = $document;
            $this->esDao->storeMerchantDetailsInDedupeEs($params);

            $this->trace->info(TraceCode::STORING_MERCHANT_DETAILS_FOR_DEDUPE_CHECK, ['params' => $params]);

            return;
        }

        $params['body'] = [
            'script' => sprintf('ctx._source.onboarding_source="%s";', $onboardingSource)
        ];

        $this->trace->info(TraceCode::UPDATE_MERCHANT_DETAILS_FOR_DEDUPE_CHECK, $params);

        $this->esDao->updateMerchantDetailsInDedupeEs($params);
    }

    protected function checkForBulkUpdateOperationErrors(array $params, array $res)
    {
        $errors = array_get($res, 'errors', true);

        if ($errors === false)
        {
            return;
        }

        $items         = $res['items'] ?? [];
        $itemsPerError = collect($items)
                            ->filter(
                                function($v, $k)
                                {
                                    return (isset($v['index']['error']) === true);
                                })
                            ->groupBy('index.error.type');

        // Temporary: (Ref: https://github.com/razorpay/api/issues/3477)
        // Just trace if there is only mapping errors. Otherwise if it
        // contains other type of errors too raise exception.
        if (($itemsPerError->count() === 1) and
            ($itemsPerError->has('mapper_parsing_exception') === true))
        {
            $this->trace->info(TraceCode::ES_SYNC_FAILED, $itemsPerError->all());
        }
        else
        {
            throw new ServerErrorException(
                'Errors in bulkUpdate response',
                ErrorCode::SERVER_ERROR_ES_OPERATION_ERRORED,
                $itemsPerError->all());
        }
    }

    protected function setMode(\RZP\Foundation\Application $app)
    {
        // Only for unit tests use default mode as test, else always expect rzp.mode to be in existence.
        $this->mode = (($app->runningUnitTests() === true) and (isset($app['rzp.mode']) === false)) ?
            Mode::TEST :
            $app['rzp.mode'];
    }
}
