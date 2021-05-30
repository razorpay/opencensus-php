<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Jkb extends Base
{
    use FileHandler;

    const BANK_REFERENCE_NUMBER = 'BID';
    const MID                   = 'Payee-id';
    const AMOUNT                = 'Transaction_Amt';
    const REFUND_AMOUNT         = 'Refund_Amt';
    const CURRENCY              = 'CRN';
    const TIMESTAMP             = 'TXN_TIME';
    const STATUS                = 'Status';
    const PAYMENT_ID            = 'Order_id';

    const FILE_NAME              = 'JKBRefund_';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::JKB_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_JKB;
    const GATEWAY_CODE           = IFSC::JAKA;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    const BASE_STORAGE_DIRECTORY = 'Jkb/Refund/Netbanking/outgoing/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('YmdHis');

            $formattedData[] = [
                self::BANK_REFERENCE_NUMBER  => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                self::MID                    => $row[Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
                self::AMOUNT                 => $this->getFormattedAmount($row[Entity::PAYMENT][Payment\Entity::AMOUNT]),
                self::REFUND_AMOUNT          => $this->getFormattedAmount($row[Entity::REFUND][Refund\Entity::AMOUNT]),
                self::CURRENCY               => 'INR',
                self::TIMESTAMP              => $date,
                self::STATUS                 => 'S',
                self::PAYMENT_ID             => $row[Entity::PAYMENT][Payment\Entity::ID]
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $dateTime;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }
}
