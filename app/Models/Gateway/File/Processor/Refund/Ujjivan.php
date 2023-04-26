<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Ujjivan extends Base
{
    use FileHandler;

    const FILE_NAME              = 'Refund_Razorpay_';
    const EXTENSION              = FileStore\Format::XLS;
    const FILE_TYPE              = FileStore\Type::UJJIVAN_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_UJJIVAN;
    const GATEWAY_CODE           = IFSC::UJVN;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    protected $type              = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Ujjivan/Refund/Netbanking/';

    protected function formatDataForFile(array $data): array
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $accountNo = $this->getAccountNo($row);

            $formattedData[]  = [
                'Transaction date'                                              => $refundDate,
                'Account number'                                                => $accountNo,
                'Amount'                                                        => $this->getFormattedAmount($row['refund']['amount']),
                'Unique reference number (Original transaction Reference ID)'   => $row['payment']['id'],
                'Tran ID(Finacle)'                                              => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                'Transaction Status'                                            => "Y"
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY_His');

        return self::FILE_NAME . $date;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getAccountNo($row)
    {
        return $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER];
    }
}
