<?php

namespace RZP\Models\Base;

use App;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;

class EsDao
{
    // Instance which will be used to communicate with ES.
    protected $es;

    protected $indexName;

    protected $config;

    protected $mode;

    // Logically separated instance for heimdall
    protected $esHeimdall;

    public function __construct($mode = null)
    {
        $this->app = App::getFacadeRoot();

        $this->config = $this->app['config'];

        // Host name will be retrieved from the ENV.
        $hostName = $this->config->get('database.es_host');

        // Since, we are using only one index, declaring the index name
        // in this class itself. If we have different indices based on some
        // logic, it makes sense to move it to an appropriate class then.
        // Live and Test have different index names in the ES cluster.
        $this->setIndexName($mode);

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
    }

    /**
     * @deprecated
     *
     * Sets index name.
     * We only have two indexes, one for each mode. And all the notes of different
     * entities are indexed in one of them as a type.
     *
     * @param string $mode
     *
     * @return
     */
    public function setIndexName($mode)
    {
        if (empty($mode) === true)
        {
            if (isset($this->app['rzp.mode']) === true)
            {
                $mode = $this->app['rzp.mode'];
            }
            else
            {
                $mode = Mode::TEST;
            }
        }

        $this->indexName = $this->config->get('database.es_index')[$mode];
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
        $this->es->delete($params);
    }

    public function search(array $params)
    {
        return $this->es->search($params);
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

    // If a document with entity ID is already present, only the notes key is updated.
    // Otherwise, creates a new document.
    // Currently storing only notes and merchant id of the entity.
    public function storeNotes($typeName, $entityData)
    {
        $entityId = $entityData['entity_id'];
        $merchantId = $entityData['merchant_id'];
        // Converting to object because sequential arrays cannot be stored in the ES schema designed.
        // Hence, using an object instead to get proper key-values.
        $notes = (object) $entityData['notes'];
        $created = time();

        // Using payment ID as the doc ID.
        $params = [
            'index' => $this->indexName,
            'type'  => $typeName,
            'id'    => $entityId,
            'body'  => [
                'upsert' => [
                    'created' => $created,
                    'merchant_id' => $merchantId,
                    'notes' => $notes
                ],
                'doc' => [
                    'notes' => $notes,
                ]
            ]
        ];

        $updateReponse = $this->es->update($params);
    }

    public function storeNotesInBulk($typeName, $entities)
    {
        $params = [];
        foreach ($entities as $entityData)
        {
            $entityId = $entityData->getId();
            $merchantId = $entityData->getMerchantId();
            $notes = $entityData->getNotes();
            $created = $entityData->getCreatedAt();

            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_type'  => $typeName,
                    '_id'    => $entityId,
                ]
            ];

            $params['body'][] = [
                'created'     => $created,
                'merchant_id' => $merchantId,
                'notes'       => $notes,
            ];
        }

        $bulkUpdateResponse = $this->es->bulkUpdate($params);

        return $bulkUpdateResponse;
    }

    public function getNotes($typeName, $params)
    {
        $merchantId = $params['merchant_id'];
        $searchString = $params['notes'];
        $count = (int) $params['count'];

        // For pagination
        $skip = 0;
        if (isset($params['skip']) === true)
        {
            $skip = (int) $params['skip'];
        }

        $filter = [];
        if ($merchantId !== null)
        {
            $filter = ['term' => ['merchant_id' => $merchantId]];
        }

        $params = [
            'index' => $this->indexName,
            'type'  => $typeName,
            'body'  => [
                'size'  => $count,
                'from'  => $skip,
                'query' => [
                    'bool'  => [
                        'must' => [
                            'multi_match'   => [
                                'query'     => $searchString,
                                'type'      => 'cross_fields',
                                'fields'    => ['notes.*']
                            ]
                        ],
                        'filter'    => $filter,
                    ]
                ],
                'sort'  => [
                    [
                        'created'   => [
                            'order' => 'desc'
                        ]
                    ]
                ]
            ]
        ];

        $entityIds = $this->es->searchNotes($params);

        $this->app['trace']->debug(
            TraceCode::ES_GET_NOTES_QUERY_AND_RESPONSE,
            [
                'es_search_params'     => $params,
                'es_search_result_ids' => $entityIds,
            ]);

        return $entityIds;
    }

    public function findMultipleDocumentsByIds($typeName, $entityIds)
    {
        $params = [
            'index' => $this->indexName,
            'type' => $typeName,
            // This method just needs to find the documents and not get the data of the documents found.
            // Hence, source is set to false.
            '_source' => false,
            'body' => [
                'ids' => $entityIds
            ]
        ];
        return $this->es->multiGet($params);
    }

    public function getPaymentById($typeName, $documentId)
    {
        $params = [
            'index'  => $this->indexName,
            'type'   => $typeName,
            'id'     => $documentId,
        ];

        return $this->es->get($params);
    }

    public function getPaymentsByDateRange($typeName, $fromDate, $toDate="now")
    {
        $params = [
            'index' => $this->indexName,
            'type' => $typeName,
            'body' => [
                'filter' => [
                    'range' => [
                        'created_at' => [
                            'gte' => $fromDate,
                            'lte' => $toDate
                        ]
                    ]
                ]
            ]
        ];
        return $this->es->search($params);
    }


    public function deletePaymentById($typeName, $paymentId)
    {
        //Payment ID is the doc ID.
        $params = [
            'index' => $this->indexName,
            'type' => $typeName,
            'id' => $paymentId
        ];

        return $this->es->delete($params);
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

        return $this->es->createIndex($params);
    }


    //This method should not be used on prod. We should delete the indices on prod directly.
    public function deleteIndex($indexName)
    {
        $params = ['index' => $indexName];

        return $this->es->deleteIndex($params);
    }


    //This method should not be used on prod. We should change index settings on prod directly.
    public function changeIndexSettings($indexName, $settings)
    {
        $params = [
            'index' => $indexName,
            'body' => [
                'settings' => $settings
            ]
        ];

        return $this->es->changeIndexSettings($params);
    }

    public function storeAdminEvent($index, $type, $fields)
    {
        $this->createIndexIfNotExistsInHiemdallHost($index);

        $params = [
            'index' => $index,
            'type'  => $type,
            'body'  => $fields
        ];

        $updateReponse = $this->es->indexHeimdall($params);
    }

    protected function createIndexIfNotExistsInHiemdallHost($index)
    {
        $params['index'] = $index;

        $client = $this->es->getHeimdallClient();

        $doesExist = $client->indices()->exists($params);

        if ($doesExist === false)
        {
            $client->indices()->create($params);
        }
    }

    public function createIndexIfNotExistsInDefaultHost(
        string $indexName,
        array $settings,
        array $mappings)
    {
        $indexExists = $this->es->indexExists(['index' => $indexName]);

        if ($indexExists)
        {
            return;
        }

        $this->createIndex($indexName, $settings, $mappings);
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

        $this->app['trace']->info(TraceCode::MISC_TRACE_CODE, ['results' => $results]);

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
}
