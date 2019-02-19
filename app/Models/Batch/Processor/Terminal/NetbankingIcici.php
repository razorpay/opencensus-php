<?php

namespace RZP\Models\Batch\Processor\Terminal;

use RZP\Constants;
use RZP\Exception\BaseException;
use RZP\Models\Batch;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Processor\Base as BaseProcessor;
use RZP\Models\Batch\Type;
use RZP\Models\Payment;
use RZP\Models\Terminal;

class NetbankingIcici extends BaseProcessor
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
            $merchantId         = trim($entry[Batch\Header::ICIC_NB_MERCHANT_ID]);
            $gatewayMerchantId  = trim($entry[Batch\Header::ICIC_NB_GATEWAY_MID]);
            $gatewayMerchantId2 = trim($entry[Batch\Header::ICIC_NB_GATEWAY_MID2]);
            $networkCategory    = trim($entry[Batch\Header::ICIC_NB_SECTOR]);
            $subIds             = trim($entry[Batch\Header::ICIC_NB_SUB_IDS]);

            $createTerminalParams = [
                Terminal\Entity::MERCHANT_ID          => $merchantId,
                Terminal\Entity::MODE                 => Terminal\Mode::DUAL,
                Terminal\Entity::NETBANKING           => 1,
                Terminal\Entity::GATEWAY              => Constants\Entity::NETBANKING_ICICI,
                Terminal\Entity::GATEWAY_MERCHANT_ID  => $gatewayMerchantId,
                Terminal\Entity::GATEWAY_MERCHANT_ID2 => $gatewayMerchantId2,
                Terminal\Entity::GATEWAY_ACQUIRER     => Payment\Gateway::ACQUIRER_ICIC,
                Terminal\Entity::CARD                 => 0,
                Terminal\Entity::NETWORK_CATEGORY     => $networkCategory,
                Terminal\Entity::TYPE                 => [
                    Terminal\Type::NON_RECURRING      => '1',
                ],
            ];

            $terminal = $this->terminalService->createTerminal($merchantId, $createTerminalParams);

            if((isset($subIds) === true) and (trim($subIds) !== ''))
            {
                $subIdsArray = explode(',', $subIds);

                foreach ($subIdsArray as $subId)
                {
                    $this->terminalService->addMerchantToTerminal($terminal[Terminal\Entity::ID], trim($subId));
                }
            }

            $entry[Batch\Header::STATUS]              = Batch\Status::SUCCESS;
            $entry[Batch\Header::ICIC_NB_TERMINAL_ID]    = $terminal[Terminal\Entity::ID];
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
