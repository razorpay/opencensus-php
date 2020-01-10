<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Mozart\NetbankingKvb\ClaimFields;

class Kvb extends Base
{
    const FILE_NAME = 'KVB_STATUS_';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::KVB_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_KVB;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $count = 1;

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d-M-Y');

            $date = strtoupper($date);

            $formattedData[] = [
                ClaimFields::SR_NO                 => $count++,
                ClaimFields::MERCHANT_CODE         => "RAZORPAY",
                ClaimFields::TRANSACTION_DATE      => $date,
                ClaimFields::PAYMENT_ID            => $row['payment']['id'],
                ClaimFields::ACCOUNT_NUMBER        => $row['gateway']['data']['account_number'],
                ClaimFields::PAYMENT_AMOUNT        => $this->getFormattedAmount($row['payment']['amount']),
                ClaimFields::BANK_REFERENCE_NUMBER => $row['gateway']['data']['bank_payment_id'],
                ClaimFields::STATUS                => $this->getstatus($row['payment']),
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('YdmHis');

        return static::FILE_NAME . $dateTime;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function fetchGatewayEntities($paymentIds)
    {
        return $this->repo->mozart->fetchByPaymentIdsAndAction($paymentIds, Action::AUTHORIZE);
    }

    protected function getStatus($payment)
    {
        $status = $payment['status'];
        $refund_status = $payment['refund_status'];

        if ($refund_status == 'partial')
        {
            return "partial refunded";
        }
        elseif ($refund_status == 'full')
        {
            return "refunded";
        }
        if ($status == 'captured' || $status == 'authorized')
        {
            return "successful";
        }

        return "failed";
    }
}
