<?php

namespace Models\Base;

use DB;
use EE\Error\ErrorCode;
use EE\Exception;

class Repository
{
    protected $entity = 'Entity';

    protected $repo = null;

    public function __construct()
    {
        if ($this->repo === null)
            $this->repo = '\Models\\' . $this->entity . '\Entity';
    }

    public function createOrFail(array $attributes)
    {
        $repo = $this->repo;

        return $repo::createOrFail($attributes);
    }

    public function create(array $attributes)
    {
        $repo = $this->repo;

        return $repo::create($attributes);
    }

    public function findOrFail($id, $columns = array('*'))
    {
        $repo = $this->repo;

        return $repo::findOrFail($id, $columns);
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        $repo = $this->repo;

        return $repo::findOrFailPublic($id, $columns);
    }

    public function find($id, $columns = array('*'))
    {
        $repo = $this->repo;

        return $repo::find($id, $columns);
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     */
    public function saveOrFail($entity, array $options = array())
    {
        $entity->saveOrFail($options);
    }

    public function save($entity, array $options = array())
    {
        $saved = $entity->save($options);

        return $saved;
    }

    public function pushOrFail($entity)
    {
        $entity->pushOrFail();
    }

    public function reload(& $entity)
    {
        $repo = $this->repo;

        $reloadedEntity = $repo::findOrFail($entity->getKey());

        $attributes = $reloadedEntity->getAttributes();

        $entity->setRawAttributes($attributes, true);
    }

    public function beginTransaction()
    {
        DB::beginTransaction();

        return $this;
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param  Closure  $callback
     * @return mixed
     *
     * @throws \Exception
     */
    public function transaction(\Closure $callback)
    {
        $result = DB::transaction($callback);

        return $result;
    }
}
