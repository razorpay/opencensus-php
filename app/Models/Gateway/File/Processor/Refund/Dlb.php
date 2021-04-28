<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Reconciliator\NetbankingDlb\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Dlb extends Base
{
    use FileHandler;

    const FILE_NAME              = 'DhanlaxmiBank_RazorPG-Refund';
    const EXTENSION              = FileStore\Format::XLS;
    const FILE_TYPE              = FileStore\Type::DLB_NETBAKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_DLB;
    const GATEWAY_CODE           = IFSC::DLXB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    protected $type              = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Dlb/Refund/Netbanking/';

    public function __construct()
    {
        parent::__construct();

        $this->claimDate = Carbon::now(Timezone::IST)->format('dmY');
    }

    protected function formatDataForFile(array $data)
    {

        $totalRefunds = array_reduce($data, function ($sum, $item)
        {
            $sum += ($item['refund']['amount']);

            return $sum;
        });

        $totalRefunds = $this->getFormattedAmount($totalRefunds);

        $formattedData = $this->getFileHeadersData($totalRefunds);

        foreach ($data as $row)
        {
            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $refundTime = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST)
                ->format('H:m:s');

            $paymentDate = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $merchant = $row['merchant']['name'];

            $description  = 'REFUND|' . $row['payment']['id']. '|'. $merchant .'|' . $paymentDate . ''; //TODO: add MID data

            $accountNo = $this->getAccountNo($row);

            $branchCode = substr($accountNo, 0 , 4);

            $fields  = [
                'Txn Type' => '1',
                'Account Number' => $accountNo,
                'Branch Code' => $branchCode,
                'Txn Code' => '1408',
                'Txn Date' => $refundDate,
                'Dr / Cr' => 'C',
                'Value Dt' => $refundDate,
                'Txn CCY' => '101',
                'Amt LCY' => $this->getFormattedAmount($row['refund']['amount']),
                'Amt TCY' => $this->getFormattedAmount($row['refund']['amount']),
                'Rate Con' => '1.00',
                'Ref No' => '0',
                'Ref Doc No' => '0',
                'Transaction Desciption' => $description,
                'Benef IC' => $refundTime,
                'Benef Name' => '',
                'Benef Add 1' => '',
                'Benef Add 2' => '',
                'Benef Add 3' => '',
                'Benef City' => '',
                'Benef State' => '',
                'Benef Cntry' => '',
                'Benef Zip' => '',
                'Option' => '30',
                'Issuer Code' => '',
                'Payable At' => '',
                'Flg FDT' => 'N',
                'MIS Account Number' => $accountNo,
            ];

            $formattedData[] = $fields;

        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        return self::BASE_STORAGE_DIRECTORY . self::FILE_NAME . '_' . $this->claimDate;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getAccountNo($row)
    {
        return $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER];
    }

    protected function getFileHeadersData($totalRefunds)
    {
        $todayDate = Carbon::now(Timezone::IST)->format('d/m/Y');

        $formattedData[] = ['PROCESS DATE',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',];

        $formattedData[] = [$todayDate,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',];

        $formattedData[] = [];

        $formattedData[] = Constants::REFUND_COLUMN_HEADERS;

        $poolAccNo = '099917700000180';
        $branchCode = substr($poolAccNo, 0 , 4);

        $todayDateForDescription = Carbon::now(Timezone::IST)->format('d M Y');

        $description = 'Razorpay Refund ' . $todayDateForDescription . '';

        $formattedData[] = [
            'Txn Type' => '1',
            'Account Number' => $poolAccNo,
            'Branch Code' => $branchCode,
            'Txn Code' => '1008',
            'Txn Date' => $todayDate,
            'Dr / Cr' => 'D',
            'Value Dt' => $todayDate,
            'Txn CCY' => '101',
            'Amt LCY' => $totalRefunds,        // refund amount is passed in rupees
            'Amt TCY' => $totalRefunds,        // refund amount is passed in rupees
            'Rate Con' => '1.00',
            'Ref No' => '0',
            'Ref Doc No' => '0',
            'Transaction Desciption' => $description,
            'Benef IC' => '',
            'Benef Name' => '',
            'Benef Add 1' => '',
            'Benef Add 2' => '',
            'Benef Add 3' => '',
            'Benef City' => '',
            'Benef State' => '',
            'Benef Cntry' => '',
            'Benef Zip' => '',
            'Option' => '30',
            'Issuer Code' => '',
            'Payable At' => '',
            'Flg FDT' => 'N',
            'MIS Account Number' => $poolAccNo,
        ];

        return $formattedData;
    }

}
