<?php

namespace RZP;

class Key extends Entity
{   
    public function create($params = null)
    {
        $relativeUrl = 'merchants/'.$this->merchant_id.'/'.$this->getEntityUrl();

        return $this->request('POST', $relativeUrl, $params);
    }

    public function roll($params = null)
    {
        $relativeUrl = 'merchants/'.$this->merchant_id.'/'.$this->getEntityUrl().$this->id;

        return $this->request('PUT', $relativeUrl, $params);
    }

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