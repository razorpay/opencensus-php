<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Base\Reconciliation\Processor as BaseProcessor;

class Processor extends BaseProcessor
{
    protected static $channel = Channel::ICICI;
}