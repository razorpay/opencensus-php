<?php


namespace RZP\Models\Batch\Processor;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Type;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class IinMcMastercard extends BaseProcessor
{
    protected $processor;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->processor = new IIN\Batch\McMastercard;
    }

    protected function processEntry(array &$entry)
    {

        $this->processor->preprocess($entry);

        $this->processor->process();

        $entry[Batch\Header::STATUS]  = Batch\Status::SUCCESS;
    }
}