<?php

namespace App\RZP;

class Pricing extends Entity
{
    const NETWORKS_URL = 'pricing/networks';

    public function create($params = null)
    {
        return parent::create($params);
    }

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all();
    }

    public function merchants()
    {
        $entityUrl = $this->getEntityUrl().'merchants';

        return $this->request('GET', $entityUrl);
    }

    public function gateways()
    {
        $entityUrl = $this->getEntityUrl().'gateways';

        return $this->request('GET', $entityUrl);
    }

    public function fetchRule($id)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/rule/'.$id;

        return $this->request('GET', $relativeUrl);
    }

    public function createRule($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/rule';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function deleteRule($planId, $ruleId)
    {
        $relativeUrl = $this->getEntityUrl().$planId.'/rule/'.$ruleId;

        return $this->request('DELETE', $relativeUrl);
    }

    protected function getEntityUrl()
    {
        $fullClassName = get_class($this);
        $pos = strrpos($fullClassName, '\\');
        $className = substr($fullClassName, $pos + 1);
        $className = lcfirst($className);
        return $className.'/';
    }

    public function fetchPaymentNetworks()
    {
        return $this->request('GET', self::NETWORKS_URL);
    }
}
