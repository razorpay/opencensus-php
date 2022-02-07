<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;


class HdfcCorp extends NetbankingBase
{

    const FILE_NAME = 'Success MIS ';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::HDFC_CORP_NETBANKING_CLAIMS;
    const BASE_STORAGE_DIRECTORY = 'HDFC_Corp/Claims/Netbanking/';

    const GATEWAY = Payment\Gateway::NETBANKING_HDFC;
    const BANKCODE = Payment\Processor\Netbanking::HDFC_C;

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;
        $end = Carbon::createFromTimestamp($end)->addDay()->timestamp;

        $claims = $this->repo->payment->fetchReconciledPaymentsForGatewayWithBankCode(
            $begin,
            $end,
            static::GATEWAY,
            static::BANKCODE,
            $statuses
        );

        return $claims;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $count = 1;
        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('Ymd');

            $formattedData[] = [
                'Sr No.'        => $count++,
                'Line No.'      => "NA",
                'Record Line'   => "NA",
                'PGIRefNo'      => $row['payment']['id'],
                'BankRefNo'     => $this->fetchBankPaymentId($row),
                'TxnAmount'     => $this->getFormattedAmount($row['payment']['amount']),
                'TxnDate'       => $date,
                'BillerId'      => "ESICMOPS",
                'MeBankId'      => "ESICCB",
                'AuthStatus'    => '300'
            ];
        }

        return $formattedData;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $claimDate = Carbon::now(Timezone::IST)->format('d M Y');

        return self::BASE_STORAGE_DIRECTORY . self::FILE_NAME . '_' . $claimDate;
    }

    protected function fetchBankPaymentId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID];
        }

        return $data['gateway']['data']['bank_payment_id'];
    }

}
