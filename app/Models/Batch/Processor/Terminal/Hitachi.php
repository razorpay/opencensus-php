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

class Hitachi extends BaseProcessor
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
            $merchantId        = trim($entry[Batch\Header::HITACHI_MERCHANT_ID]);
            $gatewayMerchantId = trim($entry[Batch\Header::HITACHI_MID]);
            $gatewayTerminalId = trim($entry[Batch\Header::HITACHI_TID]);
            $category          = trim($entry[Batch\Header::HITACHI_MCC]);
            $subIds            = trim($entry[Batch\Header::HITACHI_SUB_IDS]);
            $currency          = strtoupper(trim($entry[Batch\Header::HITACHI_CURRENCY]));

            $createTerminalParams = [
                Terminal\Entity::MERCHANT_ID         => $merchantId,
                Terminal\Entity::MODE                => Terminal\Mode::DUAL,
                Terminal\Entity::INTERNATIONAL       => true,
                Terminal\Entity::GATEWAY             => Constants\Entity::HITACHI,
                Terminal\Entity::GATEWAY_MERCHANT_ID => $gatewayMerchantId,
                Terminal\Entity::GATEWAY_TERMINAL_ID => $gatewayTerminalId,
                Terminal\Entity::GATEWAY_ACQUIRER    => Payment\Gateway::ACQUIRER_RATN,
                Terminal\Entity::CARD                => 1,
                Terminal\Entity::TYPE                => [
                    Terminal\Type::NON_RECURRING     => '1',
                    Terminal\Type::RECURRING_3DS     => '1',
                    Terminal\Type::RECURRING_NON_3DS => '1',
                    Terminal\Type::DEBIT_RECURRING   => '1',
                ],
                Terminal\Entity::CATEGORY            => $category,
                Terminal\Entity::CURRENCY            => $currency
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
            $entry[Batch\Header::HITACHI_TERMINAL_ID] = $terminal[Terminal\Entity::ID];
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
