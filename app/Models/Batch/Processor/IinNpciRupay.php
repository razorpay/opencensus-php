<?php

namespace RZP\Models\Batch\Processor;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Type;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class IinNpciRupay extends BaseProcessor
{
    protected $processor;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->processor = new IIN\Batch\NpciRupay;
    }

    protected function processEntry(array &$entry)
    {
        $header = array_keys($entry)[0];

        $row = array_values($entry)[0];

        unset($entry[$header]);

        $entry[Batch\Header::IIN_NPCI_RUPAY_ROW] = $row;

        $this->processor->preprocess($entry);

        $this->processor->process();

        $entry[Batch\Header::STATUS]  = Batch\Status::SUCCESS;
    }
}
