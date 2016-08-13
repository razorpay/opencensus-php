<?php

namespace App\RZP\Mock;

class Key extends MockEntity
{   
    public function create($params = null)
    {
        $this->mock(self::$mockData['key']);

        return $this;
    }

    public function roll($params = null)
    {
        $roll = array(
            'old_id'      => $this->id,
            'merchant_id' => $this->merchant_id,
            'new'         => self::$mockData['key']
        );

        $this->mock($roll);

        return $this;
    }

    public function fetch($id)
    {
        $this->id = $id;

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(self::$mockData['key']);

        return $this;
    }
}