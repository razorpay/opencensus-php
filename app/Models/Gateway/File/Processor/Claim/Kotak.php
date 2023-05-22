<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Kotak extends NetbankingBase
{
    use FileHandler;

    const TPV_FILE_NAME          = 'Kotak_Netbanking_Claim_OTRAZORPAY';
    const NON_TPV_FILE_NAME      = 'Kotak_Netbanking_Claim_OSRAZORPAY';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::KOTAK_NETBANKING_CLAIM;
    const GATEWAY                = Payment\Gateway::NETBANKING_KOTAK;
    const BASE_STORAGE_DIRECTORY = 'Kotak/Claims/Netbanking/';

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;
        $end = Carbon::createFromTimestamp($end)->addDay()->timestamp;
        $tpv = $this->gatewayFile->getTpv();

        $claims = $this->repo->payment->fetchReconciledPaymentsForTpv(
            $begin,
            $end,
            static::GATEWAY,
            $statuses,
            $tpv,
            ['terminal']
        );

        $claims = $claims->reject(function($claim)
        {
            return ($claim->terminal->isDirectSettlement() === true);
        });

        return $claims;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];
        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d-M-Y');

            $formattedData[] = [
                $index + 1,
                $this->fetchBankGatewayCode($row),
                $date,
                $this->fetchBankGatewayId($row),
                $row['payment']['amount'] / 100,
                $this->fetchBankPaymentId($row)
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $name = ($this->getTpv() === true) ? static::TPV_FILE_NAME : static::NON_TPV_FILE_NAME;

        return static::BASE_STORAGE_DIRECTORY . $name . '_' . $this->mode . '_' . $time;
    }
    protected function fetchBankPaymentId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway']['bank_transaction_id']; // payment through nbplus service
        }

        return $data['gateway']['bank_payment_id'];
    }
    protected function fetchBankGatewayCode($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['terminal']['gateway_merchant_id']; // payment through nbplus service
        }
        return $data['gateway']['merchant_code'];
    }
    protected function fetchBankGatewayId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['payment']['id']; // payment through nbplus service
        }

        return $data['gateway']['int_payment_id'];
    }
}
