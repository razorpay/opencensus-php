<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Exception\LogicException;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Es;

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

            $this->esRepo->setIndexName($this->mode . '_' . $this->entity);

            $this->esRepo->createIndexIfNotExists();

            $this->sync();

            $this->delete();
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e, Trace::ERROR, TraceCode::ES_SAVE_FAILED, $tracePayload);

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
        $app = App::getFacadeRoot();

        $app['rzp.mode'] = $this->mode;

        \Database\DefaultConnection::set($this->mode);

        $this->repoManager = $app['repo'];

        $this->trace = $app['trace'];

        $this->repo = $this->repoManager->{$this->entity};

        $this->esRepo = $this->repo->getEsRepoIfExistElseNull();

        if ($this->esRepo === null)
        {
            throw new LogicException('EsSync: Es repo not found.');
        }
    }

    protected function sync()
    {
        switch ($this->action)
        {
            case Es\Repository::UPSERT:

                $document = $this->esRepo->findForIndex($this->id);

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
