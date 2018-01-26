<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Base\PublicCollection;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class All extends Base
{
    const EXTENSION             = FileStore\Format::CSV;
    const GATEWAY               = ''; // fetch for all gateways
    const FILE_NAME             = 'Failed_Refunds';
    const FILE_TYPE             = FileStore\Type::GATEWAY_FAILED_REFUNDS;

    const SR_NO                 = 'Sr No';
    const REFUND_ID             = 'Refund Id';
    const PAYMENT_ID            = 'Payment Id';
    const MERCHANT              = 'Merchant';
    const AMOUNT                = 'Amount';
    const PAYMENT_AMOUNT        = 'Payment Amount';
    const TOTAL_REFUNDED_AMOUNT = 'Total Refunded Amount';
    const PAYMENT_GATEWAY       = 'Payment Gateway';
    const REFUND_TYPE           = 'Refund Type';
    const GATEWAY_CAPTURED      = 'Gateway Captured';
    const ATTEMPTS              = 'Attempts';
    const STATUS                = 'Status';
    const TERMINAL_ID           = 'Terminal Id';
    const TIME_SINCE_PAID       = 'Time Since Paid';
    const PAYMENT_TIME          = 'Payment Time';
    const REFUND_TIME           = 'Refund Time';

    public function generateData(PublicCollection $refunds)
    {
        $data = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $merchant = $refund->merchant;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $payment->toArray();
            $col['merchant'] = $merchant->toArray();

            $data[] = $col;
        }

        return $data;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $paymentDate = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $timeDiff = $row['refund']['created_at'] - $row['payment']['created_at'];

            $formattedData[] = [
                self::SR_NO                 => $index + 1,
                self::REFUND_ID             => $row['refund']['id'],
                self::PAYMENT_ID            => $row['payment']['id'],
                self::MERCHANT              => $row['merchant']['name'],
                self::AMOUNT                => $this->getFormattedAmount($row['refund']['base_amount']),
                self::PAYMENT_AMOUNT        => $this->getFormattedAmount($row['payment']['base_amount']),
                self::TOTAL_REFUNDED_AMOUNT => $this->getFormattedAmount($row['payment']['base_amount_refunded']),
                self::PAYMENT_GATEWAY       => $row['payment']['gateway'],
                self::REFUND_TYPE           => $this->getRefundType($row),
                self::GATEWAY_CAPTURED      => $row['payment']['gateway_captured'],
                self::ATTEMPTS              => $row['refund']['attempts'],
                self::TERMINAL_ID           => $row['payment']['terminal_id'],
                self::TIME_SINCE_PAID       => $this->seconds2human($timeDiff),
                self::PAYMENT_TIME          => $paymentDate,
                self::REFUND_TIME           => $refundDate
            ];
        }

        return $formattedData;
    }

    protected function seconds2human(int $seconds)
    {
        $s = $seconds % 60;

        $m = floor(($seconds % 3600) / 60);

        $h = floor(($seconds % 86400) / 3600);

        $d = floor(($seconds % 2592000) / 86400);

        $M = floor($seconds / 2592000);

        $timeStr = "$s seconds";

        if ($m > 0)
        {
            $timeStr = "$m minutes";
        }

        if ($h > 0)
        {
            $timeStr = "$h hours";
        }

        if ($d > 0)
        {
            $timeStr = "$d days";
        }

        if ($M > 0)
        {
            $timeStr = "$M months";
        }

        $timeStr .= " old";

        return $timeStr;
    }

    protected function getRefundType($row)
    {
        $paymentAmount = $row['payment']['amount'];

        $refundAmount = $row['refund']['amount'];

        if ($paymentAmount === $refundAmount)
        {
            return "Full";
        }
        else if ($paymentAmount < 2 * $refundAmount)
        {
            return "Greater Than Half";
        }
        else
        {
            return "Partial";
        }
    }
}