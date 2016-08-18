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

    public function payments()
    {
        $url = $this->getEntityUrl() . $this->id . '/payments';
        return $this->request('GET', $url);
    }

    public function setId($id)
    {
        $this->attributes['id'] = $id;
        return $this;
    }

}
