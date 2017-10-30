<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Kotak extends Base
{
    use FileHandler;

    const TPV_FILE_NAME          = 'Kotak_Netbanking_Refund_OTRAZORPAY';
    const NON_TPV_FILE_NAME      = 'Kotak_Netbanking_Refund_OSRAZORPAY';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::KOTAK_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_KOTAK;
    const GATEWAY_CODE           = IFSC::KKBK;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    protected $type = Payment\Entity::BANK;

    protected function formatDataForFile()
    {
        $formattedData = [];

        $totalAmount = 0;

        foreach ($this->data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d-M-Y');

            $formattedData[] = [
                $index + 1,
                $row['gateway']['merchant_code'],
                $date,
                $row['gateway']['int_payment_id'],
                $row['refund']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
            ];

            $totalAmount += $row['refund']['amount'] / 100;
        }

        $name = $this->getFileToWriteName();

        // First Line in the file is expected to be of the format
        // Format : FileName|ItemsCount|TotalAmount(Rs.)|CHECKSUM
        $initialLine = $name .'|'. count($formattedData) . '|' .$totalAmount . '|CHECKSUM' . "\r\n";

        $formattedData = $this->getTextData($formattedData, $initialLine);

        return $formattedData;
    }

    public function sendFile()
    {
        return;
    }


    protected function getFileToWriteName($ext = FileStore\Format::TXT)
    {
        return $this->getFileToWriteNameWithoutExt() . '.' . $ext;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $name = ($this->getTpv() === true) ? static::TPV_FILE_NAME : static::NON_TPV_FILE_NAME;

        return $name . '_' . $this->mode . '_' . $time;
    }
}
