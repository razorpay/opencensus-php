<?php

namespace RZP\Reconciliator\IndusindDebitEmi;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{

    const TX_STATUS = 'tx_status';

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }
}
