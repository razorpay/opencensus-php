<?php

namespace App\RZP\Mock;

class Transaction extends MockEntity
{
    /**
     * @param $id Merchant id
     */
    public function fetch($id)
    {
        $this->mock(self::$mockData['transaction']);

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(self::$mockData['transaction']);

        return $this;
    }
}
