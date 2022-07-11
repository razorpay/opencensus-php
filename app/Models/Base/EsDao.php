<?php

namespace RZP\Models\Base;

use App;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class EsDao
{
    // Instance which will be used to communicate with ES.
    protected $es;

    protected $indexName;

    protected $config;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    // Logically separated instance for heimdall
    protected $esHeimdall;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->config = $this->app['config'];

        $this->trace = $this->app['trace'];

        // Host name will be retrieved from the ENV.
        $hostName = $this->config->get('database.es_host');

        $this->es = $this->app['es'];

        $params = [
            'hosts' => [
                $hostName
            ],
        ];
        // Since the es client is being set on this, ensure that only this es
        // instance is used to perform any operations on the client.
        $this->es->setEsClient($params);

        $heimdallHost = $this->config->get('database.es_audit_host');

        $this->es->setHeimdallESClient([$heimdallHost]);

        // By default laravel appending default port 9200 to prevent that we are appending Aws default Es port 443
        $dedupeHost = $this->config->get('database.dedupe_es_host').':443';

        $this->es->setDedupeEsClient([$dedupeHost]);
    }

    public function setIndexNameByValue(string $indexName)
    {
        $this->indexName = $indexName;
    }

    public function bulkUpdate(array $params)
    {
        return $this->es->bulkUpdate($params);
    }

    public function delete(array $params)
    {
        $this->trace->info(TraceCode::ES_DELETE_QUERY, ['params' => $params]);

        $this->es->delete($params);
    }

    public function search(array $params)
    {
        return $this->es->search($params);
    }

    public function searchAndScroll(array $params): \Generator
    {
        return $this->es->searchAndScroll($params);
    }

    /**
     * Returns the EsClient instance.
     *
     * @return \RZP\Services\EsClient
     */
    public function getEsClient()
    {
        return $this->es;
    }

    //This method should not be used on prod. We should create the indices on prod directly.
    public function createIndex($indexName, $settings, $mappings) {
        $params = [
            'index' => $indexName,
            'body' => [
                'settings' => $settings,
                'mappings' => $mappings
            ]
        ];

        $this->trace->info(TraceCode::ES_INDEX_CREATE, ['params' => $params]);

        return $this->es->createIndex($params);
    }

    public function storeAdminEvent($index, $type, $fields)
    {
        $this->createIndexIfNotExists($index);

        $params = [
            'index' => $index,
            'type'  => $type,
            'body'  => $fields
        ];

        $updateReponse = $this->es->indexHeimdall($params);
    }

    protected function createIndexIfNotExists($index)
    {
        $params['index'] = $index;

        $client = $this->es->getHeimdallClient();

        $doesExist = $client->indices()->exists($params);

        if ($doesExist === false)
        {
            $client->indices()->create($params);
        }
    }

    public function searchByIndexTypeAndActionId($indexName, $typeName, $id)
    {
        $params = [
            'index' => $indexName,
            'type'  => $typeName,
            'body'  => [
                'query' => [
                   'match' => [
                        'action_id' => $id
                    ]
                ]
            ]
        ];

        return $this->es->searchHeimdall($params);
    }

    public function updateActionState($indexName, $typeName, $documentId, $state)
    {
        $params = [
            'index' => $indexName,
            'type'  => $typeName,
            'id'    => $documentId,
            'body'  => [
                'script' => sprintf('ctx._source.state="%s";', $state)
            ],
        ];

        return $this->es->updateHeimdall($params);
    }

    public function getDocumentByFields($indexName, $typeName, $terms)
    {
        $matchParamsForQuery = [];

        foreach ($terms as $key => $val)
        {
            $matchParamsForQuery[] = [
                'match' => [ $key => $val ]
            ];
        }

        $body = [
            "query" => [
                "bool" => [
                    "must" => $matchParamsForQuery
                ]
            ]
        ];

        $params = [
            'index' => $indexName,
            'type'  => $typeName,
            'body'  => $body,
        ];

        return $this->es->searchHeimdall($params);
    }

    public function searchDifferByParams(
        string $indexName,
        string $typeName,
        array $matchParams,
        array $openStates)
    {
        $matchParamsForQuery = [];

        foreach ($matchParams as $key => $val)
        {
            $matchParamsForQuery[] = [
                'match' => [ $key => $val ]
            ];
        }

        $body = [
            "query" => [
                "bool" => [
                    "must" => $matchParamsForQuery,
                    "filter" => [
                        "terms" => [
                            "state" => $openStates // ['open', 'approved']
                        ]
                    ]
                ]
            ]
        ];

        $params = [
            'index' => $indexName,
            'type'  => $typeName,
            'body'  => $body,
        ];

        return $this->es->searchHeimdall($params);
    }

    public function searchAuditLogs($orgId, $options = [])
    {
        $mode = empty($this->app['rzp.mode']) ? Mode::TEST : $this->app['rzp.mode'];

        $index = $this->config->get('database.es_audit')[$mode];

        $params = [
            'index'  => $index,
            'body' => [
                'query' => [
                    'match' => [
                        'extra.org_id' => $orgId
                    ]
                ],
                'sort' => [
                    'created_at' => ['order' => 'desc']
                ]
            ]
        ];

        if (isset($options['skip']))
        {
            $params['body']['from'] = (int) $options['skip'];
        }

        if (isset($options['count']))
        {
            $params['body']['size'] = (int) $options['count'];
        }

        $results = $this->es->searchHeimdall($params);

        // If the index has no documents
        if (empty($results))
        {
            $results = [];
        }

        $this->trace->info(TraceCode::MISC_TRACE_CODE, ['results' => $results]);

        $formattedResults = $this->formatAuditLogResults($results);

        return $formattedResults;
    }

    protected function formatAuditLogResults($results)
    {
        // format results
        $keyMap = [
            '_id' => 'id',
            '_source' => 'event'
        ];

        $exclude = [
            '_index',
            '_type',
            '_score'
        ];

        foreach($results as &$item)
        {
            foreach ($keyMap as $key => $replace)
            {
                if (isset($item[$key]) === true)
                {
                    $item[$replace] = $item[$key];

                    unset($item[$key]);
                }
            }

            foreach($exclude as $key)
            {
                if (isset($item[$key]))
                {
                    unset($item[$key]);
                }
            }
        }

        return $results;
    }

    public function updateObserverDataInEs($indexName, $typeName, $documentId, array $observerData)
    {
        $params = [
            'index' => $indexName,
            'type'  => $typeName,
            'id'    => $documentId,
            'body'  => [
                'script'    => [
                    'inline'    =>  'ctx._source.workflow_observer_data= params.observer',
                    'params'    => [
                        'observer'  =>  $observerData
                     ]
                ]
            ]
        ];

        return $this->es->updateHeimdall($params);
    }

    public function updateDiffForActionId($indexName, $typeName, $documentId, array $diff)
    {
        $params = [
            'index' => $indexName,
            'type'  => $typeName,
            'id'    => $documentId,
            'body'  => [
                'script'    => [
                    'inline'    =>  'ctx._source.diff= params.diff',
                    'params'    => [
                        'diff'  =>  $diff
                     ]
                ]
            ]
        ];

        return $this->es->updateHeimdall($params);
    }

    public function searchByIdInDedupeEs($indexName, $documentId)
    {
        $params = [
            'index' => $indexName,
            'type'  => '_all',
            'body'  => [
                'query' => [
                    'match' => [
                        'action_id' => $documentId
                    ]
                ]
            ]
        ];

        return $this->es->searchDedupe($params);
    }

    public function storeMerchantDetailsInDedupeEs($params)
    {
        $this->es->indexDedupe($params);
    }

    public function updateMerchantDetailsInDedupeEs($params)
    {
        return $this->es->updateDedupe($params);
    }
}
