<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;
use Illuminate\Database\Eloquent\Builder;

class BuilderEx extends Builder
{
    public static function createOrFail(array $attributes)
    {
        if ( ! (NULL === $model = static::create($attributes))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $attributes,
                'operation' => 'create');

        throw new Exception\DbQueryException($e);
    }

    public function findOrFail($id, $columns = array('*'))
    {
        if ( ! is_null($model = $this->find($id, $columns))) return $model;

        $e = array(
                'model' => get_class($this->model),
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\DbQueryException($e);
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        if ( ! is_null($model = $this->find($id, $columns))) return $model;

        $e = array(
                'model' => get_class($this->model),
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     */
    public function saveOrFail(array $options = array())
    {
        $saved = parent::save($options);

        if ($saved === true)
            return;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $this->attributes,
                'operation' => 'save');

        throw new Exception\DbQueryException($e);
    }

    public function pushOrFail()
    {
        $pushed = parent::push();

        if ($pushed === true)
            return;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $this->attributes,
                'operation' => 'push');

        throw new Exception\DbQueryException($e);
    }
}
