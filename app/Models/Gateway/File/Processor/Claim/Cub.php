<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Mozart\NetbankingCub\ClaimFields;

class Cub extends NetbankingBase
{
    use FileHandler;

    // TODO : find file name to use
    const FILE_NAME = 'CUB SETTLEMENT_';
    const EXTENSION = FileStore\Format::TXT;
    const FILE_TYPE = FileStore\Type::CUB_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_CUB;
    const BASE_STORAGE_DIRECTORY = 'Cub/Claim/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');

            $formattedData[] = [
                ClaimFields::PAYMENT_ID         => $row['payment']['id'],
                ClaimFields::TRANSACTION_AMOUNT => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                ClaimFields::BANK_REFERENCE_ID  => $this->fetchBankPaymentId($row),
                ClaimFields::TRANSACTION_DATE   => $date,
            ];
        }

        $prependLine = implode('~', ClaimFields::COLUMNS) . "\r\n";

        $formattedData = $this->getTextData($formattedData, $prependLine, '~');

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('YdmHis');

        return  static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $dateTime;
    }

    protected function fetchGatewayEntities($paymentIds)
    {
        return $this->repo->mozart->fetchByPaymentIdsAndAction($paymentIds, Action::AUTHORIZE);
    }

    protected function fetchBankPaymentId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway']['bank_transaction_id'];
        }

        return $data['gateway']['data']['bank_payment_id'];
    }
}
