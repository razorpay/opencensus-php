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
        if (substr(array_values($entry)[0], 0, 3) === 'TRL')
        {
            $entry[Batch\Header::STATUS]  = Batch\Status::SUCCESS;

            return;
        }

        $this->processor->preprocess($entry);

        $this->processor->process();

        $entry[Batch\Header::STATUS]  = Batch\Status::SUCCESS;
    }
}
