<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use App;

/**
 * Input:
 * - Mode
 * - Model name
 *
 * Flow:
 * - Ensures index exists for given model
 * - Reads all data from mysql and indexes into es
 *
 * TODO:
 * - Add logging/tracing and debug lines
 */
class Index extends Command
{
    protected $signature = 'rzp:index
                            {--mode=test : Database mode the command will run in (test|live)}
                            {--model=    : Model name (eg. Invoice|Merchant) }';

    protected $description = 'Indexes model data into ES.';

    protected $mode;
    protected $model;

    public function fire()
    {
        $this->setOptions();

        \Database\DefaultConnection::set($this->mode);

        $this->app  = App::getFacadeRoot();

        $this->initRepo();

        $this->doIndexing();
    }

    protected function setOptions()
    {
        $this->mode  = $this->option('mode');
        $this->model = $this->option('model');
    }

    protected function initRepo()
    {
        //
        // Gets EsRepo instanse of model
        //

        $esRepoPath = 'RZP\\Models\\' . $this->model . '\\EsRepository';

        $this->esRepo = new $esRepoPath;

        $indexName = $this->mode . '_' . camel_case($this->model);

        $this->esRepo->setIndexName($indexName);

        //
        // Gets entity of model
        //

        $this->repo = $this->app['repo'];

        $accessor = snake_case($this->model);

        $this->repo = $this->repo->$accessor;
    }

    protected function doIndexing()
    {
        $this->esRepo->createIndexIfNotExists();

        $skip = 0;
        $take = 100;

        while (true)
        {
            $indexedFields = $this->esRepo->getFields();

            $collection = $this->repo->fetchForIndexing($skip, $take, $indexedFields);

            if ($collection->count() === 0)
            {
                break;
            }

            $serialized = $collection->toArray();

            $this->bulkUpdate($serialized);

            $skip += $take;
        }
    }

    protected function bulkUpdate(array $documents)
    {
        try
        {
            $this->esRepo->bulkUpdate($documents);
        }
        catch(\Exception $ex)
        {
        }
    }
}
