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
    const BASE_STORAGE_DIRECTORY = 'Ibk/Claim/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $count = [
            'claims'  => 0,
            'refunds' => 0,
        ];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $count['claims'] = count($data['claims']);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $date = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('m/d/Y');

        $formattedData[] = [
            ClaimFields::SR_NO             => '1',
            ClaimFields::SUMMARY_ID        => "-",
            ClaimFields::BANK_MERCHANT_ID  => $data['claims'][0]['terminal']['gateway_merchant_id'],
            ClaimFields::MERCHANT_NAME     => '',
            ClaimFields::ACCOUNT_DETAILS   => $this->fetchBankAccountDetails(),
            ClaimFields::CITY              => self::CITY,
            ClaimFields::DATE              => $date,
            ClaimFields::NUMBER_OF_TXNS    => $count['claims'],
            ClaimFields::TOTAL_AMOUNT      => $this->getFormattedAmount($amount['claims']),
            ClaimFields::NUMBER_OF_REFUNDS => $count['refunds'],
            ClaimFields::REFUND_AMOUNT     => $this->getFormattedAmount($amount['refunds']),
            ClaimFields::NET_AMOUNT        => $this->getFormattedAmount($amount['total']),
        ];

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY. strtr(self::FILE_NAME, ['{$date}' => $date]);
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
