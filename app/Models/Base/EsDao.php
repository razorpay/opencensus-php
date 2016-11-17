<?php

namespace RZP\Models\Base;

use App;
use RZP\Constants\Mode;

class EsDao
{
    // Instance which will be used to communicate with ES.
    protected $es;

    protected $indexName;

    protected $config;

    protected $mode;

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
    }


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

        $params = [
            'index' => $this->indexName,
            'type'  => $typeName,
            'body'  => [
                'size'  => $count,
                'from'  => $skip,
                'query' => [
                    'filtered'  => [
                        'query' => [
                            'multi_match'   => [
                                'query'     => $searchString,
                                'type'      => 'cross_fields',
                                'fields'    => ['notes.*']
                            ]
                        ],
                        'filter'    => [],
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

        if ($merchantId !== null)
        {
            $params['body']['query']['filtered']['filter'] = ['term' => ['merchant_id' => $merchantId]];
        }

        $entityIds = $this->es->searchNotes($params);

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
}
