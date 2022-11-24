<?php

namespace RZP\Console\Commands;

use App;
use Illuminate\Console\Command;

use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Base\EsRepository;


/**
 * Indexes entity into es for search purposes.
 * Do -h for more options.
 * Refer: https://github.com/razorpay/api/wiki/Making-models-in-API-searchable-via-ES
 */
class Index extends Command
{
    protected $signature = 'rzp:index
                            {mode            : Database & application mode the command will run in (test|live)}
                            {entity          : Entity name (eg. item|merchant) }

                            {--primary_key=  : Primary key of the entity }
                            {--slave=0       : Whether to use slave or master db connection? (0|1)}
                            {--index_prefix= : ES new index prefix (eg. 20171201_beta_api_) }
                            {--after_id=     : Skip until specified row id }
                            {--take=5000     : Take count (eg. 1000 at a time) }
                            {--start_at=     : Start value(epoch) for time range query }
                            {--end_at=       : End value(epoch) for time range query }';

    protected $description = 'Indexes entity into es for search purposes.';

    protected $mode;
    protected $entity;
    protected $primaryKey;
    protected $slave;
    protected $indexPrefix;
    protected $afterId;
    protected $take;
    protected $startAt;
    protected $endAt;

    protected $trace;
    protected $repo;
    protected $esRepo;

    public function handle()
    {
        $this->setOptions();

        $this->init();

        $this->doIndexing();
    }

    protected function setOptions()
    {
        $this->mode        = $this->argument('mode');
        $this->entity      = $this->argument('entity');
        $this->primaryKey  = $this->option('primary_key');
        $this->slave       = (int) $this->option('slave');
        $this->indexPrefix = $this->option('index_prefix');
        $this->afterId     = trim($this->option('after_id'));
        $this->take        = (int) $this->option('take');
        $this->startAt     = $this->option('start_at');
        $this->endAt       = $this->option('end_at');
    }

    protected function init()
    {
        // 1. Sets DB connection and Application mode.

        if ($this->slave === 1)
        {
            \Database\DefaultConnection::setSlaveConnection($this->mode);
        }
        else
        {
            \Database\DefaultConnection::set($this->mode);
        }

        $app = App::getFacadeRoot();

        $app['rzp.mode'] = $this->mode;

        //
        // 2. Initializes repository and esRepository corresponding to the
        //    input entity.
        //

        $this->trace = $app['trace'];

        $repoManager = $app['repo'];

        $this->repo = $repoManager->{$this->entity};

        $this->esRepo = $this->repo->setAndGetEsRepoIfExist();

        if ($this->esRepo === null)
        {
            throw new LogicException('EsSync: Es repo not found.');
        }

        // set primary key as 'id' if not provided in the options
        $this->primaryKey = ($this->primaryKey) ?? 'id';

        //
        // 3. If index prefix is passed in option, will use that. Useful in
        //    cases of re-indexing with mapping changes. We create the new index,
        //    do indexing and then switch and then again do delta indexing.
        //

        if ($this->indexPrefix !== null)
        {
            $this->esRepo->setIndexName($this->indexPrefix);
        }
    }

    /**
     * Fetches all entities in batch and indexes them to es.
     *
     */
    protected function doIndexing()
    {
        $afterId = $this->afterId ?: null;

        while (true)
        {
            $this->info('Offset (Row ID): ' . $afterId);

            $documents = $this->repo
                              ->findManyForIndexing(
                                    $afterId,
                                    $this->take,
                                    $this->startAt,
                                    $this->endAt);

            $documents = array_values(array_filter($documents));

            if (count($documents) === 0)
            {
                break;
            }

            $afterId = end($documents)[$this->primaryKey];

            $this->info('Filtering..');

            $documents = array_filter(
                            $documents,
                            function ($doc)
                            {
                                return $this->repo->isEsSyncNeeded(EsRepository::CREATE, $doc);
                            });

            $filteredCount = count($documents);

            $this->info('Filtered docs count: ' . $filteredCount);

            if ($filteredCount === 0)
            {
                continue;
            }

            try
            {
                $response = $this->esRepo->bulkUpdate($documents);

                $this->info('Took: ' . $response['took'] . 'ms');

                $this->trace->info(
                    TraceCode::ES_INDEX_PROGRESS,
                    [
                        'offset'  => $afterId,
                        'took_ms' => $response['took'],
                    ]);
            }
            catch(\Throwable $e)
            {
                $this->error($e);

                $this->trace->traceException($e);
            }
        }
    }
}
