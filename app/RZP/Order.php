<?php

namespace App\RZP;

class Order extends Entity
{
    /**
     * @param $id Order id
     */
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all($options);
    }

}
