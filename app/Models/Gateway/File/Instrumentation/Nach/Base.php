<?php

namespace RZP\Models\Gateway\File\Instrumentation\Nach;


use RZP\Models\Gateway\File\Instrumentation;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

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
