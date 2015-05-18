<?php

namespace Models\Card\IIN;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Card\IIN;

class Service extends Base\Service
{
    protected $repo = null;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new IIN\Repository();
    }

    public function fetchIinDetails($id)
    {
        $iin = $this->repo->findOrFail($id);

        return $iin->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $iins = $this->repo->fetch($input);

        return $iins->toArrayPublic();
    }

    public function addIin($input)
    {
        ;
    }
}
