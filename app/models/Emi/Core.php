<?php

namespace Models\Emi;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Core extends Base\Core
{
    public function __construct()
    {
        $this->repo = new Repository;
    }

    public function addEmiOption($input)
    {
        $emi = (new Entity)->build($input);
                
        $this->repo->saveOrFail($emi);

        return $emi;
    }
}