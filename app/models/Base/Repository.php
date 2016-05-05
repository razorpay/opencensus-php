<?php

namespace Models\Base;

use Constants\Table;
use DB;
use Illuminate\Support\Facades\App;

class Repository extends \Razorpay\Spine\Repository
{
    protected $app;

    protected $db;

    protected $auth;

    public function __construct()
    {
        parent::__construct();

        $this->app = App::getFacadeRoot();

        $this->auth = $this->app['basicauth'];
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        return $this->newQuery()->findOrFailPublic($id, $columns);
    }

    protected function processDbQueryFailure($operation, $attributes = null)
    {
        $e = $this->getExceptionDataArray($operation, $attributes);

        $this->throwException($e);
    }

    protected function getExceptionDataArray($operation, $attributes = null)
    {
        $e = array(
                'model' => get_class($this),
                'operation' => $operation,
                'attributes' => $attributes);

        return $e;
    }

    protected function throwException(array $e)
    {
        throw new Exception\DbQueryException($e);
    }

    public function isTransactionActive()
    {
        $env = $this->app->environment();

        if ($env === 'testing')
        {
            return ($this->db->transactionLevel() > 1);
        }

        return ($this->db->transactionLevel() > 0);
    }

    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        return $this->fetchBetweenTimestamp($merchantId, $from, $to);
    }

    public function fetchBetweenTimestamp($merchantId, $from, $to)
    {
        return $this->newQuery()
                    ->betweenTime($from, $to)
                    ->merchantId($merchantId)
                    ->get();
    }

    public function saveOrFailTemp($entity, array $options = array())
    {
        // Gets the attributes which are being newly inserted or updated.
        $dirty = $entity->getDirty();

        // Saves the entity in MySql.
        $entity->saveOrFail($options);

        // Commenting this out for now. Will uncomment later.
        //$this->saveInEs($entity, $dirty);
    }

    protected function saveInEs($entity, $dirty)
    {
        try
        {
            // Checks if whitelisted es params is set. If yes, checks if $dirty contains any of them.
            if ((isset($this->esWhitelistedParams) === true) and
                (empty(array_intersect(array_keys($dirty), $this->esWhitelistedParams)) === false))
            {
                $parentNamespace = $this->getParentNamespace();

                $esRepoClassPath = $parentNamespace . '\\' . 'EsRepository';

                // Saving the entity in ES.
                // Will throw an error if the class does not exist.
                $esRepo = new $esRepoClassPath;
                $esType = $this->getEsType($parentNamespace);
                $esRepo->storeEntity($esType, $entity);
            }
        }
        catch (\Exception $e)
        {
            $this->app['exception.handler']->traceException($e);
        }
    }

    protected function getParentNamespace()
    {
        return join('\\', explode('\\', get_called_class(), -1));
    }

    // Override this method in entity/repository in case the type name is different for that entity.
    public function getEsType($parentNamespace)
    {
        $parentNamespaceArray = explode('\\', $parentNamespace);

        $className = strtoupper(end($parentNamespaceArray));

        $typeName = constant("Constants\\Table::$className");

        return $typeName;
    }
}
