<?php

namespace RZP\Models\Base;

use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use App;

class EsRepository extends \Razorpay\Spine\Repository
{
    protected $esDao;
    protected $indexName;

    protected $trace;

    const MAX_JOB_ATTEMPTS = 10;
    // This is in seconds
    const JOB_RELEASE_WAIT = 120;

    public function __construct()
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->indexName = $app['config']->get('database.es_index');

        $this->esDao = new EsDao();
    }

    public function fetchNotes($typeName, $params, $merchantId)
    {
        $params['merchant_id'] = $merchantId;

        $entities = new PublicCollection;

        // Returns all the entity IDs matching the notes search.
        $entityIds = $this->esDao->getNotes($typeName, $params);

       if (empty($entityIds) === false)
        {
            // Get the entity data from MySQL.
            $entities = $this->newQuery()->findOrFailPublic($entityIds, array('*'));

            // MySQL should contain all entities present in ES.
            if ($entities->count() !== count($entityIds))
            {
                throw new Exception\ServerErrorException(
                    'Did not find corresponding entity data in MySQL' ,
                    ErrorCode::SERVER_ERROR_MYSQL_ENTRY_NOT_FOUND,
                    ['es_entity_ids' => $entityIds]);
            }
        }

        return $entities;
    }

    // Currently storing only notes and merchant ID.
    public function storeEntity($typeName, $entityArray, $esDao = null)
    {
        $params['notes'] = $entityArray['notes'];
        $params['merchant_id'] = $entityArray['merchant_id'];
        $params['entity_id'] = $entityArray['id'];

        if ($esDao === null)
        {
            $esDao = $this->esDao;
        }

        $esDao->storeNotes($typeName, $params);
    }


    // Called through queue
    // Called through the entity repository
    public function fireStoreEntity($job, $data)
    {
        $esType = $data['es_type'];
        $entityArray = $data['entity'];
        $mode = $data['mode'];

        try
        {
            $this->trace->info(
                TraceCode::ES_SAVE_REQUEST,
                $data);

            // Creating a new EsDao object because,
            // in the queue flow, the mode needs to be passed
            // to the constructor.
            $esDao = new EsDao($mode);
            // Calls the entity es repository
            $this->storeEntity($esType, $entityArray, $esDao);

            $job->delete();
        }
        catch (\Exception $ex)
        {
            $data['job_attempts'] = $job->attempts();

            $this->trace->error(
                TraceCode::ES_SAVE_FAILED,
                [
                    $data
                ]
            );

            $this->trace->traceException($ex);

            if ($job->attempts() > self::MAX_JOB_ATTEMPTS)
            {
                $job->delete();
            }
            else
            {
                $job->release(self::JOB_RELEASE_WAIT);
            }
        }
    }
}