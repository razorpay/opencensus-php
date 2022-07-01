<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class AublCorp extends Base
{

    use FileHandler;

    const SR_NO                 = 'Sr.No';
    const REFUND_ID             = 'Refund ID';
    const TXN_DATE              = 'Txn Date';
    const BAK_REFERENCE_NUMBER  = 'Bank Ref No.';
    const PGI_REFERENCE_NO      = 'RZP Reference No.';
    const TXN_AMT               = 'Txn Amount (Rs Ps)';
    const REFUND_AMOUNT         = 'Refund Amount (Rs Ps)';

    const FILE_NAME                  = 'AUBL_CORP_Refund_';
    const EXTENSION                  = FileStore\Format::XLS;
    const FILE_TYPE                  = FileStore\Type::AUBL_CORP_NETBANKING_REFUND;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = Payment\Processor\Netbanking::AUBL_C;
    const GATEWAY                    = Payment\Gateway::NETBANKING_AUSF;
    const BASE_STORAGE_DIRECTORY     = 'Aubl/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $content = [];

        $count = 1;

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $bankRefNo = $row['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service

            $content[] = [
                self::SR_NO                 => $count++,
                self::REFUND_ID             => $row['refund']['id'],
                self::TXN_DATE              => $transactionDate,
                self::BAK_REFERENCE_NUMBER  => $bankRefNo,
                self::PGI_REFERENCE_NO      => $row['payment']['id'],
                self::TXN_AMT               => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT         => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('Ymd');

        return self::BASE_STORAGE_DIRECTORY . self::FILE_NAME . $date;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

}
