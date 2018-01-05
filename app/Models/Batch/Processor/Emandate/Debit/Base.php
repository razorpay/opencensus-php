<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Exception;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Models\Batch\Processor\Base as BaseProcessor;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Processor;

class Base extends BaseProcessor
{
    protected function processEntry(array & $entry)
    {
        $parsedData = $this->getDataFromRow($entry);

        $this->updatePaymentEntities($parsedData);
    }

    /**
     * @param array $parsedData
     * @param $parsedData['payment_id']
     * @param $parsedData['account_number']
     */
    protected function updatePaymentEntities(array $parsedData)
    {
        $paymentId = $parsedData['payment_id'];

        $accountNumber = $parsedData['account_number'];

        // Update gateway payment
        $gatewayPayment = $this->updateGatewayPayment($parsedData);

        // Get payment
        $payment = $this->repo->payment->fetchDebitEmandatePaymentPendingAuth(
                        $this->gateway,
                        $paymentId,
                        $accountNumber);

        // Update payment
        $this->updatePayment($gatewayPayment, $payment);
    }

    /**
     * @param array $parsedData
     * @param $parsedData['payment_id']
     * @param $parsedData['status']
     * @param $parsedData['error_message']
     *
     * @return NetbankingEntity
     */
    protected function updateGatewayPayment(array $parsedData): NetbankingEntity
    {
        $paymentId = $parsedData['payment_id'];

        $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndActionOrFail(
            $paymentId, GatewayAction::AUTHORIZE);

        $attrs = $this->getGatewayAttributes($parsedData);

        $gatewayPayment->fill($attrs);

        $this->repo->netbanking->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function updatePayment(NetbankingEntity $gatewayPayment, Payment\Entity $payment)
    {
        if ($this->isAuthorized($gatewayPayment) === true)
        {
            return $this->processAuthorizedPayment($payment);
        }

        return $this->processFailedPayment($payment, $gatewayPayment);
    }

    protected function processAuthorizedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $processor = $processor->setPayment($payment);

        return $processor->processAuth($payment);
    }

    protected function processFailedPayment(Payment\Entity $payment, NetbankingEntity $gatewayPayment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $gatewayErrorDesc = $gatewayPayment->getErrorMessage();

        $errorCode = $this->getApiErrorCode($gatewayErrorDesc);

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

    /**
     * Child class must implement it
     *
     * @param string $errorDescription
     *
     * @return string
     */
    protected function getApiErrorCode(string $errorDescription): string
    {
        throw new \BadMethodCallException();
    }

    /**
     * Child class must implement it
     *
     * @param array $row
     *
     * @return array
     * @return array['payment_id']
     * @return array['token_id']
     * @return array['status']
     * @return array['error_message']
     * @return array['account_number']
     */
    protected function getDataFromRow(array & $row): array
    {
        throw new \BadMethodCallException();
    }

    protected function createSetOutputFileAndSave(array & $entries)
    {
        return ;
    }

    protected function sendProcessedMail()
    {
        return ;
    }
}