<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use Config;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Rbl\Status;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;

class EnachRbl extends Base
{
    protected $gateway = Gateway::ENACH_RBL;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        return [
            'payment_id'    => $row[Headings::REFNO],
            'amount'        => $row[Headings::AMOUNT],
            'umrn'          => $row[Headings::UMRN],
            'status'        => $row[Headings::STATUS],
            'error_message' => $row[Headings::REASON_DESCRIPTION],
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
        return Status::isDebitSuccess($content['status']);
    }

    protected function getErrorDescription(array $content)
    {
        return $content['error_message'];
    }
}
