<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Payment\Processor\Processor;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;

class EMandateDebitReconFile extends Base\EMandateDebitReconFile
{
    use FileHandlerTrait;

    protected $fileContents;

    protected $errors = [];

    const PROCESS = 'process';
    const REJECT  = 'reject';

    public function process(array $input)
    {
        $file = $input['file'];

        $this->fileContents = $this->parseExcelSheets($file);

        $response = $this->processFileContents();

        $this->trace->info(TraceCode::EMANDATE_DEBIT_RESPONSE, $response);

        return $response;
    }

    protected function processFileContents(): array
    {
        $totalCount = count($this->fileContents);

        $processedCount = 0;

        foreach ($this->fileContents as $row)
        {
            $this->trace->info(
                TraceCode::EMANDATE_DEBIT_RECON_ROW,
                [
                    'gateway'   => 'netbanking_hdfc',
                    'row'       => $row,
                ]);

            try
            {
                $this->updatePaymentEntities($row);

                $processedCount++;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::EMANDATE_DEBIT_RECON_FAILED
                );
            }
        }

        return ['total_count' => $totalCount, 'processed_count' => $processedCount];
    }

    protected function updatePaymentEntities(array $row)
    {
        $paymentId = trim($row[Headings::TRANSACTION_REF_NO]);

        $tokenId = trim($row[Headings::MANDATE_ID]);

        $accountNumber = trim($row[Headings::ACCOUNT_NO]);

        // Update gateway payment
        $gatewayPayment = $this->updateGatewayPayment($row);

        // Get payment
        $payment = $this->repo->payment->fetchDebitEmandatePaymentPendingAuth(
                        Payment\Gateway::NETBANKING_HDFC,
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

    protected function updatePayment(Base\Entity $gatewayPayment, Payment\Entity $payment)
    {
        if ($gatewayPayment->getStatus() !== self::PROCESS)
        {
            return $this->processAuthorizedPayment($payment);
        }

        return $this->processFailedPayment($payment, $gatewayPayment);
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

    protected function processAuthorizedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $processor = $processor->setPayment($payment);

        return $processor->processAuth($payment);
    }

    protected function processFailedPayment(Payment\Entity $payment, Base\Entity $gatewayPayment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $gatewayErrorDesc = $gatewayPayment->getErrorMessage();

        $errorCode = ErrorCode::getApiErrorCode($gatewayErrorDesc);

        $e = new Exception\GatewayErrorException(
            $errorCode,
            '',
            $gatewayErrorDesc,
            [
                'payment_id'         => $payment->getId(),
                'gateway_payment_id' => $gatewayPayment->getId(),
            ]);

        $processor = $processor->setPayment($payment);

        return $processor->updatePaymentAuthFailed($e);
    }
}
