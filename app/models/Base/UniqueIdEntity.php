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
        $this->setAttribute(self::ID, UniqueId::generateId());
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

        parent::save($options);
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