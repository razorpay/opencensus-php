<?php

namespace Models\Emi;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Core extends Base\Core
{
    public function __construct()
    {
        $this->repo = new Emi\Repository;
    }

    public function addEmiOptions($input)
    {
        $emi = (new Emi\Entity)->build($input);

        $this->repo->saveOrFail($emi);

        return $emi;
    }
}