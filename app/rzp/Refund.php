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

    public function generateHDFCRefundsExcel()
    {
        $relativeUrl = $this->getEntityUrl() . 'hdfcnb/generate';

        return $this->request('GET', $relativeUrl);
    }
}
