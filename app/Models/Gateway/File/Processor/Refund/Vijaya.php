<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Vijaya extends Base
{
    use FileHandler;

    const FILE_NAME                  = '000000000010';
    const EXTENSION                  = FileStore\Format::IN;
    const FILE_TYPE                  = FileStore\Type::VIJAYA_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_VIJAYA;
    const PAYMENT_BANK               = 'VijayaBank';
    const REFUND                     = 'RFND';
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::VIJB;
    const BASE_STORAGE_DIRECTORY     = 'Vijaya/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $index = 0;

        foreach ($data as $row)
        {
            $formattedData[] = [
                ++$index,
                $row['payment']['id'],
                self::REFUND,
                self::PAYMENT_BANK,
                number_format($row['refund']['amount'] / 100, 2, '.', ''),
                $row['gateway']['bank_payment_id'],
            ];
        }

        $formattedData = $this->getTextData($formattedData, '', '||');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::BASE_STORAGE_DIRECTORY . self::FILE_NAME . $date . '01';
    }
}
