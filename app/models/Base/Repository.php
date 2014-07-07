<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class Repository
{
    protected $model;

    protected $repo = 'Entity';

    public function __construct()
    {
        ;
    }

    public function createOrFail(array $attributes)
    {
        $repo = $get_called_class();

        if ( ! (NULL === $model = $repo::create($attributes))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $attributes,
                'operation' => 'create');

        throw new Exception\DbQueryException($e);
    }

    public static function findOrFail($id, $columns = array('*'))
    {
        $repo = $get_called_class();

        if ( ! (NULL === $model = $repo::find($id, $columns))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\DbQueryException($e);
    }

    public static function findOrFailPublic($id, $columns = array('*'))
    {
        $repo = $get_called_class();

        if ( ! (NULL === $model = $repo::find($id, $columns))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     */
    public function saveOrFail($entity)
    {
        $saved = $entity->save($options);

        if ($saved === true)
            return;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $this->attributes,
                'operation' => 'save');

        throw new Exception\DbQueryException($e);
    }

    public function pushOrFail($entity)
    {
        $pushed = $entity->push();

        if ($pushed === true)
            return;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $this->attributes,
                'operation' => 'push');

        throw new Exception\DbQueryException($e);
    }
}
