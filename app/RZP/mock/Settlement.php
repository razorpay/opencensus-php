<?php

namespace App\RZP\Mock;

class Settlement extends MockEntity
{
    /**
     * @param $id Merchant id
     */
    public function fetch($id)
    {
        $this->mock(self::$mockData['settlement']);

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(self::$mockData['settlement']);

        return $this;
    }
}
