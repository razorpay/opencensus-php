<?php

namespace Models\DAL;

use Models\Manager\UniqueId;

class UniqueIdDal extends DAL
{

    /**
     * Indicates if the IDs are uuid.
     *
     * @var bool
     */
    protected $uniqueId = true;

    public $incrementing = false;

    protected $secureUid = false;

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