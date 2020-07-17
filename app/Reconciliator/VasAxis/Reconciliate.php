<?php

namespace RZP\Reconciliator\VasAxis;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    //
    // We get total 4 sheets in the MIS file,
    // but only one sheet contains data,
    // Other sheets contain metadata info
    //
    const ACCEPTED_SHEET_NAMES = [
        'Settle Detail',
        'settle detail',
    ];

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getSheetNames(array $fileDetails = [])
    {
        return self::ACCEPTED_SHEET_NAMES;
    }

    // Used to unzip the MIS file
    public function getReconPassword($fileDetails)
    {
        return '037111004800466';
    }
}
