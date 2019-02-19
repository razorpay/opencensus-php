<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Vijaya extends Base
{
    static $filename = 'RazorPay-MIS';

    const EXTENSION               = FileStore\Format::XLS;
    const FILE_TYPE               = FileStore\Type::VIJAYA_NETBANKING_CLAIM;
    const GATEWAY                 = Payment\Gateway::NETBANKING_VIJAYA;

    const MERCHANT   = 'Merchant';
    const LIVE       = 'Live';
    const SERVICE    = 'Service';

    const SL_NO               = 'S.No.';
    const MERCHANT_NAME       = 'MERCHANT NAME';
    const COMPANY_PROFILE     = 'Company Profile ';
    const STATUS              = 'Status';
    const BANK_SELECTION      = 'Bank Selection';
    const TARIFF_DETAILS      = 'Tariffs Details';
    const TARIFF_TYPE         = 'Tariff type';
    const TRANSACTION_DATE    = 'Transaction date';
    const AMOUNT              = 'Amount';
    const TOTAL_COMMISSION    = 'TotalCommision';
    const BANK_COMMISSION     = 'bank Comis';
    const RAZORPAY_COMMISSION = 'RazorPay commission ';

    protected function formatDataForFile(array $data)
    {
        $merchant_data = [];

        foreach ($data as $row)
        {
            $merchant_id = $row['payment']->getMerchantId();

            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d-M-y');

            if (array_key_exists($merchant_id, $merchant_data) === true)
            {
                if (array_key_exists($date, $merchant_data[$merchant_id]['amount']) === true)
                {
                    $merchant_data[$merchant_id]['amount'][$date] = $merchant_data[$merchant_id]['amount'][$date] + $row['payment']['amount'];
                }
                else
                {
                    $merchant_data[$merchant_id]['amount'][$date] = $row['payment']['amount'];
                }
            }
            else
            {
                $merchant = $this->repo->merchant->fetchMerchantFromEntity($row['payment']);

                $merchant_data[$merchant_id] = [
                    'name' => $merchant->getFilteredDba(),
                    'amount' => [
                        $date => $row['payment']['amount']
                    ]
                ];
            }
        }

        $formattedData = [];
        $index         = 0;

        foreach ($merchant_data as $merchant)
        {
            foreach ($merchant['amount'] as $date => $amount)
            {
                $formattedData[] = [
                    self::SL_NO               => ++$index,
                    self::MERCHANT_NAME       => $merchant['name'],
                    self::COMPANY_PROFILE     => self::SERVICE,
                    self::STATUS              => self::LIVE,
                    self::BANK_SELECTION      => self::MERCHANT,
                    self::TARIFF_DETAILS      => 0,
                    self::TARIFF_TYPE         => '-',
                    self::TRANSACTION_DATE    => $date,
                    self::AMOUNT              => $this->getFormattedAmountString($amount / 100),
                    self::TOTAL_COMMISSION    => 0,
                    self::BANK_COMMISSION     => 0,
                    self::RAZORPAY_COMMISSION => 0,
                ];
            }
        }

        $formattedData[] = $this->getFinalRow($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::$filename. '-' . $date;
    }

    protected function getFinalRow($formattedData)
    {
        $total = array_reduce($formattedData, function ($sum, $row)
        {
            $sum += $row[self::AMOUNT];

            return $sum;
        });

        return [
            self::SL_NO               => '',
            self::MERCHANT_NAME       => '',
            self::COMPANY_PROFILE     => '',
            self::STATUS              => '',
            self::BANK_SELECTION      => '',
            self::TARIFF_DETAILS      => '',
            self::TARIFF_TYPE         => '',
            self::TRANSACTION_DATE    => '',
            self::AMOUNT              => $this->getFormattedAmountString($total),
            self::TOTAL_COMMISSION    => '',
            self::BANK_COMMISSION     => '',
            self::RAZORPAY_COMMISSION => '',
        ];
    }

    protected function getFormattedAmountString($amount): String
    {
        $amt = number_format($amount, 2, '.', '');

        return $amt;
    }
}
