<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Mozart\NetbankingIbk\ClaimFields;

class Ibk extends NetbankingBase
{
    const FILE_NAME = 'Claim_{$date}_IndianBank-NetBanking';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::IBK_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_IBK;
    const CITY      = 'Bangalore';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $paymentAmount = $refundAmount = $refundCount = 0;

        $date = Carbon::createFromTimestamp($data[0]['payment']['created_at'], Timezone::IST)->format('m/d/Y');

        foreach ($data as $row)
        {
            $paymentAmount += $row['payment']->getAmount();
            $refundAmount  += $row['payment']->getAmountRefunded();

            if ($refundAmount > 0)
            {
                $refundCount++;
            }
        }

        $totalAmount = $paymentAmount - $refundAmount;

        $formattedData[] = [
            ClaimFields::SR_NO             => '1',
            ClaimFields::SUMMARY_ID        => "-",
            ClaimFields::BANK_MERCHANT_ID  => $data[0]['terminal']['gateway_merchant_id'],
            ClaimFields::MERCHANT_NAME     => '',
            ClaimFields::ACCOUNT_DETAILS   => $this->fetchBankAccountDetails(),
            ClaimFields::CITY              => self::CITY,
            ClaimFields::DATE              => $date,
            ClaimFields::NUMBER_OF_TXNS    => (string)count($data),
            ClaimFields::TOTAL_AMOUNT      => $this->getFormattedAmount($paymentAmount),
            ClaimFields::NUMBER_OF_REFUNDS => (string)$refundCount,
            ClaimFields::REFUND_AMOUNT     => $this->getFormattedAmount($refundAmount),
            ClaimFields::NET_AMOUNT        => $this->getFormattedAmount($totalAmount),
        ];

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return strtr(self::FILE_NAME, ['{$date}' => $date]);
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function fetchBankAccountDetails()
    {
        $config = $this->config->get('nodal.axis');

        return 'accountNumber : ' . $config['account_number'] . "\n" . 'accountName : Razorpay Software Private Limited'
            . "\n" . 'ifsc : ' . $config['ifsc_code'] . "\n" . 'bankName : Axis Bank Ltd';
    }
}
