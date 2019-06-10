<?php

namespace RZP\Models\Batch\Processor;

use RZP\Constants;
use RZP\Exception\BaseException;
use RZP\Models\Batch;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Processor\Base as BaseProcessor;
use RZP\Models\Batch\Type;
use RZP\Models\Payment;
use RZP\Models\Terminal;


class SubmerchantAssign extends BaseProcessor
{
    /**
     * @var Terminal\Service
     */
    protected $terminalService;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->terminalService = new Terminal\Service;
    }

    protected function processEntry(array &$entry)
    {
        try
        {
            $subMerchantId = trim($entry[Batch\Header::SUBMERCHANT_ID]);
            $terminalId    = trim($entry[Batch\Header::TERMINAL_ID]);

            $this->terminalService->addMerchantToTerminal($terminalId, $subMerchantId);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        }
        catch (BaseException $e)
        {
            $error = $e->getError();

            $entry[Batch\Header::STATUS]            = Batch\Status::FAILURE;
            $entry[Batch\Header::FAILURE_REASON]    = $error->getDescription();
        }
    }

    public function getOutputFileHeadings(): array
    {
        $headerRule = $this->batch->getValidator()->getHeaderRule();

        return Batch\Header::getHeadersForFileTypeAndBatchType($this->outputFileType, $headerRule);
    }
}
