<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use App;

use RZP\Trace\Trace;
use RZP\Exception\LogicException;

/**
 * Indexes entity into es for search purposes.
 * Usage: `php artisan rzp:index --mode=test --entity=invoice`
 *
 * Refer: https://github.com/razorpay/api/wiki/Making-models-in-API-searchable-via-ES
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

        // Sets database mode.
        // Gets App facade.
        // Sets App's mode.

        \Database\DefaultConnection::set($this->mode);

        $app = App::getFacadeRoot();

        $app['rzp.mode'] = $this->mode;

        // Inits repo and esRepo corresponding to the input entity.

        $this->trace = $app['trace'];

        $repoManager = $app['repo'];

        $this->repo = $repoManager->{$this->entity};

        $this->repo->setEsRepoIfExist();

        $this->esRepo = $this->repo->getEsRepo();

        if ($this->esRepo === null)
        {
            throw new LogicException('EsSync: Es repo not found.');
        }

        $this->doIndexing();
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

            $documents = $this->repo->fetchForIndexing($skip, $take);

            if (count($documents) === 0)
            {
                break;
            }

            try
            {
                $response = $this->esRepo->bulkUpdate($documents);

                $this->outputEsResponse($response);
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

    /**
     * Outputs formatted es response data to console for debugging purposes.
     *
     * @param array $response
     *
     * @return
     */
    protected function outputEsResponse(array & $response)
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

            $this->error(json_encode($errorItems));
        }
    }
}
