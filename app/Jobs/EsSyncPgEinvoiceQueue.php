<?php

namespace RZP\Jobs;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

/**
 * Es sync job class.
 * Receives insert/update/delete events of models and syncs the same change to ES.
 */
class EsSyncPgEinvoiceQueue extends Job
{
    const MAX_JOB_ATTEMPTS = 3;
    const JOB_RELEASE_WAIT = 30;
    const MAX_BATCH_SIZE = 1000;

    private $action;
    private $entity;
    private $ids;
    private $id;

    private $repo;
    private $esRepo;
    private $rearch;

    public $timeout = 4000;

    //changing queue to pg_invoice queue instead of the default queue
    /**
     * @var string
     */
    protected $queueConfigKey = 'pg_einvoice';

    public function __construct(
        string $mode,
        string $action,
        string $entity,
        $id,
        bool $rearch = false)
    {
        parent::__construct($mode);

        $this->action = $action;
        $this->entity = $entity;
        $this->ids    = array_wrap($id);
        // incase of rearch payments we have to call findOrFail instead of
        // findManyForIndexingByIds
        $this->rearch = $rearch;
    }

    public function handle()
    {
        $this->handleBackwardCompatibility();

        parent::handle();

        // Trace payload should include all necessary info for debugging.

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'action'       => $this->action,
            'entity'       => $this->entity,
            'ids'          => $this->ids,
        ];

        try
        {
            $this->trace->debug(TraceCode::ES_SYNC_REQUEST_PG_INVOICE_QUEUE, $tracePayload);

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

                if ($this->rearch === true)
                {
                    return $this->syncRearchEntities();
                }

                $this->syncApiEntities();

                break;

            case Base\EsRepository::DELETE:

                foreach ($this->ids as $id)
                {
                    $this->esRepo->deleteDocument($id);
                }

                break;

            default:

                throw new LogicException('EsSync: Invalid action.');
        }
    }


    private function syncApiEntities()
    {
        $batches = array_chunk($this->ids, self::MAX_BATCH_SIZE, true);

        foreach ($batches as $batch)
        {
            $documents = $this->repo->findManyForIndexingByIds($batch);

            $response = $this->esRepo->bulkUpdate($documents);

            $this->traceErrorResponse($response);
        }
    }

    private function syncRearchEntities()
    {
        foreach ($this->ids as $id)
        {
            $entity = $this->repo->findOrFail($id);

            $documents = $this->repo->serializeForIndexingForExternal($entity);

            $response = $this->esRepo->bulkUpdate([$documents]);

            $this->traceErrorResponse($response);
        }
    }

    /**
     * Handles backward compatibility , remove this function after deployment
     *
     * If this->id is set => request came from deserialization
     *
     */
    private function handleBackwardCompatibility()
    {
        if (isset($this->id) === true)
        {
            $this->ids = array_wrap($this->id);
        }
    }

    private function traceErrorResponse($res)
    {
        if( isset($res['errors']) and $res['errors'] == true)
        {
            $this->trace->debug(TraceCode::ES_UNHANDLED_FAILURE, [
                'mode'         => $this->mode,
                'action'       => $this->action,
                'entity'       => $this->entity,
                'ids'          => $this->ids,
            ]);
        }
    }
}
