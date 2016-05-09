<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;

class EsRepository extends \Razorpay\Spine\Repository
{
    protected $esDao;
    protected $indexName;

    public function __construct()
    {
        parent::__construct();

        $app = \App::getFacadeRoot();

        $this->indexName = $app['config']->get('database.es_index');

        $this->esDao = new EsDao();
    }

    public function fetchNotes($typeName, $params, $merchantId)
    {
        $params['merchant_id'] = $merchantId;

        $entities = [];

        // Returns all the entity IDs matching the notes search.
        $entityIds = $this->esDao->getNotes($typeName, $params);

        if (empty($entityIds) !== true)
        {
            // Get the entity data from MySQL.
            $entities = $this->newQuery()->findOrFailPublic($entityIds, array('*'));
            
            // MySQL should contain all entities present in ES.
            if ($entities->count() !== count($entityIds))
            {
                throw new Exception\ServerErrorException(
                    'Did not find corresponding entity data in MySQL' ,
                    ErrorCode::SERVER_ERROR_MYSQL_ENTRY_NOT_FOUND);
            }
        }

        return $entities;
    }

    // Currently storing only notes and merchant ID.
    public function storeEntity($typeName, $entity)
    {
        $entityId = $entity->getId();
        $merchantId = $entity->getMerchantId();
        $notes = $entity->getNotes();

        $params['notes'] = $notes;
        $params['merchant_id'] = $merchantId;
        $params['entity_id'] = $entityId;

        $this->esDao->storeNotes($typeName, $params);
    }
}