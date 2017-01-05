<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use App;

use RZP\Constants\Entity;

/**
 * Input:
 * - Mode
 * - Entity name
 *
 * Flow:
 * - Ensures index exists for given entity
 * - Reads all data from mysql and indexes into es
 *
 * TODO:
 * - Add logging/tracing and debug lines
 */
class Index extends Command
{
    protected $signature = 'rzp:index
                            {--mode=test : Database mode the command will run in (test|live)}
                            {--entity=   : Entity name (eg. item|merchant) }';

    protected $description = 'Indexes entity data into ES.';

    protected $mode;
    protected $entity;

    public function fire()
    {
        $this->setOptions();

        \Database\DefaultConnection::set($this->mode);

        $this->app  = App::getFacadeRoot();

        $this->initRepo();

        $this->doIndexing();
    }

    protected function setOptions()
    {
        $this->mode   = $this->option('mode');
        $this->entity = $this->option('entity');
    }

    protected function initRepo()
    {
        //
        // Gets es repo of entity
        //

        $esRepoPath   = Entity::getEntityEsRepository($this->entity);
        $this->esRepo = new $esRepoPath;

        $indexName = $this->mode . '_' . $this->entity;

        $this->esRepo->setIndexName($indexName);
    }

    protected function doIndexing()
    {
        $this->esRepo->createIndexIfNotExists();

        $skip = 0;
        $take = 100;

        while (true)
        {
            $collection = $this->esRepo->fetchForIndex(null, $skip, $take);

            if ($collection->count() === 0)
            {
                break;
            }

            $serialized = $collection->toArray();

            $this->bulkUpdate($serialized);

            $skip += $take;
        }
    }

    protected function bulkUpdate(array $documents)
    {
        try
        {
            $this->esRepo->bulkUpdate($documents);
        }
        catch(\Exception $ex)
        {
        }
    }
}
