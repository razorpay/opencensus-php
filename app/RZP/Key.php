<?php

namespace App\RZP;

class Key extends Entity
{
    public function fetch($id)
    {
        $this->id = $id;

        return $this;
    }

    public function all($options = array())
    {
        $relativeUrl = 'merchants/'.$this->merchant_id.'/'.$this->getEntityUrl();

        return $this->request('GET', $relativeUrl, $options);
    }
}
