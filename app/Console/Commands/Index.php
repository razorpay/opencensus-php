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
    protected $repo;
    protected $esRepo;

    public function fire()
    {
        $this->setOptions();

        \Database\DefaultConnection::set($this->mode);

        $app = App::getFacadeRoot();

        $app['rzp.mode'] = $this->mode;

        $this->trace = $app['trace'];

        $repoManager = $app['repo'];

        $this->repo = $repoManager->{$this->entity};

        $this->repo->setEsRepoIfExist();

        $this->esRepo = $this->repo->getEsRepo();

        if ($this->esRepo === null)
        {
            throw new LogicException('EsSync: Es repo not found.');
        }
    }

    protected function setOptions()
    {
        $this->mode   = $this->option('mode');
        $this->entity = $this->option('entity');
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
