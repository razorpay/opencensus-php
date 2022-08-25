<?php

namespace RZP\Models\Gateway\File\Instrumentation\Emandate;


use RZP\Models\Gateway\File\Instrumentation;

abstract class Base extends Instrumentation\Base
{
    public function filterFiles(& $files)
    {
        return $files;
    }

    public function parseTextRow($row)
    {
        return $row;
    }

    public function processInput($data, $entries)
    {
        throw new \BadMethodCallException();
    }
}
