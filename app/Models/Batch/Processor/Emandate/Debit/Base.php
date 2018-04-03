<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Processor\Processor;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    protected function processEntry(array & $entry)
    {
        $content = $this->getDataFromRow($entry);

        $this->updatePaymentEntities($content);
    }

    protected function updatePaymentEntities(array $content)
    {
        //
        // Can't put in a transaction because of webhooks and emails
        //

        // Update gateway payment
        $this->updateGatewayPayment($content);

        $payment = $this->getPayment($content);

        $this->assertAmount($payment, $content);

        // Update payment
        $this->updatePayment($payment, $content);
    }

    protected function getPayment(array $content)
    {
        $paymentId = $content['payment_id'];

        $accountNumber = $content['account_number'];

        // Get payment
        $payment = $this->repo->payment->fetchDebitEmandatePaymentPendingAuth(
                                                                    $this->gateway,
                                                                    $paymentId,
                                                                    $accountNumber);

        return $payment;
    }

    protected function assertAmount(Payment\Entity $payment, $content)
    {
        $expectedAmount = number_format($payment->getAmount() / 100, 2, '.', '');

        $actualAmount = $this->getFormattedGatewayAmount($content);

        if ($expectedAmount !== $actualAmount)
        {
            throw new Exception\LogicException(
                'Amount tampering in Emandate found.',
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED, [
                    'expected' => $expectedAmount,
                    'actual'   => $actualAmount,
                    'payment_id'    => $payment->getId(),
                ]);
        }
    }

    protected function getFormattedGatewayAmount($content)
    {
        return number_format($content['amount'], 2, '.', '');
    }

    protected function updateGatewayPayment(array $content)
    {
        $paymentId = $content['payment_id'];

        $gatewayPayment = $this->repo
                               ->netbanking
                               ->findByPaymentIdAndActionOrFail($paymentId, GatewayAction::AUTHORIZE);

        $attrs = $this->getGatewayAttributes($content);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getGatewayAttributes(array $content)
    {
        return [];
    }

    protected function updatePayment(Payment\Entity $payment, array $content)
    {
        if ($this->isAuthorized($content) === true)
        {
            return $this->processAuthorizedPayment($payment);
        }

        return $this->processFailedPayment($payment, $content);
    }

    protected function processAuthorizedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $processor = $processor->setPayment($payment);

        return $processor->processAuth($payment);
    }

    protected function processFailedPayment(Payment\Entity $payment, array $content)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $gatewayErrorDesc = $this->getErrorDescription($content);

        $errorCode = $this->getApiErrorCode($gatewayErrorDesc);

        $e = new Exception\GatewayErrorException(
            $errorCode,
            '',
            $gatewayErrorDesc,
            [
                'payment_id'         => $payment->getId(),
            ]);

        $processor = $processor->setPayment($payment);

        $processor->updatePaymentAuthFailed($e);
    }

    protected function getErrorDescription(array $content)
    {
        return null;
    }

    protected function getApiErrorCode(string $errorDescription): string
    {
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    protected function createSetOutputFileAndSave(array & $entries, string $fileType = FileStore\Type::BATCH_OUTPUT)
    {
        return;
    }

    protected function sendProcessedMail()
    {
        return;
    }
}
