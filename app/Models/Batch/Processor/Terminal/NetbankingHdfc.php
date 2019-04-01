<?php

namespace RZP\Models\Batch\Processor\Terminal;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class NetbankingHdfc extends BaseProcessor
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

        $merchantId         = trim($entry[Batch\Header::HDFC_NB_MERCHANT_ID]);
        $gatewayMerchantId  = trim($entry[Batch\Header::HDFC_NB_GATEWAY_MERCHANT_ID]);
        $networkCategory    = trim($entry[Batch\Header::HDFC_NB_CATEGORY]);
        $tpv                = $entry[Batch\Header::HDFC_NB_TPV] ?? Terminal\TpvType::NON_TPV_ONLY;

        $createTerminalParams = [
            Terminal\Entity::MERCHANT_ID          => $merchantId,
            Terminal\Entity::MODE                 => Terminal\Mode::DUAL,
            Terminal\Entity::NETBANKING           => '1',
            Terminal\Entity::GATEWAY              => Constants\Entity::NETBANKING_HDFC,
            Terminal\Entity::GATEWAY_MERCHANT_ID  => $gatewayMerchantId,
            Terminal\Entity::GATEWAY_ACQUIRER     => Payment\Gateway::ACQUIRER_HDFC,
            Terminal\Entity::CARD                 => 0,
            Terminal\Entity::TPV                  => (int) $tpv,
            Terminal\Entity::NETWORK_CATEGORY     => $networkCategory,
            Terminal\Entity::TYPE                 => [
                Terminal\Type::NON_RECURRING      => '1',
            ],
        ];

        try
        {
            $terminal = $this->terminalService->createTerminal($merchantId, $createTerminalParams);

            $entry[Batch\Header::STATUS]                 = Batch\Status::SUCCESS;

            $entry[Batch\Header::HDFC_NB_TERMINAL_ID]    = $terminal[Terminal\Entity::ID];
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
