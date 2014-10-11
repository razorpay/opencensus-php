<?php

namespace RZP\Mock;

use RZP\Entity;

class MockEntity extends Entity
{
    protected static $mockData;

    public function __construct()
    {
        static::$mockData = MockData::getData();
    }

    protected function mock($data, $attributes = array())
    {
        $this->fill($data);

        foreach ($attributes as $key => $value)
        {
            $this->$key = $value;
        }
    }

    protected function mockCollection($data)
    {
        $collection = array(
            'entity'    => 'collection',
            'count'     => 1,
            'data'      => array($data)
        );

        $this->fill($collection);
    }
}