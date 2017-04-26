<?php

namespace App\RZP;

class Item extends Entity
{
    public function all($options = [])
    {
        return parent::all($options);
    }

    public function create($params = [])
    {
        return parent::create($params);
    }

    public function edit($id, $params = [])
    {
        return parent::request('PATCH', $this->getEntityUrl() . $id, $params);
    }

    public function delete($id)
    {
      return parent::request('DELETE', $this->getEntityUrl() . $id);
    }
}
