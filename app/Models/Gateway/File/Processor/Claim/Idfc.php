<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Idfc extends NetbankingBase
{
    use FileHandler;

    static $filename = 'Razorpay';

    const EXTENSION               = FileStore\Format::TXT;
    const FILE_TYPE               = FileStore\Type::IDFC_NETBANKING_CLAIMS;
    const GATEWAY                 = Payment\Gateway::NETBANKING_IDFC;
    const BASE_STORAGE_DIRECTORY  = 'Idfc/Claims/Netbanking/';

    const HEADERS = [
        'RAZORPAYReferenceNumber',
        'BankTransactionReferenceNo',
        'TransactionAmount',
        'STATUS',
        'TRANSACTIONDATE'
    ];

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $formattedData[] = [
                $row['payment']['id'],
                $this->fetchBankReferenceId($row),
                $this->getFormattedAmountString($row['payment']['amount']),
                'SUCCESS',
                Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d-M-Y H:i:s')
            ];
        }

        $initialLine = $this->getInitialLine();

        $formattedData = $this->getTextData($formattedData, $initialLine, '|');

        return $formattedData;
    }

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;
        $end   = Carbon::createFromTimestamp($end)->addDay()->timestamp;

        $claims = $this->repo->payment->fetchReconciledPaymentsForGateway(
            $begin,
            $end,
            static::GATEWAY,
            $statuses
        );

        $claims = $claims->reject(function($claim)
        {
            return ($claim->terminal->isDirectSettlement() === true);
        });

        return $claims;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        return static::BASE_STORAGE_DIRECTORY . $time. '_' . static::$filename;
    }

    protected function getFormattedAmountString(int $amount): String
    {
        $amt = number_format(($amount / 100), 2, '.', '');

        return $amt;
    }

    protected function fetchBankReferenceId($row)
    {
        if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $row['gateway']['bank_transaction_id']; // payment through nbplus service
        }

        return $row['gateway']['bank_payment_id'];
    }
}
