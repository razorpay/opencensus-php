<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use Config;
use RZP\Models\Batch;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Base\Entity;
use RZP\Gateway\Enach\Npci\Netbanking;
use RZP\Models\Merchant\RazorxTreatment;

class EnachNpciNetbanking extends Base
{
    protected $gateway = Gateway::ENACH_NPCI_NETBANKING;

    const UMRN = 'umrn';

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        return [
            self::PAYMENT_ID            => substr($row[Batch\Header::ENACH_NPCI_NETBANKING_DEBIT_PAYMENT_ID], -14),
            self::AMOUNT                => $row[Batch\Header::ENACH_NPCI_NETBANKING_DEBIT_AMOUNT],
            self::GATEWAY_RESPONSE_CODE => $row[Batch\Header::ENACH_NPCI_NETBANKING_DEBIT_STATUS],
            self::GATEWAY_ERROR_CODE    => $row[Batch\Header::ENACH_NPCI_NETBANKING_DEBIT_ERROR_CODE],
            self::GATEWAY_ERROR_MESSAGE => $row[Batch\Header::ENACH_NPCI_NETBANKING_DEBIT_ERROR_DESCRIPTION],
            self::UMRN                  => $row[Batch\Header::ENACH_NPCI_NETBANKING_DEBIT_UMRN],
        ];
    }

    protected function getPayment(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        $umrn = $content[self::UMRN];

        $payment = $this->repo->payment->fetchDebitEnachPaymentPendingAuth(
                                                       $this->gateway,
                                                       $paymentId,
                                                       $umrn
                                                    );

        return $payment;
    }

    protected function getGatewayPayment(string $paymentId)
    {
        return $this->repo
                    ->enach
                    ->findAuthorizedPaymentByPaymentId($paymentId);
    }

    protected function getGatewayAttributes(array $parsedData): array
    {
        return [
            Entity::STATUS        => $parsedData[self::GATEWAY_RESPONSE_CODE],
            Entity::ERROR_CODE    => $parsedData[self::GATEWAY_ERROR_CODE],
            Entity::ERROR_MESSAGE => $parsedData[self::GATEWAY_ERROR_MESSAGE],
        ];
    }

    protected function isAuthorized(array $content): bool
    {
        return Netbanking\DebitFileStatus::isDebitSuccess($content[self::GATEWAY_RESPONSE_CODE], $content);
    }

    protected function isRejected(array $content): bool
    {
        return Netbanking\DebitFileStatus::isDebitRejected($content[self::GATEWAY_RESPONSE_CODE], $content);
    }

    protected function getApiErrorCode(array $content): string
    {
        return NetBanking\ErrorCodes\FileBasedErrorCodes::getDebitPublicErrorCode($content);
    }
    
    protected function getNRErrorCode(array $content)
    {
        return Netbanking\ErrorCodes\NRImprovementErrorCodes::getNRErrorCodes($content);
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Batch\Header::ENACH_NPCI_NETBANKING_DEBIT_BANK_ACC]);
    }

    protected function getBankStatus($status): string
    {
        return Netbanking\DebitFileStatus::bankMappedStatus($status);
    }

    public function shouldSendToBatchService(): bool
    {
        return true;
    }
}
