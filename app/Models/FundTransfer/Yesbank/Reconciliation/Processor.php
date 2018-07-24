<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Base\Reconciliation\AttemptProcessor as BaseProcessor;

class Processor extends BaseProcessor
{
    protected static $channel = Channel::YESBANK;

    protected function getRowProcessorNamespace($row)
    {
        return __NAMESPACE__ . '\\StatusProcessor';
    }
}
