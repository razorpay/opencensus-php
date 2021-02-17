<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Processor\Processor;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Batch\Processor\Emandate\Base as BaseProcessor;

class Base extends BaseProcessor
{
    const PAYMENT_ID            = 'payment_id';
    const ACCOUNT_NUMBER        = 'account_number';
    const GATEWAY_RESPONSE_CODE = 'gateway_response_code';
    const AMOUNT                = 'amount';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const GATEWAY_ERROR_CODE    = 'gateway_error_code';
    const GATEWAY_ERROR_MESSAGE = 'gateway_error_message';

    protected function processEntry(array & $entry)
    {
        $content = $this->getDataFromRow($entry);

        try
        {
            $this->updatePaymentEntities($content);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        }
        catch (\Throwable $ex)
        {
            unset($content[self::ACCOUNT_NUMBER]);

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::EMANDATE_DEBIT_RESPONSE_ERROR,
                [
                    'gateway' => $this->gateway,
                    'content' => $content,
                ]
            );

            throw $ex;
        }
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
        $paymentId = $content[self::PAYMENT_ID];

        $accountNumber = $content[self::ACCOUNT_NUMBER];

        // Get payment
        $payment = $this->repo->payment->fetchDebitEmandatePaymentPendingAuth(
                                                                    $this->gateway,
                                                                    $paymentId,
                                                                    $accountNumber);

        return $payment;
    }

    /**
     * @param Payment\Entity $payment
     * @param $content
     * @throws Exception\LogicException
     */
    protected function assertAmount(Payment\Entity $payment, $content)
    {
        $expectedAmount = number_format($payment->getAmount() / 100, 2, '.', '');

        $actualAmount = $this->getFormattedGatewayAmount($content);

        if ($expectedAmount !== $actualAmount)
        {
            throw new Exception\LogicException(
                'Amount tampering in Emandate found.',
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
                [
                    'expected'   => $expectedAmount,
                    'actual'     => $actualAmount,
                    'payment_id' => $payment->getId(),
                ]);
        }
    }

    protected function getFormattedGatewayAmount($content)
    {
        return number_format($content['amount'], 2, '.', '');
    }

    protected function updateGatewayPayment(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        $gatewayPayment = $this->getGatewayPayment($paymentId);

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
        // Possible status from gateway:
        // 1.Success, 2.Failure, 3.Pending
        if ($this->isAuthorized($content) === true)
        {
            // handle already processed
            if ($payment->hasBeenAuthorized() === true)
            {
                $this->trace->info(TraceCode::PAYMENT_ALREADY_AUTHORIZED, ['payment_id' => $payment->getId()]);
            }
            else
            {
                $this->processAuthorizedPayment($payment);
            }
        }
        else if ($this->isRejected($content) === true)
        {
            // We do not check for already processed here, since we can update the error code of the payment
            $this->processFailedPayment($payment, $content);
        }
    }

    protected function processAuthorizedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $processor = $processor->setPayment($payment);

        $data = $processor->processAuth($payment);

        $this->reconcileEntity($payment);

        return $data;
    }

    protected function processFailedPayment(Payment\Entity $payment, array $content)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $errorCode = $this->getApiErrorCode($content);

        $e = new Exception\GatewayErrorException(
            $errorCode,
            $content[self::GATEWAY_ERROR_CODE] ?? null,
            $content[self::GATEWAY_ERROR_MESSAGE] ?? null,
            [
                'payment_id' => $payment->getId(),
            ]);

        $processor = $processor->setPayment($payment);

        $processor->updatePaymentAuthFailed($e);
    }

    protected function getApiErrorCode(array $content): string
    {
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
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

    protected function getGatewayPayment(string $paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndActionOrFail($paymentId, GatewayAction::AUTHORIZE);
    }

    // Should be overridden in child class to handle pending status if applicable
    protected function isRejected(array $content): bool
    {
        return ($this->isAuthorized($content) === false);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('2048M');
    }
}
