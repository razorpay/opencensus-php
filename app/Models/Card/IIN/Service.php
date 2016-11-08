<?php

namespace RZP\Models\Card\IIN;

use RZP\Exception;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function fetchIin($iinId)
    {
        $iin = $this->repo->iin->findOrFail($iinId);

        return $iin->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $iins = $this->repo->iin->fetch($input);

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
        $iin = $this->repo->iin->findOrFail($id);

        $iin->edit($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayPublic();
    }

    public function importIin($input)
    {
        $result = (new Import\XLSImporter)->import($input);

        return $result;
    }

    public function importCsvIin($job, $input)
    {
        $result = (new Import\XLSImporter)->importWithoutNetwork($input);

        return $result;
    }

    public function generateIinFile($input)
    {
        $result = (new Import\IinGenerator)->generate($input);

        return $result;
    }
}
