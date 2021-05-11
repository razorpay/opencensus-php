<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class All extends Base
{
    const EXTENSION              = FileStore\Format::CSV;
    const GATEWAY                = ''; // fetch for all gateways
    const FILE_NAME              = 'Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::GATEWAY_FAILED_REFUNDS;
    const BASE_STORAGE_DIRECTORY = 'AllGateways/Refund/Failed/';

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
            $paymentDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST);

            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST);

            $timeDiff = $paymentDate->diffForHumans($refundDate);

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
                self::TIME_SINCE_PAID       => $timeDiff,
                self::PAYMENT_TIME          => $paymentDate->format('d/m/Y H:i:s'),
                self::REFUND_TIME           => $refundDate->format('d/m/Y H:i:s')
            ];
        }

        return $formattedData;
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

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
