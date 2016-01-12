<?php

namespace Models\Emi;

use Models\Base;

class Service extends Base\Service
{
    public function fetch()
    {
        $emiOptions = $this->repo->all();

        return $emiOptions->toArrayPublic();
    }

    public function addEmiOptions($input)
    {
        $emi = (new Emi\Core)->addEmiOption();

        return $emi->toArrayPublic();
    }
}