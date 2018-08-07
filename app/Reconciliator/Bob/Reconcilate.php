<?php

namespace RZP\Reconciliator\Bob;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{

    public function getDelimiter()
    {
        return ',';
    }

    /**
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}
