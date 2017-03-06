<?php

namespace RZP\Models\DataStore\PrioritySet;

use RZP\Models\Base\Core;

/**
 * Defines the abstract class to be extended by child classes. Defines the contract
 * to be implemented by any PrioritySet implementation
 */
abstract class Base extends Core
{
    protected $key;

    protected $prefix;

    protected $data;

    public function getKey()
    {
        return $this->key;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getSetMembers()
    {
        if (empty($this->data) === true)
        {
            return null;
        }

        return array_keys($this->data);
    }

    public function getSetMembersWithPositiveScore()
    {
        if (empty($this->data) === true)
        {
            return null;
        }

        $result = array_keys(array_filter($this->data));

        return $result ? : null;
    }

    public function toArray()
    {
        return [
            $this->key => $this->data
        ];
    }

    abstract public function saveOrFail();

    abstract public function fetchOrFail();

    abstract public function deleteOrFail();
}
