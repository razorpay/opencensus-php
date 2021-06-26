<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Mozart\NetbankingNsdl\RefundFields;

class Nsdl extends Base
{
    use FileHandler;

    const FILE_NAME              = 'pgtxnrefund_Et3lZ2p9bx_';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::NSDL_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_NSDL;
    const GATEWAY_CODE           = IFSC::NSPB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Nsdl/Refund/Netbanking/';

    const HEADERS = RefundFields::REFUND_FIELDS;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d-m-Y H:i:s');

            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('d-m-Y H:i:s');

            $formattedData[] = [
                RefundFields::TRANSACTION_DATE         => $transactionDate,
                RefundFields::CHANNELID                => 'iSBjlAJqAdqdPdncFqGV',
                RefundFields::PARTNERID                => 'Et3lZ2p9bx',
                RefundFields::PGTXNID                  =>  $row['payment']['id'],
                RefundFields::REFUNDAMOUNT             => $this->formatAmount($row['refund']['amount']),
                RefundFields::REFUNDTXNDATE            => $refundDate,
                RefundFields::CURRENCY                 => 'INR',
                RefundFields::BANKREFNO                => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                RefundFields::REMARKS                  => 'NA',
            ];
        }

        $formattedData = $this->getTextData($formattedData,"",",");

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time;
    }

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }
}
