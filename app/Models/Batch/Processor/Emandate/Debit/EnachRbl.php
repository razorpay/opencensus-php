<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use Config;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Rbl\Status;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;

class EnachRbl extends Base
{
    protected $gateway = Gateway::ENACH_RBL;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        $paymentId      = $row[Headings::REFNO];

        $umrn           = $row[Headings::UMRN];

        $amount         = $row[Headings::AMOUNT];

        $status         = $row[Headings::CLG_STATUS];

        return [
            'payment_id' => $paymentId,
            'amount'     => $amount,
            'umrn'       => $umrn,
            'status'     => $status,
        ];
    }

    /**
     * @param array $parsedData
     */
    protected function updatePaymentEntities(array $parsedData)
    {
        $paymentId = $parsedData['payment_id'];

        $umrn = $parsedData['umrn'];

        // Get payment
        $payment = $this->repo->payment->fetchDebitEnachPaymentPendingAuth(
                        $this->gateway,
                        $paymentId,
                        $umrn);

        // Update payment
        $this->updatePaymentEntity($payment, $parsedData);
    }

    protected function updatePaymentEntity(Payment\Entity $payment, $content)
    {
        if ($this->isAuthorized($content) === true)
        {
            return $this->processAuthorizedPayment($payment);
        }

        return $this->processFailedPayment($payment);
    }

    protected function processFailedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $errorCode = $this->getApiErrorCode('');

        $e = new Exception\GatewayErrorException(
                $errorCode,
                null,
                null,
                [
                    'payment_id'         => $payment->getId(),
                ]);

        $processor = $processor->setPayment($payment);

        return $processor->updatePaymentAuthFailed($e);
    }

    /**
     * @param  array   $content
     * @return boolean
     * @todo Fix the status check
     */
    protected function isAuthorized(array $content): bool
    {
        return ($content['status'] !== Status::DEBIT_REJECT);
    }

    protected function getApiErrorCode(string $errorDescription): string
    {
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    protected function parseExcelSheets($filePath)
    {
        Config::set('excel.import.force_sheets_collection', true);
        Config::set('excel.import.heading', 'original');
        Config::set('excel.import.startRow', 2);

        $sheets = $this->parseExcelFile($filePath);

        $hasSingleSheet  = (count($sheets) === 1);
        $errorMessage    = 'Sheets keys: ' . implode('.', array_keys($sheets));

        assertTrue($hasSingleSheet, $errorMessage);

        //
        // We use head() instead of integer index as sheets might be
        // an associative array.
        //
        return head($sheets);
    }
}