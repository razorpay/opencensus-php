<?php

namespace App\RZP;

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
        return parent::all($options);
    }

    public function generateNetBankingExcel($params)
    {
        $relativeUrl = $this->getEntityUrl() . "netbanking/excel";

        return $this->request('POST', $relativeUrl, $params);
    }
}
