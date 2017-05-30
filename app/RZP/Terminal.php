<?php

namespace App\RZP;

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

    public function edit($id, $params)
    {
        $relativeUrl = $this->getEntityUrl() . $id;

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function unassignSubMerchant($id, $merchantId)
    {
        $relativeUrl = $this->getEntityUrl() . $id . '/merchants/' . $merchantId;

        return $this->request('DELETE', $relativeUrl);
    }
}
