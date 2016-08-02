<?php

namespace App\RZP;

class IIN extends Entity
{
    public function create($params = null)
    {
        return parent::create($params);
    }

    protected function getEntityUrl()
    {
        return strtolower((new \ReflectionClass($this))->getShortName()) . 's/';
    }

    public function edit($iin, $params)
    {
        $url = $this->getEntityUrl() . $iin;
        return $this->request('PUT', $url, $params);
    }
}
