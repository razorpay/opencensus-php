<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use RZP\Exception;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Payment;

class EMandateDebitReconFile extends Base\EMandateDebitReconFile
{
    use FileHandlerTrait;

    protected $errors = [];

    const PROCESS = 'process';
    const REJECT  = 'reject';


    protected function updatePaymentEntity(array $row)
    {
        $paymentId = trim($row[Headings::TRANSACTION_REF_NO]);

        $tokenId = trim($row[Headings::MANDATE_ID]);

        $accountNumber = trim($row[Headings::ACCOUNT_NO]);

        // Update gateway payment
        $gatewayPayment = $this->updateGatewayPayment($row);

        // Get payment
        $payment = $this->repo->payment->fetchDebitEmandatePaymentPendingAuth(
                        $this->gateway,
                        $paymentId,
                        $tokenId,
                        $accountNumber);

        // Update payment
        $this->updatePayment($gatewayPayment, $payment);
    }

    protected function updateGatewayPayment(array $row): Base\Entity
    {
        $paymentId = trim($row[Headings::TRANSACTION_REF_NO]);

        $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndActionOrFail(
                                $paymentId, GatewayAction::AUTHORIZE);

        $attrs = $this->getGatewayAttributes($row);

        $gatewayPayment->fill($attrs);

        $this->repo->netbanking->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getGatewayAttributes(array $row): array
    {
        $error = trim($row[Headings::REJECTION_REMARKS] ?: '');

        $gatewayStatus = strtolower(trim($row[Headings::STATUS] ?? ''));

        if (in_array($gatewayStatus, [self::PROCESS, self::REJECT], true) === false)
        {
            throw new Exception\GatewayErrorException(
                'Unrecognized gateway status ' . $gatewayStatus, ['row' => $row]);
        }

        return [
            'received'      => true,
            'error_message' => $error,
            'status'        => $gatewayStatus,
        ];
    }

    protected function isStatusProcess(Base\Entity $gatewayPayment): bool
    {
        return ($gatewayPayment->getStatus() === self::PROCESS);
    }

    protected function getApiErrorCode(string $errorDescription): string
    {
        return ErrorCode::getApiErrorCode($errorDescription);
    }
}
