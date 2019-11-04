<?php

namespace RZP\Models\Batch\Processor;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;

class TerminalCreation extends Base
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

    protected function processEntry(array & $entry)
    {
        $merchantId         = $entry[Batch\Header::TERMINAL_CREATION_MERCHANT_ID];
        $gateway            = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY];
        $gatewayMerchantId  = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID];
        $gatewayMerchantId2 = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID2];
        $gatewayTerminalId  = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_TERMINAL_ID];
        $gatewayAccessCode  = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_ACCESS_CODE];
        $mcMPAN             = $entry[Batch\Header::TERMINAL_CREATION_MC_MPAN];
        $visaMPAN           = $entry[Batch\Header::TERMINAL_CREATION_VISA_MPAN];
        $rupayMPAN          = $entry[Batch\Header::TERMINAL_CREATION_RUPAY_MPAN];
        $vpa                = $entry[Batch\Header::TERMINAL_CREATION_VPA];
        $category           = $entry[Batch\Header::TERMINAL_CREATION_CATEGORY];
        $card               = $entry[Batch\Header::TERMINAL_CREATION_CARD];
        $netbanking         = $entry[Batch\Header::TERMINAL_CREATION_NETBANKING];
        $emandate           = $entry[Batch\Header::TERMINAL_CREATION_EMANDATE];
        $emi                = $entry[Batch\Header::TERMINAL_CREATION_EMI];
        $upi                = $entry[Batch\Header::TERMINAL_CREATION_UPI];
        $bankTransfer       = $entry[Batch\Header::TERMINAL_CREATION_BANK_TRANSFER];
        $aeps               = $entry[Batch\Header::TERMINAL_CREATION_AEPS];
        $emiDuration        = $entry[Batch\Header::TERMINAL_CREATION_EMI_DURATION];
        $type               = $entry[Batch\Header::TERMINAL_CREATION_TYPE] ?? [];
        $tpv                = $entry[Batch\Header::TERMINAL_CREATION_TPV];
        $mode               = $entry[Batch\Header::TERMINAL_CREATION_MODE];
        $international      = $entry[Batch\Header::TERMINAL_CREATION_INTERNATIONAL];
        $corporate          = $entry[Batch\Header::TERMINAL_CREATION_CORPORATE];
        $expected           = $entry[Batch\Header::TERMINAL_CREATION_EXPECTED];
        $emiSubvention      = $entry[Batch\Header::TERMINAL_CREATION_EMI_SUBVENTION];
        $gatewayAcquirer    = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_ACQUIRER];
        $networkCategory    = $entry[Batch\Header::TERMINAL_CREATION_NETWORK_CATEGORY];
        $currency           = $entry[Batch\Header::TERMINAL_CREATION_CURRENCY];
        $accountNumber      = $entry[Batch\Header::TERMINAL_CREATION_ACCOUNT_NUMBER];
        $ifscCode           = $entry[Batch\Header::TERMINAL_CREATION_IFSC_CODE];
        $cardlessEMI        = $entry[Batch\Header::TERMINAL_CREATION_CARDLESS_EMI];
        $payLater           = $entry[Batch\Header::TERMINAL_CREATION_PAYLATER];
        $enabled            = $entry[Batch\Header::TERMINAL_CREATION_ENABLED];
        $capability         = $entry[Batch\Header::TERMINAL_CREATION_CAPABILITY];

        $createTerminalParams = [
            Terminal\Entity::MERCHANT_ID                => $merchantId,
            Terminal\Entity::GATEWAY                    => $gateway,
            Terminal\Entity::TYPE                       => $type,
            Terminal\Entity::GATEWAY_MERCHANT_ID        => $gatewayMerchantId,
            Terminal\Entity::GATEWAY_MERCHANT_ID2       => $gatewayMerchantId2,
            Terminal\Entity::GATEWAY_TERMINAL_ID        => $gatewayTerminalId,
            Terminal\Entity::GATEWAY_ACCESS_CODE        => $gatewayAccessCode,
            Terminal\Entity::MODE                       => $mode,
            Terminal\Entity::MC_MPAN                    => $mcMPAN,
            Terminal\Entity::VISA_MPAN                  => $visaMPAN,
            Terminal\Entity::RUPAY_MPAN                 => $rupayMPAN,
            Terminal\Entity::VPA                        => $vpa,
            Terminal\Entity::CATEGORY                   => $category,
            Terminal\Entity::CARD                       => $card,
            Terminal\Entity::NETBANKING                 => $netbanking,
            Terminal\Entity::EMANDATE                   => $emandate,
            Terminal\Entity::EMI                        => $emi,
            Terminal\Entity::UPI                        => $upi,
            Terminal\Entity::BANK_TRANSFER              => $bankTransfer,
            Terminal\Entity::AEPS                       => $aeps,
            Terminal\Entity::EMI_DURATION               => $emiDuration,
            Terminal\Entity::TPV                        => $tpv,
            Terminal\Entity::INTERNATIONAL              => $international,
            Terminal\Entity::CORPORATE                  => $corporate,
            Terminal\Entity::EXPECTED                   => $expected,
            Terminal\Entity::EMI_SUBVENTION             => $emiSubvention,
            Terminal\Entity::GATEWAY_ACQUIRER           => $gatewayAcquirer,
            Terminal\Entity::NETWORK_CATEGORY           => $networkCategory,
            Terminal\Entity::CURRENCY                   => $currency,
            Terminal\Entity::ACCOUNT_NUMBER             => $accountNumber,
            Terminal\Entity::IFSC_CODE                  => $ifscCode,
            Terminal\Entity::CARDLESS_EMI               => $cardlessEMI,
            Terminal\Entity::PAYLATER                   => $payLater,
            Terminal\Entity::ENABLED                    => $enabled,
            Terminal\Entity::CAPABILITY                 => $capability,
        ];

        // Unsetting empty or null values
        $createTerminalParams = array_filter($createTerminalParams);

        $terminal = $this->terminalService->createTerminal($merchantId, $createTerminalParams);

        $entry[Batch\Header::STATUS]            = Batch\Status::SUCCESS;

        $entry[Batch\Header::TERMINAL_ID]       = $terminal[Terminal\Entity::ID];

    }

    public function getOutputFileHeadings(): array
    {
        $headerRule = $this->batch->getValidator()->getHeaderRule();

        return Batch\Header::getHeadersForFileTypeAndBatchType($this->outputFileType, $headerRule);
    }

    protected function sendProcessedMail()
    {
        return;
    }
}