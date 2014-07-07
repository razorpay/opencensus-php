<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class Repository
{
    protected $entity = 'Entity';

    protected $repo = null;

    public function __construct()
    {
        if ($this->repo === null)
            $this->repo = '\\Models\\'.$this->entity.'\\Entity';
    }

    public function createOrFail(array $attributes)
    {
        $repo = $this->repo;

        if ( ! (NULL === $model = $repo::create($attributes))) return $model;

        $e = array(
                'model' => $repo,
                'attributes' => $attributes,
                'operation' => 'create');

        throw new Exception\DbQueryException($e);
    }

    public function create(array $attributes)
    {
        $repo = $this->repo;

        return $repo::create($attributes);
    }

    public function findOrFail($id, $columns = array('*'))
    {
        $repo = $this->repo;

        if ( ! (NULL === $model = $repo::find($id, $columns))) return $model;

        $e = array(
                'model' => $repo,
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\DbQueryException($e);
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        $repo = $this->repo;

        if ( ! (NULL === $model = $repo::find($id, $columns))) return $model;

        $e = array(
                'model' => $repo,
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
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
        $saved = $this->save($entity, $options);

        if ($saved === true)
            return;

        $e = array(
                'model' => get_class($entity),
                'attributes' => $entity->getAttributes(),
                'operation' => 'save');


        throw new Exception\DbQueryException($e);
    }

    public function save($entity, array $options = array())
    {
        $saved = $entity->save($options);

        return $saved;
    }

    public function pushOrFail($entity)
    {
        $pushed = $entity->push();

        if ($pushed === true)
            return;

        $e = array(
                'model' => get_class($entity),
                'attributes' => $entity->getAttributes(),
                'operation' => 'push');

        throw new Exception\DbQueryException($e);
    }
}
