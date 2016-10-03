<?php

namespace RZP\Models\Base;

use RZP\Error\ErrorCode;
use RZP\Exception;

class Entity extends EloquentEx
{
    protected function asDateTime($value)
    {
        //
        // If this value is an integer, we will assume
        // it is a UNIX timestamp's value and return as it is.
        // Otherwise we will call the parent function to handle it.
        //
        if ((ctype_digit($value)) or
            (is_int($value)))
        {
            return (int) $value;
        }
        else
        {
            return parent::asDateTime($value);
        }
    }

    /**
     * It takes the input array used to create the entity
     * and replaces blanks '' with null
     *
     * @param  array    $input Takes input array by ref
     */
    protected function modifyInputRemoveBlanks(& $input)
    {
        foreach ($input as $key => $value)
        {
            if ($input[$key] === '')
            {
                $input[$key] = null;
            }
        }
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return snake_case($this->getEntityName()) . '_id';
    }

    /**
     * Since namespacing is ?/?/Entity
     * This returns the second last segment of the namespace
     * which is also the entity type is.
     *
     * eg: for RZP/Models/Transaction/Entity class, it returns Transaction
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entity;
    }

    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function setId($id)
    {
        UniqueIdEntity::verifyUniqueId($id, true);

        return $this->setAttribute('id', $id);
    }

    protected function isDateCastable($key)
    {
        return false;
    }

    public function fromDateTime($value)
    {
        return $value;
    }

    public function getDates()
    {
        return [];
    }

    public function getCreatedAtAttribute()
    {
        return (int) $this->attributes[self::CREATED_AT];
    }

    public function getUpdatedAtAttribute()
    {
        return (int) $this->attributes[self::UPDATED_AT];
    }
}
