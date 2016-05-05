<?php

use Models\Base\EsDao;

class EsController extends BaseController
{
    protected $mode;
    protected $app;
    protected $esDao;

    public function __construct()
    {
        $this->esDao = new EsDao();
    }

    public function migrateEntity($entityName)
    {
        assert(defined("Constants\\Table::". strtoupper($entityName)));
        assert(in_array($entityName, ['payment', 'refund']));

        $skip = 0;

        // TODO: Add validations.
        if ((isset($input['skip'])) and ($input['skip'] >= 0))
        {
            $skip = $input['skip'];
        }

        // Currently, only storing notes of an entity.
        // Will change this when we move on to more things.
        $this->migrateNotes($entityName, $skip);
    }

    protected function migrateNotes($entityName, $skip)
    {
        $take = 1000;

        while(true)
        {
            $entities = $this->fetchNotesFromMySql($entityName, $skip, $take);

            $this->storeNotesInEs($entityName, $entities);

            if (count($entities) < $take)
            {
                break;
            }

            $skip += $take;

            sleep(1);
        }
    }

    protected function fetchNotesFromMySql($entityName, $skip, $take)
    {
        $entityRepoClass = 'Models' . '\\' . ucfirst($entityName) . '\\' . 'Repository';
        $entities = (new $entityRepoClass)->fetchAllWithLimit($skip, $take);

        return $entities;
    }

    // TODO: Check if already present in es
    // TODO: If one update fails, the whole bulk update fails.
    protected function storeNotesInEs($entityName, $entities)
    {
        $entityType = constant("Constants\\Table::". strtoupper($entityName));
        try
        {
            $storeResponse = $this->esDao->storeNotesInBulk($entityType, $entities);
        }
        catch (\Exception $ex)
        {
            // TODO: Throw exception
            die;
        }
    }
}