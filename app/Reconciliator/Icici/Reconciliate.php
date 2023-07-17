<?php

namespace RZP\Reconciliator\Icici;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
    public function getFileType(string $mimeType): string
    {
        return FileProcessor::CSV;
    }
}

