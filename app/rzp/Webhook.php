<?php

namespace RZP;

class Webhook extends Entity
{
    public function create($params = null)
    {
        return parent::create($params);
    }

    public function fetch($id = null)
    {
        return parent::all();
    }

    public function edit($params)
    {
        $relativeUrl = $this->getEntityUrl();

        return $this->request('PUT', $relativeUrl, $params);
    }
}
