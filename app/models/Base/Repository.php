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

        $this->throwException(
            get_class($entity),
            $entity->getAttributes(),
            'find');
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

        $this->throwException(
            get_class($entity),
            $entity->getAttributes(),
            'save');
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

        $this->throwException(
            get_class($entity),
            $entity->getAttributes(),
            'push');
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

    protected function throwException($model, $attr, $op)
    {
        $e = array(
            'model' => $model,
            'attributes' => $attr,
            'operation' => $op);

        throw new Exception\DbQueryException($e);
    }
}
