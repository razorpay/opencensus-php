<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use App;

use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Base\EsRepository;
use Razorpay\Trace\Logger as Trace;

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
                            {--index=    : ES index name (eg. beta_api_invoice_test) }
                            {--entity=   : Entity name (eg. item|merchant) }
                            {--skip=0    : Skip offset (eg. skip first 100 rows) }
                            {--take=5000 : Take count (eg. 1000 at a time) }
                            {--start_at= : Start value(epoch) for time range query }
                            {--end_at=   : End value(epoch) for time range query }';

    protected $description = 'Indexes entity into es for search purposes.';

    protected $slave;
    protected $mode;
    protected $index;
    protected $entity;
    protected $skip;
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
        $this->index   = $this->option('index');
        $this->entity  = $this->option('entity');
        $this->skip    = (int) $this->option('skip');
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

        //
        // There are some entities for which es updates has to be synced
        // to both _test and _live suffixed index, similar to how it happens
        // in database. But in one time indexing we intentionally don't want
        // to do that.
        //

        if (method_exists($this->esRepo, 'disableSync') === true)
        {
            $this->esRepo->disableSync();
        }

        // 3. If index name is passed in option, will use that. Useful in
        //    cases of first time indexing with mapping changes. We create the
        //    new index do indexing and then switch and then again do delta indexing.

        if ($this->index !== null)
        {
            $this->esRepo->setIndexNameByValue($this->index);
        }
    }

    /**
     * Fetches all entities in batch and indexes them to es.
     *
     */
    protected function doIndexing()
    {
        $skip = $this->skip;

        while (true)
        {
            $this->info('Offset: ' . $skip);

            $documents = $this->repo
                              ->findManyForIndexing(
                                    $skip,
                                    $this->take,
                                    $this->startAt,
                                    $this->endAt);

            $skip += $this->take;

            if (count($documents) === 0)
            {
                break;
            }

            $this->info('Filtering..');

            $documents = array_filter(
                            $documents,
                            function (& $doc)
                            {
                                return $this->repo
                                            ->isEsSyncNeeded(EsRepository::CREATE, $doc);
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
