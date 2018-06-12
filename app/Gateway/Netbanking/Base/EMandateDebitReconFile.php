<?php

namespace RZP\Gateway\Netbanking\Base;

use Illuminate\Http\UploadedFile;
use Razorpay\Trace\Logger as Trace;

use RZP\Base\RuntimeManager;
use RZP\Exception;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Processor;
use RZP\Trace\TraceCode;

class EMandateDebitReconFile extends Base\Core
{
    use FileHandlerTrait;

    protected $gateway;

    protected $fileContents;

    public function __construct()
    {
        parent::__construct();

        $this->increaseAllowedSystemLimits();
    }

    public function process(array $input)
    {
        $file = $input['file'];

        $this->fileContents = $this->parseFile($file);

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

    /**
     * Parses given file and returns the entries array
     *
     * @param UploadedFile $file
     * @return array
     * @throws Exception\LogicException
     */
    protected function parseFile(UploadedFile $file): array
    {
        $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

        switch ($ext)
        {
            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                return $this->parseExcelSheets($file);

            case FileStore\Format::TXT:
                //
                // We use standard separator | for txt, if needs this
                // can be made configurable. But for now it's ok.
                //
                return $this->parseTextFile($file, '|');

            case FileStore\Format::CSV:
                return $this->parseTextFile($file, ',');

            default:
                throw new Exception\LogicException("Extension not handled: {$ext}");
        }
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
     * Override this in the child class to get the corresponding API error codes
     *
     * @param string $errorDescription Error description from gateway
     *
     * @return string Mapped API error code
     * @throws Exception\LogicException
     */
    protected function getApiErrorCode(string $errorDescription): string
    {
        throw new Exception\LogicException('Child class must implement this method');
    }
}
