<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

/**
 * Es sync job class.
 * Receives insert/update/delete events of models and syncs the same change to ES.
 */
class EsSync extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    const MAX_JOB_ATTEMPTS = 3;
    const JOB_RELEASE_WAIT = 30;

    private $action;
    private $entity;
    private $id;

    private $repo;
    private $esRepo;

    public function __construct(
        string $mode,
        string $action,
        string $entity,
        string $id)
    {
        parent::__construct($mode);

        $this->action = $action;
        $this->entity = $entity;
        $this->id     = $id;
    }

    public function handle()
    {
        parent::handle();

        // Trace payload should include all necessary info for debugging.

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'action'       => $this->action,
            'entity'       => $this->entity,
            'id'           => $this->id,
        ];

        try
        {
            $this->trace->debug(TraceCode::ES_SYNC_REQUEST, $tracePayload);

            $this->setRepoAndEsRepo();

            $this->sync();

            $this->delete();
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::ES_SYNC_FAILED,
                            $tracePayload);

            // If it's logical error or maximum number of retries has happened
            // just delete the job, else retry the job after a wait.

            if (($e instanceof LogicException) or
                ($this->attempts() >= self::MAX_JOB_ATTEMPTS))
            {
                $this->delete();
            }
            else
            {
                $this->release(self::JOB_RELEASE_WAIT);
            }
        }
    }

    /**
     * Sets repository and es repository corresponding to the entity
     * set in queue message.
     *
     * @return null
     * @throws LogicException
     */
    private function setRepoAndEsRepo()
    {
        $this->repo = $this->repoManager->{$this->entity};

        $this->esRepo = $this->repo->setAndGetEsRepoIfExist();

        if ($this->esRepo === null)
        {
            throw new LogicException('EsSync: Es repo not found.');
        }
    }

    private function sync()
    {
        switch ($this->action)
        {
            case Base\EsRepository::CREATE:
            case Base\EsRepository::UPDATE:

                $document = $this->repo->findForIndexing($this->id);

                $this->esRepo->bulkUpdate([$document]);

                break;

            case Base\EsRepository::DELETE:

                $this->esRepo->deleteDocument($this->id);

                break;

            default:

                throw new LogicException('EsSync: Invalid action.');
        }
    }
}
