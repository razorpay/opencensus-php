<?php

namespace App\RZP;

class Org extends Entity
{
    public function fetch($id)
    {
    	$relativeUrl = $this->getEntityUrl(). $id .'/self';
        return $this->request('GET', $relativeUrl);
    }
}
