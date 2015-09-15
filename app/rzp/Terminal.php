<?php

namespace RZP;

class Terminal extends Entity
{
    /**
     * @param $id Merchant id
     */
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all();
    }

    public function delete($id)
    {
        $relativeUrl = $this->getEntityUrl() . $id;
        return $this->request('DELETE', $relativeUrl);
    }

    public function edit($id, $input)
    {
        $relativeUrl = $this->getEntityUrl() . $id;
        return $this->request('PUT', $relativeUrl, $input);
    }
}
