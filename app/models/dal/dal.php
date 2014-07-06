<?php

namespace Models\DAL;

use EE\Error\ErrorCode;
use EE\Exception;

class DAL extends \Eloquent
{
    /**
     * Indicates if the primary key is uuid.
     *
     * @var bool
     */
    public $uuid = false;

    protected $sign = '';

    protected $entity = '';

    public function toArrayPublic()
    {
        $array = $this->toArray();

        $array['id' ] = $this->sign . '-' . $array['id'];

        $array['entity'] = $this->entity;

        return $array;
    }

    public function getVisible()
    {
        return $this->visible;
    }

    public function getHidden()
    {
        return $this->hidden;
    }

    public function getGuarded()
    {
        return $this->guarded;
    }

    public static function createOrFail(array $attributes)
    {
        if ( ! (NULL === $model = static::create($attributes))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $attributes,
                'operation' => 'create');

        throw new Exception\DbQueryException($e);
    }

    public static function findOrFail($id, $columns = array('*'))
    {
        if ( ! (NULL === $model = static::find($id, $columns))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\DbQueryException($e);
    }

    public static function findOrFailPublic($id, $columns = array('*'))
    {
        if ( ! (NULL === $model = static::find($id, $columns))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
    }

    protected function getDateFormat()
    {
        return 'U';
    }

    protected function asDateTime($value)
    {
        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and return as it is. Otherwise we will call the parent function to handle it.
        if (is_numeric($value))
        {
            return $value;
        }
        else
        {
            return parent::asDateTime($value);
        }
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
