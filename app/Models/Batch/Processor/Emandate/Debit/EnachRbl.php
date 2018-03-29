<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use Config;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Rbl\Status;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;

class EnachRbl extends Base
{
    protected $gateway = Gateway::ENACH_RBL;

    protected function parseExcelSheets($filePath)
    {
        Config::set('excel.import.force_sheets_collection', true);
        Config::set('excel.import.heading', 'original');
        Config::set('excel.import.startRow', 2);

        $sheets = $this->parseExcelFile($filePath);

        Config::set('excel.import.startRow', 1);

        $hasSingleSheet  = (count($sheets) === 1);
        $errorMessage    = 'Sheets keys: ' . implode('.', array_keys($sheets));

        assertTrue($hasSingleSheet, $errorMessage);

        //
        // We use head() instead of integer index as sheets might be
        // an associative array.
        //
        return head($sheets);
    }

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        return [
            'payment_id' => $row[Headings::REFNO],
            'amount'     => $row[Headings::AMOUNT],
            'umrn'       => $row[Headings::UMRN],
            'status'     => $row[Headings::CLG_STATUS],
        ];
    }

    protected function getPayment(array $content)
    {
        $paymentId = $content['payment_id'];

        $umrn = $content['umrn'];

        $payment = $this->repo->payment->fetchDebitEnachPaymentPendingAuth(
                                                                $this->gateway,
                                                                $paymentId,
                                                                $umrn);

        return $payment;
    }

    protected function updateGatewayPayment(array $content)
    {
        return;
    }

    protected function getGatewayAttributes(array $parsedData): array
    {
        return [];
    }

    protected function isAuthorized(array $content): bool
    {
        return ($content['status'] !== Status::DEBIT_REJECT);
    }
}
