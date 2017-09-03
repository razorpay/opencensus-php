<?php

namespace RZP\Models\Base;

use RZP\Exception;

class Entity extends \RZP\Base\EloquentEx
{
    /**
     * Soft deletable models will have this attribute.
     */
    const DELETED_AT = 'deleted_at';

    /**
     * Keeps the current action value here to be set by the entity updater
     */
    protected $auditAction = [];

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

    protected function hasAttribute($key)
    {
        return (array_key_exists($key, $this->attributes) === true);
    }

    public function fromDateTime($value)
    {
        return $value;
    }

    /**
     * @override
     *
     * Ref: Illuminate/Database/Eloquent/Concerns/HasAttributes.php
     *
     * Laravel internally does some mutation, formatting and assumes
     * stuffs based on returned field list of this method. We haven't
     * been using any of those and so returning empty on this method call
     * intentionally.
     *
     * We do have $dates attribute and we use that in following two places:
     * - Base/EloquentEx.php: to serialize attributes with $dates fields casted to int,
     * - Base/PublicEntity.php: formatDateFieldsForReport(): to format $dates fields
     *   converted to a uniform string format across reports.
     *
     * @return array
     */
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

    public function setAuditAction(array $action)
    {
        $this->auditAction = $action;
    }

    public function getAuditAction()
    {
        return $this->auditAction;
    }

    public function resetAuditAction()
    {
        $this->auditAction = [];
    }
}
