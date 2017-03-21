<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use App;

use RZP\Constants\Entity;
use RZP\Trace\Trace;

/**
 * Indexes entity into es for search purposes.
 *
 */
class Index extends Command
{
    protected $signature = 'rzp:index
                            {--mode=test : Database mode the command will run in (test|live)}
                            {--entity=   : Entity name (eg. item|merchant) }';

    protected $description = 'Indexes entity into es for search purposes.';

    protected $mode;
    protected $entity;
    protected $trace;
    protected $esRepo;

    public function fire()
    {
        $this->setOptions();

        \Database\DefaultConnection::set($this->mode);

        $app  = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->initRepo();

        $this->doIndexing();
    }

    protected function setOptions()
    {
        $this->mode   = $this->option('mode');
        $this->entity = $this->option('entity');
    }

    /**
     * Sets es repo and index name for the entity.
     *
     */
    protected function initRepo()
    {
        $esRepoPath = Entity::getEntityEsRepository($this->entity);
        $this->esRepo = new $esRepoPath;

        $indexName = $this->mode . '_' . $this->entity;

        $this->esRepo->setIndexName($indexName);
    }

    /**
     * Fetches all entities in batch and indexes them to es.
     *
     */
    protected function doIndexing()
    {
        $this->esRepo->createIndexIfNotExists();

        $skip = 0;
        $take = 100;

        while (true)
        {
            $this->info('Offset: ' . $skip);

            $documents = $this->esRepo->fetchForIndex($skip, $take);

            if (count($documents) === 0)
            {
                break;
            }

            try
            {
                $this->esRepo->bulkUpdate($documents);
            }
            catch(\Exception $e)
            {
                $this->error($e);

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    null,
                    [
                        'mode'   => $this->mode,
                        'entity' => $this->entity,
                        'skip'   => $skip,
                        'take'   => $take,
                    ]);
            }

            $skip += $take;
        }
    }
}
