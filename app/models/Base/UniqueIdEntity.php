<?php

namespace Models\Base;

class UniqueIdEntity extends Entity
{

    /**
     * Indicates if the IDs are Unique Id
     *
     * @var bool
     */
    protected $uniqueId = true;

    public $incrementing = false;

    protected $secureUid = false;

    public function generateId($input)
    {
        $this->setAttribute(static::ID, UniqueId::generateId());
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        $this->generateUniqueIdIfNotSet();

        $saved = parent::save($options);

        return $saved;
    }

    public function generateUniqueIdIfNotSet()
    {
        $key = $this->getKeyName();

        $value = $this->getAttribute($key);

        if ($value === null)
        {
            $value = UniqueId::generateId($this->secureUid);

            $this->setAttribute($key, $value);
        }
    }
}