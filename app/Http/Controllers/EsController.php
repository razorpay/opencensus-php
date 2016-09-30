<?php

namespace RZP\Http\Controllers;

use RZP\Base\RuntimeManager;
use RZP\Models\Base\EsDao;
use RZP\Trace\TraceCode;
use Request;

class EsController extends Controller
{
    protected $esDao;

    protected $entityName;
    protected $skip = 0;
    protected $created = 0;
    // Limit
    protected $take = 1000;

    public function __construct()
    {
        parent::__construct();

        $this->esDao = new EsDao();
    }

    public function migrateEntity($entityName)
    {
        // Currently, the entity migration is supported for only payments and refunds.
        assertTrue(defined("RZP\\Constants\\Table::". strtoupper($entityName)));
        assertTrue(in_array($entityName, ['payment', 'refund']));

        $this->entityName = $entityName;

        // Sets the skip and created_at values from the input. If not present, defaults to 0.
        if ((isset($input['skip'])) and ($input['skip'] >= 0))
        {
            $this->skip = $input['skip'];
        }

        if ((isset($input['created_at'])) and ($input['created_at'] >= 0))
        {
            $this->created = $input['created_at'];
        }

        // Currently, only storing notes of an entity.
        // Will change this when we move on to more things.
        $this->migrateNotes();
    }

    protected function migrateNotes()
    {
        $this->increaseAllowedSystemLimits();

        // The migration is done in batches.
        while(true)
        {
            // Gets all the entities with notes data from MySQL.
            $entities = $this->fetchNotesFromMySql();

            if (count($entities) === 0)
            {
                break;
            }

            // Stores these entities in ES
            $this->storeNotesInEs($entities);

            if (count($entities) < $this->take)
            {
                break;
            }

            $this->skip += $this->take;

            // Adding sleep here, just in case.
            sleep(1);
        }
    }

    protected function fetchNotesFromMySql()
    {
        // Gets the repository of the entity which is being migrated.
        $entityRepo = $this->getEntityRepo();

        $entities = $entityRepo->fetchAllNotesFromCreatedAt($this->skip, $this->created, $this->take);

        return $entities;
    }

    protected function getEntityRepo()
    {
        $entityRepoClass = 'RZP' . '\\' . 'Models' . '\\' . ucfirst($this->entityName) . '\\' . 'Repository';
        $entityRepo = new $entityRepoClass;

        return $entityRepo;
    }

    protected function storeNotesInEs($entities)
    {
        // The ES entity type is the same as the MySQL table name.
        $entityType = constant("RZP\\Constants\\Table::" . strtoupper($this->entityName));

        // Gets all the ids of all the entities which need to be migrated.
        $entityIds = $entities->getIds();

        // We do not migrate the entities which are already present in ES.
        $storeEntityIds = $this->getEntityIdsAbsentInEs($entityType, $entityIds);

        // If all the entities are already present in ES, return the control.
        if (empty($storeEntityIds) === true)
        {
            return;
        }

        $storeEntities = $entities->filterEntitiesFromEntityIds($storeEntityIds);

        try
        {
            $storeResponse = $this->esDao->storeNotesInBulk($entityType, $storeEntities);

            // If errors are present, log it as warning,
            // otherwise, log it as info.
            if ($storeResponse['errors'] === true)
            {
                $this->trace->warning(
                    TraceCode::ES_BULK_UPDATE_FAILED,
                    [
                        'es_response' => $storeResponse,
                        'entity_ids'  => $storeEntityIds,
                        'message'     => 'Bulk store of entities gave errors in ES response.'
                    ]
                );
            }
            else
            {
                $this->trace->info(
                    TraceCode::ES_BULK_UPDATE,
                    [
                        'es_response' => $storeResponse,
                        'entity_ids'  => $storeEntityIds,
                    ]
                );
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::ES_BULK_UPDATE_FAILED,
                [
                    'entityIds' => $storeEntityIds,
                ]
            );

            $this->trace->traceException($ex);
        }
    }

    protected function getEntityIdsAbsentInEs($entityType, $entityIds)
    {
        // Runs an mget (bulk GET request) to get the documents by Ids.
        $esEntities = $this->esDao->findMultipleDocumentsByIds($entityType, $entityIds);
        $esEntities = $esEntities['docs'];

        // Filters and gets the IDs of all the documents which are returned as 'not found' by ES response.
        $absentEsEntityIds = array_map(function($esEntity) {
            if ($esEntity['found'] === false)
            {
                return $esEntity['_id'];
            }
        }, $esEntities);

        // This is required to remove the null entries from the array.
        return array_filter($absentEsEntityIds);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(1800);
    }
}
