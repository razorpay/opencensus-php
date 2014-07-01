<?php

namespace Models\DAL;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class UuidDAL extends DAL
{

    /**
     * Indicates if the IDs are uuid.
     *
     * @var bool
     */
    public $uuid = true;

    public $incrementing = false;

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        $this->generateUuidIfNotSet();

        parent::save($options);
    }

    public function generateUuidIfNotSet()
    {
        $key = $this->getKeyName();

        $value = $this->getAttribute($key);

        if ($value === null)
        {
            $value = str_replace("-", "", Uuid::uuid1());

            $this->setAttribute($key, $value);
        }
    }
}