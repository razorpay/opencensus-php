<?php

namespace RZP;

class Transaction extends Entity
{
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all();
    }

    public function generateReport($params)
    {
        $relativeUrl = $this->getEntityUrl(). 'report';

        return $this->request('GET', $relativeUrl, $params);
    }
}