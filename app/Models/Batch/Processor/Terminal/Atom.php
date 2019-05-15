<?php

namespace RZP\Models\Batch\Processor\Terminal;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class Atom extends BaseProcessor
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
        $merchantId               = trim($entry[Batch\Header::ATOM_MERCHANT_ID]);
        $gatewayMerchantId        = trim($entry[Batch\Header::ATOM_GATEWAY_MERCHANT_ID]);
        $gatewayTerminalPassword  = trim($entry[Batch\Header::ATOM_TERMINAL_PASSWORD]);
        $gatewayTerminalPassword2 = trim($entry[Batch\Header::ATOM_TERMINAL_PASSWORD2]);
        $accessCode               = trim($entry[Batch\Header::ATOM_ACCESS_CODE]);
        $secureSecret             = trim($entry[Batch\Header::ATOM_SECURE_SECRET]);
        $secureSecret2            = trim($entry[Batch\Header::ATOM_SECURE_SECRET2]);
        $networkCategory          = trim($entry[Batch\Header::ATOM_CATEGORY]);
        $nonRecurring             = trim($entry[Batch\Header::ATOM_NON_RECURRING]);

        $createTerminalParams = [
            Terminal\Entity::MERCHANT_ID                => $merchantId,
            Terminal\Entity::MODE                       => Terminal\Mode::DUAL,
            Terminal\Entity::CARD                       => 0,
            Terminal\Entity::NETBANKING                 => '1',
            Terminal\Entity::GATEWAY                    => Constants\Entity::ATOM,
            Terminal\Entity::GATEWAY_MERCHANT_ID        => $gatewayMerchantId,
            Terminal\Entity::GATEWAY_TERMINAL_PASSWORD  => $gatewayTerminalPassword,
            Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2 => $gatewayTerminalPassword2,
            Terminal\Entity::GATEWAY_ACCESS_CODE        => $accessCode,
            Terminal\Entity::NETWORK_CATEGORY           => $networkCategory,
            Terminal\Entity::GATEWAY_SECURE_SECRET      => $secureSecret,
            Terminal\Entity::GATEWAY_SECURE_SECRET2     => $secureSecret2,
            Terminal\Entity::TYPE                       => [
                Terminal\Type::NON_RECURRING => $nonRecurring,
            ],
        ];

        try
        {
            $terminal = $this->terminalService->createTerminal($merchantId, $createTerminalParams);

            $entry[Batch\Header::STATUS]                 = Batch\Status::SUCCESS;

            $entry[Batch\Header::ATOM_TERMINAL_ID]       = $terminal[Terminal\Entity::ID];
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
