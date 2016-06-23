<?php

namespace Models\Card\IIN;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Card\IIN;
use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    protected $repo = null;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new IIN\Repository();
    }

    public function fetchIin($iin)
    {
        $iin = $this->repo->findOrFail($iin);

        return $iin->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $iins = $this->repo->fetch($input);

        return $iins->toArrayPublic();
    }

    public function addIin($input)
    {
        $iin = (new Entity)->build($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayPublic();
    }

    public function editIin($id, $input)
    {
        $iin = $this->repo->findOrFail($id);

        $iin->edit($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayPublic();
    }

    public function importIin($input)
    {
        $result = (new Import\XLSImporter)->import($input);

        return $result;
    }

    public function generateIinFile($input)
    {
        $result = (new Import\IinGenerator)->generate($input);

        return $result;
    }
}
