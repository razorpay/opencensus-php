<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Exception;
use Razorpay\Trace\Logger as Trace;

use RZP\Base\RuntimeManager;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Processor;
use RZP\Trace\TraceCode;

class EMandateDebitReconFile extends Base\Core
{
    protected $gateway = Payment\Gateway::NETBANKING_HDFC;

    protected $fileContents;

    public function __construct()
    {
        parent::__construct();

        $this->increaseAllowedSystemLimits();
    }

    public function process(array $input)
    {
        $file = $input['file'];

        $this->fileContents = $this->parseExcelSheets($file);

        $response = $this->processFileContents();

        $this->trace->info(
            TraceCode::EMANDATE_DEBIT_RESPONSE,
            [
                'response' => $response,
                'gateway'  => $this->gateway
            ]
        );

        return $response;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(300);
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
                    'gateway'   => $this->gateway,
                    'row'       => $row,
                ]);

            try
            {
                $this->updatePaymentEntity($row);

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

    protected function updatePayment(Base\Entity $gatewayPayment, Payment\Entity $payment)
    {
        if ($this->isStatusProcess($gatewayPayment) === false)
        {
            return $this->processAuthorizedPayment($payment);
        }

        $this->processFailedPayment($payment, $gatewayPayment);
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

        $processor->updatePaymentAuthFailed($e);
    }

    /**
     * Override this in the child class to get the corresponding API error codes
     *
     * @param string $errorDescription Error description from gateway
     *
     * @return string Mapped API error code
     */
    protected function getApiErrorCode(string $errorDescription): string
    {
        return '';
    }
}
