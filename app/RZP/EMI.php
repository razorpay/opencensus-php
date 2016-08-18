<?php

namespace App\RZP;

class EMI extends Entity
{
    public function create($params = null)
    {
        return parent::create($params);
    }

    public function delete()
    {
        $url = $this->getEntityUrl() . $this->id;
        return $this->request('DELETE', $url);
    }

    public function setId($emiId)
    {
        $this->attributes['id'] = $emiId;
        return $this;
    }

    /**
     * This is faster, cleaner and easier than using
     * reflection. Needed because of lowercase requirement
     * @return [type] [description]
     */
    protected function getEntityUrl()
    {
        return 'emi/';
    }
}
