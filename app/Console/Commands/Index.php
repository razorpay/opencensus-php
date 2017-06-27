<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use App;

use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Base\EsRepository;

/**
 * Indexes entity into es for search purposes.
 * Usage: `php artisan rzp:index --mode=test --entity=invoice`
 * Do -h for more options.
 *
 * Refer: https://github.com/razorpay/api/wiki/Making-models-in-API-searchable-via-ES
 *
 */
class Index extends Command
{
    protected $signature = 'rzp:index
                            {--slave=0   : Whether to use slave or master db connection? (0|1)}
                            {--mode=test : Database mode the command will run in (test|live)}
                            {--entity=   : Entity name (eg. item|merchant) }
                            {--take=5000 : Take count (eg. 1000 at a time) }
                            {--start_at= : Start value(epoch) for time range query }
                            {--end_at=   : End value(epoch) for time range query }';

    protected $description = 'Indexes entity into es for search purposes.';

    protected $slave;
    protected $mode;
    protected $entity;
    protected $take;
    protected $startAt;
    protected $endAt;

    protected $trace;
    protected $repo;
    protected $esRepo;

    public function fire()
    {
        $this->setOptions();

        $this->init();

        $this->doIndexing();
    }

    protected function setOptions()
    {
        $this->slave   = (int) $this->option('slave');
        $this->mode    = $this->option('mode');
        $this->entity  = $this->option('entity');
        $this->take    = (int) $this->option('take');
        $this->startAt = $this->option('start_at');
        $this->endAt   = $this->option('end_at');
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

        // 2. Initializes repository and esRepository corresponding to the
        //    input entity.

        $this->trace = $app['trace'];

        $repoManager = $app['repo'];

        $this->repo = $repoManager->{$this->entity};

        $this->repo->setEsRepoIfExist();

        $this->esRepo = $this->repo->getEsRepo();

        if ($this->esRepo === null)
        {
            throw new LogicException('EsSync: Es repo not found.');
        }

        // 3. Set index name in esRepo. This can be removed in next pr (Now for BC).

        $prefix = $app['config']->get('database.es_entity_index_prefix');

        $indexName = $prefix . $this->entity . '_' . $app['rzp.mode'];

        $this->esRepo->setIndexNameByValue($indexName);
    }

    /**
     * Fetches all entities in batch and indexes them to es.
     *
     */
    protected function doIndexing()
    {
        $skip = 0;

        while (true)
        {
            $this->info('Offset: ' . $skip);

            $documents = $this->repo
                              ->findManyForIndexing(
                                    $skip,
                                    $this->take,
                                    $this->startAt,
                                    $this->endAt);

            $documents = array_filter(
                            $documents,
                            function (& $doc)
                            {
                                return $this->repo
                                            ->isEsSyncNeeded(EsRepository::CREATE, $doc);
                            });

            if (count($documents) === 0)
            {
                break;
            }

            try
            {
                $response = $this->esRepo->bulkUpdate($documents);

                $this->processEsResponse($response);
            }
            catch(\Exception $e)
            {
                $this->error($e);

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::ES_BULK_UPDATE_FAILED,
                    [
                        'source'  => __CLASS__,
                        'options' => $this->option(),
                    ]);
            }

            $skip += $this->take;
        }
    }

    /**
     * Checks ES response for errors and logs error if any.
     *
     * @param array $response
     *
     */
    protected function processEsResponse(array & $response)
    {
        // By default just log time taken in writing the docs to ES
        $this->info('Took: ' . $response['took'] . 'ms');

        $errors = $response['errors'];

        // In case of errors just logging the items with error(status not in 200,201)
        if ($errors === true)
        {

            $errorItems = array_filter(
                            $response['items'],
                            function ($v)
                            {
                                return (in_array($v['index']['status'], [200, 201], true) === false);
                            });

            $this->error('Error count: ' . count($errorItems));

            $this->trace->error(
                TraceCode::ES_BULK_UPDATE_FAILED,
                [
                    'source' => __CLASS__,
                    'errors' => $errorItems,
                ]);
        }
    }
}
