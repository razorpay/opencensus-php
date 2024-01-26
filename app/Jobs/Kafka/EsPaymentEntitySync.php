<?php

namespace RZP\Jobs\Kafka;
use RZP\Exception\LogicException;
use RZP\Trace\TraceCode;
use RZP\Models\Base;

class EsPaymentEntitySync extends Job
{
    const MAX_BATCH_SIZE = 1000;

    const ACTION = "action";
    const ENTITY= "entity";
    const ID= "id";
    const REARCH= "rearch";

    private $action;
    private $entity;
    private $id;
    private $ids;
    private $repo;
    private $esRepo;
    private $rearch;

    public function handle(): void
    {
        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        $this->setEsPayload();

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'action'       => $this->action,
            'entity'       => $this->entity,
            'ids'          => $this->ids,
            'task_id'      => $taskId
        ];

        try
        {
            $this->setRepoAndEsRepo();
            $this->sync();
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ES_SYNC_FAILED,
                $tracePayload);
        }
    }

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

    private function setEsPayload()
    {
        //get below field from payload and populate private member variables
        try {
            $this->action = $this->payload[self::ACTION] ?? null;
            $this->entity = $this->payload[self::ENTITY] ?? null;
            $this->id = $this->payload[self::ID] ?? null;
            $this->rearch = $this->payload[self::REARCH] ?? null;

            if (isset($this->id) === true)
            {
                $this->ids = array_wrap($this->id);
            }

            // Validate payload
            if (empty($this->action) || empty($this->entity) || empty($this->id) || $this->rearch === null) {
                throw new \InvalidArgumentException('Invalid payload. Missing required fields.');
            }
        } catch (\Exception $e) {

            $this->trace->traceException(
                $e,
                null,
                TraceCode::INVALID_PAYLOAD_FOR_ES_PAYMENT_SYNC_EVENTS,
                ['payload' => $this->payload]
            );
        }

    }

}
