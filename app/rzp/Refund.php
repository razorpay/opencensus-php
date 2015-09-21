<?php

namespace RZP;

class Refund extends Entity
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

    public function generateNetBankingExcel($bank, $params)
    {
        // this includes date/to/from
        $query = http_build_query($params);

        $relativeUrl = $this->getEntityUrl() . "{$bank}nb/generate?$query";
        return $this->request('GET', $relativeUrl);
    }
}
