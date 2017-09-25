<?php

namespace RZP\Models\Base;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;

class EsRepository extends \Razorpay\Spine\Repository
{
    use Base\Traits\Es\QueryBuilder;

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
    protected $indexPrefix;

    /**
     * Name of the index to which this repo might correspond to.
     *
     * @var null|string
     */
    protected $indexName = null;

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

        $this->mode = $app['rzp.mode'];

        $this->entity = $entity;

        $this->trace = $app['trace'];

        $this->esDao = new Base\EsDao;

        $this->indexPrefix = $app['config']->get('database.es_entity_index_prefix');

        //
        // Sets index name for this repository
        // Format: <prefix_><entity>_<mode>
        //
        $index = "{$this->indexPrefix}{$this->entity}_{$this->mode}";

        $this->setIndexNameByValue($index);

    }

    public function setIndexNameByValue(string $indexName)
    {
        $this->indexName = $indexName;

        $this->esDao->setIndexNameByValue($indexName);
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

        $this->trace->info(TraceCode::ES_REQUEST_PARAMS, $esRequestParams);

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
            //
            // TODO:
            // - Can't use following with alias as index name. Type name doesn't
            //   need to be a variable of env/app.
            //
            // Also given that all our indices have only one type, following is
            // not really needed.
            //
            // 'type'  => $this->indexName,
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

        $res = $this->esDao->bulkUpdate($params);

        $errors = $res['errors'] ?? true;

        if ($errors === true)
        {
            throw new ServerErrorException(
                'Errors in bulkUpdate response',
                ErrorCode::SERVER_ERROR_ES_OPERATION_ERRORED,
                [
                    'params' => $params,
                    'res'    => $res,
                ]);
        }

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
            'type'  => $this->indexName,
            'id'    => $id,
        ];

        $this->esDao->delete($params);
    }
}
