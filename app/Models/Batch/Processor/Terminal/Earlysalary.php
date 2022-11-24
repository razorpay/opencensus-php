<?php

namespace RZP\Models\Batch\Processor\Terminal;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class Earlysalary extends BaseProcessor
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
        $gatewayTerminalPassword = $this->getGatewayPasswordFromEnv();

        $merchantId              = trim($entry[Batch\Header::EARLYSALARY_MERCHANT_ID]);
        $gatewayMerchantId       = trim($entry[Batch\Header::EARLYSALARY_GATEWAY_MERCHANT_ID]);
        $gatewayMerchantId2      = trim($entry[Batch\Header::EARLYSALARY_GATEWAY_MERCHANT_ID2]);
        $terminalCategory        = trim($entry[Batch\Header::EARLYSALARY_CATEGORY]);

        $createTerminalParams = [
            Terminal\Entity::MERCHANT_ID                => $merchantId,
            Terminal\Entity::MODE                       => Terminal\Mode::AUTH_CAPTURE,
            Terminal\Entity::CARDLESS_EMI               => 1,
            Terminal\Entity::CARD                       => 0,
            Terminal\Entity::GATEWAY_TERMINAL_PASSWORD  => $gatewayTerminalPassword,
            Terminal\Entity::GATEWAY                    => Constants\Entity::CARDLESS_EMI,
            Terminal\Entity::GATEWAY_MERCHANT_ID        => $gatewayMerchantId,
            Terminal\Entity::GATEWAY_MERCHANT_ID2       => $gatewayMerchantId2,
            Terminal\Entity::GATEWAY_ACQUIRER           => CardlessEmi::EARLYSALARY,
            Terminal\Entity::CATEGORY                   => $terminalCategory,
            Terminal\Entity::TYPE                       => [
                Terminal\Type::NON_RECURRING => '1',
            ],
        ];

        try
        {
            $terminal = $this->terminalService->createTerminal($merchantId, $createTerminalParams);

            $entry[Batch\Header::STATUS]                 = Batch\Status::SUCCESS;

            $entry[Batch\Header::EARLYSALARY_TERMINAL_ID]    = $terminal[Terminal\Entity::ID];
        }
        catch (BaseException $e)
        {
            $error = $e->getError();
            $entry[Batch\Header::STATUS]            = Batch\Status::FAILURE;
            $entry[Batch\Header::FAILURE_REASON]    = $error->getDescription();
        }
    }

    protected function getGatewayPasswordFromEnv(): string
    {
        $gatewayTerminalPassword = $this->app['config']->get('gateway.cardless_emi.live_earlysalary_terminal_password');

        return $gatewayTerminalPassword;
    }

    public function getOutputFileHeadings(): array
    {
        $headerRule = $this->batch->getValidator()->getHeaderRule();

        return Batch\Header::getHeadersForFileTypeAndBatchType($this->outputFileType, $headerRule);
    }
}
