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
            Terminal\Entity::MERCHANT_ID                => trim($merchantId),
            Terminal\Entity::GATEWAY                    => trim($gateway),
            Terminal\Entity::TYPE                       => $type,
            Terminal\Entity::GATEWAY_MERCHANT_ID        => trim($gatewayMerchantId),
            Terminal\Entity::GATEWAY_MERCHANT_ID2       => trim($gatewayMerchantId2),
            Terminal\Entity::GATEWAY_TERMINAL_ID        => trim($gatewayTerminalId),
            Terminal\Entity::GATEWAY_ACCESS_CODE        => trim($gatewayAccessCode),
            Terminal\Entity::MODE                       => trim($mode),
            Terminal\Entity::MC_MPAN                    => trim($mcMPAN),
            Terminal\Entity::VISA_MPAN                  => trim($visaMPAN),
            Terminal\Entity::RUPAY_MPAN                 => trim($rupayMPAN),
            Terminal\Entity::VPA                        => trim($vpa),
            Terminal\Entity::CATEGORY                   => trim($category),
            Terminal\Entity::CARD                       => trim($card),
            Terminal\Entity::NETBANKING                 => trim($netbanking),
            Terminal\Entity::EMANDATE                   => trim($emandate),
            Terminal\Entity::EMI                        => trim($emi),
            Terminal\Entity::UPI                        => trim($upi),
            Terminal\Entity::BANK_TRANSFER              => trim($bankTransfer),
            Terminal\Entity::AEPS                       => trim($aeps),
            Terminal\Entity::EMI_DURATION               => trim($emiDuration),
            Terminal\Entity::INTERNATIONAL              => trim($international),
            Terminal\Entity::CORPORATE                  => trim($corporate),
            Terminal\Entity::EXPECTED                   => trim($expected),
            Terminal\Entity::EMI_SUBVENTION             => trim($emiSubvention),
            Terminal\Entity::GATEWAY_ACQUIRER           => trim($gatewayAcquirer),
            Terminal\Entity::NETWORK_CATEGORY           => trim($networkCategory),
            Terminal\Entity::CURRENCY                   => trim($currency),
            Terminal\Entity::ACCOUNT_NUMBER             => trim($accountNumber),
            Terminal\Entity::IFSC_CODE                  => trim($ifscCode),
            Terminal\Entity::CARDLESS_EMI               => trim($cardlessEMI),
            Terminal\Entity::PAYLATER                   => trim($payLater),
            Terminal\Entity::ENABLED                    => trim($enabled),
            Terminal\Entity::CAPABILITY                 => trim($capability),
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