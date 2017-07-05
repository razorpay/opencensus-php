<?php

namespace RZP\Models\Base;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class EsRepository extends \Razorpay\Spine\Repository
{
    use Base\Traits\Es\QueryBuilder;

    /**
     * Maximum number of attempts for a given ES sync queue job.
     */
    const MAX_JOB_ATTEMPTS = 3;

    /**
     * Wait for 120 s before re-queuing the failed job.
     */
    const JOB_RELEASE_WAIT = 120;

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
    protected $fields         = [];

    /**
     * Fields against with 'q' param will be matched against from ES
     *
     * @var array
     */
    protected $queryFields    = [];

    /**
     * Constructor
     *
     * @param string $entity
     */
    public function __construct(string $entity)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->esDao = new Base\EsDao;

        $esEntityIndexPrefix = $app['config']->get('database.es_entity_index_prefix');

        // Index name is of following format:
        // <prefix><entity>_<mode>, Eg. 'delta_api_invoice_live'.

        $indexName = $esEntityIndexPrefix . $entity . '_' . $app['rzp.mode'];

        $this->setIndexNameByValue($indexName);
    }

    public function setIndexNameByValue(string $indexName)
    {
        $this->indexName = $indexName;

        $this->esDao->setIndexNameByValue($indexName);
    }

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

    /**
     * Makes search in ES on this model with given params.
     *
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

        $this->trace->info(TraceCode::MISC_TRACE_CODE, $esRequestParams);

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
        // Initializes query to empty array, which follows formation of the same
        // using methods defined in QueryBuilder.

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

        $sort = $this->getSortParameter();

        return [
            'index' => $this->indexName,
            'type'  => $this->indexName,
            'body'  => [
                '_source' => $source,
                'from'    => $from,
                'size'    => $size,
                'query'   => $query,
                'sort'    => $sort,
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

        $res = $this->esDao->bulkUpdate($params);

        $error = $res['errors'] ?? true;

        if ($error === true)
        {
            $this->trace->error(
                TraceCode::ES_BULK_UPDATE_FAILED,
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
