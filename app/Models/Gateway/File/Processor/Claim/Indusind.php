<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Netbanking\Indusind\RefundFileFields;

class Indusind extends NetbankingBase
{
    use FileHandler;

    const FILE_NAME              = 'PGClaimRazorpay';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::INDUSIND_NETBANKING_CLAIM;
    const GATEWAY                = Payment\Gateway::NETBANKING_INDUSIND;
    const BASE_STORAGE_DIRECTORY = 'Indusind/Claims/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
            {
                $bankRefNo = $row['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
            }
            else
            {
                $bankRefNo = $row['gateway']['bank_payment_id'];
            }

            $formattedData[] = [
                RefundFileFields::SERIAL_NO          => $index + 1,
                RefundFileFields::TRANSACTION_ID     => $row['payment']['id'],
                RefundFileFields::BANK_REFERENCE_ID  => $bankRefNo
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
            return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time . $this->mode;
        }

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time;
    }

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $claims = parent::fetchReconciledPaymentsToClaim($begin, $end, $statuses);

        $claims = $claims->reject(function($claim)
        {
            return ($claim->terminal->isDirectSettlement() === true);
        });

        return $claims;
    }
}
