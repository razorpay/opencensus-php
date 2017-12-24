<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Netbanking\Indusind\RefundFileFields;

class Indusind extends Base
{
    use FileHandler;

    const FILE_NAME = 'PGClaimRazorpay';
    const EXTENSION = FileStore\Format::TXT;
    const FILE_TYPE = FileStore\Type::INDUSIND_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_INDUSIND;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                RefundFileFields::SERIAL_NO          => $index + 1,
                RefundFileFields::TRANSACTION_ID     => $row['payment']['id'],
                RefundFileFields::BANK_REFERENCE_ID  => $row['gateway']['bank_payment_id']
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        if ($this->isTestMode() === true)
        {
            return self::FILE_NAME . $time . $this->mode;
        }

        return self::FILE_NAME . $time;
    }
}
