<?php

namespace RZP;

class Pricing extends Entity
{   
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

    protected function getEntityUrl()
    {
        $fullClassName = get_class($this);
        $pos = strrpos($fullClassName, '\\');
        $className = substr($fullClassName, $pos + 1);
        $className = lcfirst($className);
        return $className.'/';
    }
}