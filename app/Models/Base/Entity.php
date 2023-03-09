<?php

namespace RZP\Models\Base;

use Config;

class Entity extends \RZP\Base\EloquentEx
{
    /**
     * Soft deletable models will have this attribute.
     */
    const DELETED_AT = 'deleted_at';

    /**
     * This column is currently used in centralised warm storage (TiDB)
     * To identify source of the entity in Re Arch flow
     */
    const RECORD_SOURCE = '_record_source';

    /**
     * If this is set to true, DualWrite trait will throw an exception unless both writes are successful
     */
    const SAVE_OPTION_RAZORPAY_API_STRICT_DUAL_WRITE = 'razorpay_api_strict_dual_write';

    /**
     * Keeps the current action value here to be set by the entity updater
     */
    protected $auditAction = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setQueryTimeoutFromConfig();
    }

    // This fetches the query timeout in config/env
    // and updates it if the value is non-null.
    private function setQueryTimeoutFromConfig()
    {
        $timeout = Config::get('database.db_mysql_query_timeout');

        if (! empty($timeout))
        {
            $this->setQueryTimeout($timeout);
        }
    }

    protected function asDateTime($value)
    {
        return parent::asDateTime($value);
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

    public function hasAttribute($key): bool
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

    /**
     * Since we have suppressed the actual getDates method to support the serialization
     * this method can work as proxy
     * @return array
     */
    public function getEntityDates()
    {
        return parent::getDates();
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
