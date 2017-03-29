<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Exception\LogicException;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Es;

/**
 * Es sync job class.
 * Receives insert/update/delete events of models and syncs the same change to ES.
 */
class EsSync extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    private $mode;
    private $action;
    private $entity;
    private $id;

    private $repoManager;
    private $repo;
    private $esRepo;
    private $trace;

    public function __construct(
        string $mode,
        string $action,
        string $entity,
        string $id)
    {
        $this->mode   = $mode;
        $this->action = $action;
        $this->entity = $entity;
        $this->id     = $id;
    }

    public function handle()
    {
        try
        {
            $this->init();

            $tracePayload = [
                'job_attempts' => $this->attempts(),
                'mode'         => $this->mode,
                'action'       => $this->action,
                'entity'       => $this->entity,
                'id'           => $this->id,
            ];

            $this->trace->debug(TraceCode::ES_SAVE_REQUEST, $tracePayload);

            // We do this to ensure index with proper settings is created already.
            $this->esRepo->createIndexIfNotExists();

            $this->sync();

            $this->delete();
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e, Trace::ERROR, TraceCode::ES_SAVE_FAILED, $tracePayload);

            // If it's logical error or maximum number of retries has happened
            // just delete the job, else retry the job after a wait.
            if (($e instanceof LogicException) or
                ($this->attempts() > Es\Repository::MAX_JOB_ATTEMPTS))
            {
                $this->delete();
            }
            else
            {
                $this->release(Es\Repository::JOB_RELEASE_WAIT);
            }
        }
    }

    /**
     * - Initializes instance variables: core, trace etc.
     * - Sets application mode, database connection based on the mode.
     * - Validates event
     *
     * @return null
     * @throws LogicException
     */
    private function init()
    {
        // - Gets App facade
        // - Sets App's mode
        // - Sets database's mode
        // - Initializes other needed services
        // - Sets repo and es repo for the entity

        $app = App::getFacadeRoot();

        $app['rzp.mode'] = $this->mode;

        \Database\DefaultConnection::set($this->mode);

        $this->repoManager = $app['repo'];

        $this->trace = $app['trace'];

        $this->repo = $this->repoManager->{$this->entity};

        $this->repo->setEsRepoIfExist();

        $this->esRepo = $this->repo->getEsRepo();

        if ($this->esRepo === null)
        {
            throw new LogicException('EsSync: Es repo not found.');
        }
    }

    private function sync()
    {
        switch ($this->action)
        {
            case Es\Repository::UPSERT:

                $document = $this->repo->findForIndexing($this->id);

                $this->esRepo->bulkUpdate([$document]);

                break;

            case Es\Repository::DELETE:

                $this->esRepo->deleteDocument($this->id);

                break;

            default:

                throw new LogicException('EsSync: Invalid action.');
        }
    }
}
