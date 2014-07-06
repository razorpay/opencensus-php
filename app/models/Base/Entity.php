<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class Entity extends \Eloquent
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
