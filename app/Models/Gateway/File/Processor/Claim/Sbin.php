<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\FileHandler;

use Carbon\Carbon;

class Sbin extends Base
{
    use FileHandler;

    const GATEWAY = 'netbanking_sbi';

    const HEADERS = [
        'AG_REF',
        'PG_REF',
        'AMOUNT',
        'STATUS',
        'DATE',
    ];

    const EXTENSION = FileStore\Format::TXT;

    const FILE_TYPE = FileStore\Type::SBI_NETBANKING_CLAIM;

    const FILE_NAME = 'SBI_CLAIM';

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('2048M');
    }

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $claims = parent::fetchReconciledPaymentsToClaim($begin, $end, $statuses);

        $claims = $claims->reject(function($claim)
        {
            return $claim->isEmandate() === true;
        });

        return $claims;
    }

    // SBI wants claims for payments between 8pm to 8pm cycle
    protected function fetchPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($begin, Timezone::IST)
                         ->subHours(4)
                         ->getTimestamp();

        $end = Carbon::createFromTimestamp($end, Timezone::IST)
                       ->subHours(4)
                       ->getTimestamp();

        $claims = parent::fetchPaymentsToClaim($begin, $end, $statuses);

        $claims = $claims->reject(function($claim)
        {
            if (($claim->isEmandate() === true) or ($claim->terminal->isDirectSettlement() === true))
            {
                return true;
            }
            return false;
        });

        return $claims;
    }

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)
                ->format('d/m/y H:i:s');

            $formattedData[] = [
                $row['payment']['id'],
                $row['gateway']['bank_payment_id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                'SUCCESS',
                $date,
            ];
        }
        $initialLine = $this->getInitialLine('|');

        $formattedData = $this->getTextData($formattedData, $initialLine, '|');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        return static::FILE_NAME . '_' . $time;
    }
}
