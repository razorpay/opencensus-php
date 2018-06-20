<?php

namespace RZP\Models\FundTransfer\Rbl\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\AttemptProcessor as BaseProcessor;
use RZP\Models\Settlement\Channel;

class Processor extends BaseProcessor
{
    protected static $channel = Channel::RBL;

    protected function getRowProcessorNamespace($row)
    {
        return __NAMESPACE__ . '\\StatusProcessor';
    }
}
