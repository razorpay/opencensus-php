<?php

namespace Models\DAL;

use Exceptions\DbQueryException;

class DAL extends \Eloquent
{

    /**
     * Indicates if the primary key is uuid.
     *
     * @var bool
     */
    public $uuid = false;

    
    /**
     * should be default but that's
     * php keyword. It returns those attributes
     * which are defined in 'visible' and not 
     * defined in 'hidden'. At a time only one of
     * 'visible' or 'hidden' is defined.
     */
    const THEDEFAULT = 0x0;

    /**
     * Same as THEDEFAULT, except that it also
     * includes 'appends' attributes.
     */
    const FIELDS = 0x1;

    /**
     * All fields, irrespective of 'hidden' or 
     * 'visible'. Does not include 'appends' 
     * attributes.
     */
    const ALL_FIELDS = 0x2;

    /**
     * Returns 'appends' attributes
     */
    const APPENDS = 0x4;

    /**
     * Return object properties as array
     *
     * @return array
     */
    public function toArrayEx($flag = 0x0)
    {
        $array = array();

        if (($flag & static::FIELDS) or
            ($flag & static::THEDEFAULT))
        {
            $array = $this->getArrayableAttributes();
        }
        else if ($flag & static::ALL_FIELDS)
        {
            $array = $this->data;
        }

        if (($flag & static::APPENDS) or
            ($flag & static::THEDEFAULT))
        {
            $array = array_merge($array, $this->getAppends());
        }

        return $array;
    }

    /**
     * 
     */
    public function getAppends()
    {
        $appends = array();

        //
        // Here we will grab all of the appended, calculated fields to this object
        // as these fields are not really in the fields array, but are run
        // when we need to array or JSON the object for convenience to the coder.
        // 

        foreach ($this->appends as $key)
        {
            $appends[$key] = $this->mutateAttribute($key, null);
        }

        return $appends;
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

        throw new DbQueryException($e);
    }

    public static function findOrFail2($id, $columns = array('*'))
    {
        if ( ! (NULL === $model = static::find($id, $columns))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $id,
                'operation' => 'find');

        throw new DbQueryException($e);
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
}
