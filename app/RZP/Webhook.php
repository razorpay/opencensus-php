<?php

namespace App\RZP;

class Webhook extends Entity
{
    public function create($params = null)
    {
        return parent::create($params);
    }

    public function all($options = [])
    {
        return parent::all($options);
    }

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function edit($params)
    {
        $relativeUrl = $this->getEntityUrl() . $this->id;

        return $this->request('PUT', $relativeUrl, $params);
    }
}
