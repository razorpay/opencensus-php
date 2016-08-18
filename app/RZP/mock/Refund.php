<?php

namespace App\RZP\Mock;

class Refund extends MockEntity
{
    /**
     * @param $id Merchant id
     */
    public function fetch($id)
    {
        $this->mock(self::$mockData['refund']);

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(self::$mockData['refund']);

        return $this;
    }
}
