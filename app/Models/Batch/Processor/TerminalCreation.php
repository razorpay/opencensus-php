<?php

namespace RZP\Models\Batch\Processor;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Pricing\Repository;
use RZP\Models\Pricing\Type;
use RZP\Models\Terminal;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Batch\Processor\AESCrypto;

class TerminalCreation extends Base
{
    /**
     * @var Terminal\Service
     */
    protected $terminalService;

    public function __construct(Entity $batch = null)
    {
        if ($batch != null)
        {
            parent::__construct($batch);
        }

        $this->terminalService = new Terminal\Service;
    }

    public function processEntry(array & $entry)
    {
        // decrypt sensitive headers (check comments on $haveSensitiveData in Batch/Type.php)
        $this->decryptSensitiveHeaders($entry);

        $merchantId         = $entry[Batch\Header::TERMINAL_CREATION_MERCHANT_ID];
        $gateway            = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY];
        $gatewayMerchantId  = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID];
        $gatewayMerchantId2 = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID2];
        $gatewayTerminalId  = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_TERMINAL_ID];
        $gatewayAccessCode  = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_ACCESS_CODE];
        $plan_name          = $entry[Batch\Header::TERMINAL_CREATION_PLAN_NAME];

        $gatewayTerminalPassword  = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD];
        $gatewayTerminalPassword2 = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD2];
        $gatewaySecureSecret      = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_SECURE_SECRET];
        $gatewaySecureSecret2     = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_SECURE_SECRET2];
        $gatewayReconPassword     = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_RECON_PASSWORD];
        $gatewayClientCertificate = $entry[Batch\Header::TERMINAL_CREATION_GATEWAY_CLIENT_CERTIFICATE];

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
        $omnichannel        = $entry[Batch\Header::TERMINAL_CREATION_OMNICHANNEL];
        $bankTransfer       = $entry[Batch\Header::TERMINAL_CREATION_BANK_TRANSFER];
        $aeps               = $entry[Batch\Header::TERMINAL_CREATION_AEPS];
        $emiDuration        = $entry[Batch\Header::TERMINAL_CREATION_EMI_DURATION];
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
        $status             = $entry[Batch\Header::TERMINAL_CREATION_STATUS];
        $capability         = $entry[Batch\Header::TERMINAL_CREATION_CAPABILITY];

        $currency = empty($currency) ? null : explode(', ', trim($currency));

        $plan_name = blank($plan_name) ? null : $plan_name;

        $type = $this->getTerminalTypeParam($entry[Batch\Header::TERMINAL_CREATION_TYPE]);

        $createTerminalParams = [
            Terminal\Entity::MERCHANT_ID                => trim($merchantId),
            Terminal\Entity::GATEWAY                    => trim($gateway),
            Terminal\Entity::TYPE                       => $type,
            Terminal\Entity::GATEWAY_MERCHANT_ID        => trim($gatewayMerchantId),
            Terminal\Entity::GATEWAY_MERCHANT_ID2       => trim($gatewayMerchantId2),
            Terminal\Entity::GATEWAY_TERMINAL_ID        => trim($gatewayTerminalId),
            Terminal\Entity::GATEWAY_ACCESS_CODE        => trim($gatewayAccessCode),
            Terminal\Entity::GATEWAY_TERMINAL_PASSWORD  => trim($gatewayTerminalPassword),
            Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2 => trim($gatewayTerminalPassword2),
            Terminal\Entity::GATEWAY_SECURE_SECRET      => trim($gatewaySecureSecret),
            Terminal\Entity::GATEWAY_SECURE_SECRET2     => trim($gatewaySecureSecret2),
            Terminal\Entity::GATEWAY_RECON_PASSWORD     => trim($gatewayReconPassword),
            Terminal\Entity::GATEWAY_CLIENT_CERTIFICATE => trim($gatewayClientCertificate),
            Terminal\Entity::MODE                       => $mode,
            Terminal\Entity::MC_MPAN                    => trim($mcMPAN),
            Terminal\Entity::VISA_MPAN                  => trim($visaMPAN),
            Terminal\Entity::RUPAY_MPAN                 => trim($rupayMPAN),
            Terminal\Entity::VPA                        => trim($vpa),
            Terminal\Entity::CATEGORY                   => trim($category),
            Terminal\Entity::CARD                       => $card,
            Terminal\Entity::NETBANKING                 => $netbanking,
            Terminal\Entity::EMANDATE                   => $emandate,
            Terminal\Entity::EMI                        => $emi,
            Terminal\Entity::UPI                        => $upi,
            Terminal\Entity::OMNICHANNEL                => $omnichannel,
            Terminal\Entity::BANK_TRANSFER              => $bankTransfer,
            Terminal\Entity::AEPS                       => $aeps,
            Terminal\Entity::EMI_DURATION               => $emiDuration,
            Terminal\Entity::TPV                        => $tpv,
            Terminal\Entity::INTERNATIONAL              => $international,
            Terminal\Entity::CORPORATE                  => $corporate,
            Terminal\Entity::EXPECTED                   => $expected,
            Terminal\Entity::EMI_SUBVENTION             => trim($emiSubvention),
            Terminal\Entity::GATEWAY_ACQUIRER           => trim($gatewayAcquirer),
            Terminal\Entity::NETWORK_CATEGORY           => trim($networkCategory),
            Terminal\Entity::CURRENCY                   => $currency,
            Terminal\Entity::ACCOUNT_NUMBER             => trim($accountNumber),
            Terminal\Entity::IFSC_CODE                  => trim($ifscCode),
            Terminal\Entity::CARDLESS_EMI               => $cardlessEMI,
            Terminal\Entity::PAYLATER                   => $payLater,
            Terminal\Entity::ENABLED                    => $enabled,
            Terminal\Entity::STATUS                     => empty($status) ? "activated" : $status,
            Terminal\Entity::CAPABILITY                 => $capability,
            Terminal\Entity::PLAN_NAME                  => $plan_name,
        ];

        // Unsetting empty or null values
        $createTerminalParams = array_filter($createTerminalParams, function($v) {
            return ($v !== null) && ($v !== '');
        });

        $terminal = $this->terminalService->createTerminal($merchantId, $createTerminalParams);

        $entry[Batch\Header::STATUS]            = Batch\Status::SUCCESS;

        $entry[Batch\Header::TERMINAL_ID]       = $terminal[Terminal\Entity::ID];

    }

    public function decryptSensitiveHeaders(array & $entry)
    {
        foreach(Batch\Header::HEADER_MAP[Batch\Type::TERMINAL_CREATION][Batch\Header::SENSITIVE_HEADERS] as $sensitiveHeader)
        {
            if( (isset($entry[$sensitiveHeader]) === true) and
                (empty($entry[$sensitiveHeader]) === false) )
            {
                $aesCrypto =  new AESCrypto();

                $entry[$sensitiveHeader] = $aesCrypto->decryptString($entry[$sensitiveHeader]);
            }
        }
    }

    public function getTerminalTypeParam($typeString)
    {
        if (empty($typeString) === true)
        {
            return null;
        }

        $typeItems = explode(', ', trim($typeString));

        $type = [];
        foreach($typeItems as $typeItem)
        {
            $type[$typeItem] = '1';
        }

        return $type;
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
