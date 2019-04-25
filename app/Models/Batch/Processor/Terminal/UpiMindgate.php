<?php
namespace RZP\Models\Batch\Processor\Terminal;
use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class UpiMindgate extends BaseProcessor
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
        $merchantId = $entry[Batch\Header::UPI_MINDGATE_MERCHANT_ID];
        $pay        = $entry[Batch\Header::UPI_MINDGATE_PAY] ?? '0';
        $collect    = $entry[Batch\Header::UPI_MINDGATE_COLLECT] ?? '0';

        $createTerminalParams = [
            Terminal\Entity::MERCHANT_ID               => $merchantId,
            Terminal\Entity::MODE                      => Terminal\Mode::DUAL,
            Terminal\Entity::UPI                       => '1',
            Terminal\Entity::NETBANKING                => '0',
            Terminal\Entity::GATEWAY                   => Constants\Entity::UPI_MINDGATE,
            Terminal\Entity::GATEWAY_MERCHANT_ID       => $entry[Batch\Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID],
            Terminal\Entity::GATEWAY_MERCHANT_ID2      => $entry[Batch\Header::UPI_MINDGATE_VPA],
            Terminal\Entity::GATEWAY_TERMINAL_PASSWORD => $entry[Batch\Header::UPI_MINDGATE_TERMINAL_PASSWORD],
            Terminal\Entity::GATEWAY_ACQUIRER          => Payment\Gateway::ACQUIRER_HDFC,
            Terminal\Entity::CARD                      => 0,
            Terminal\Entity::TYPE                      => [
                Terminal\Type::NON_RECURRING => '1',
                Terminal\Type::PAY           => strval($pay),
                Terminal\Type::COLLECT       => strval($collect),
            ],
        ];

        try
        {
            $terminal = $this->terminalService->createTerminal($merchantId, $createTerminalParams);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;

            $entry[Batch\Header::UPI_MINDGATE_TERMINAL_ID] = $terminal[Terminal\Entity::ID];
        }
        catch (BaseException $e)
        {
            $error = $e->getError();

            $entry[Batch\Header::STATUS] = Batch\Status::FAILURE;

            $entry[Batch\Header::FAILURE_REASON] = $error->getDescription();
        }
    }

    public function getOutputFileHeadings(): array
    {
        $headerRule = $this->batch->getValidator()->getHeaderRule();

        return Batch\Header::getHeadersForFileTypeAndBatchType($this->outputFileType, $headerRule);
    }
}

