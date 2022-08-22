<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Mozart\NetbankingYesb\ClaimFields;

class Yesb extends NetbankingBase
{
    const EXTENSION = FileStore\Format::XLS;
    const FILE_TYPE = FileStore\Type::YESB_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_YESB;
    const BASE_STORAGE_DIRECTORY     = 'Yesbank/Claim/Netbanking/';

    // This value needs be stored as this is used in the file name
    protected $gatewayMerchantId;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $this->setGatewayMerchantId($data[0]['terminal']['gateway_merchant_id']);

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');

            $formattedData[] = [
                ClaimFields::MERCHANT_CODE      => 'RAZORPAY',
                ClaimFields::TRANSACTION_DATE   => strval($date),
                ClaimFields::PAYMENT_ID         => strval($row['payment']['id']),
                ClaimFields::BANK_REFERENCE_ID  => strval($this->fetchBankPaymentId($row)),
                ClaimFields::TRANSACTION_AMOUNT => $this->getFormattedAmount($row['payment']['amount']),
            ];
        }

        return $formattedData;
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

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        // the serial no is hardcoded as the file is generated only once
        return static::BASE_STORAGE_DIRECTORY . 'RAZORPAY'.'_'. $date . '_' . '01';
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function fetchBankPaymentId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS)))
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $data['gateway']['data']['bank_payment_id']; // payment through api - mozart
    }

    protected function setGatewayMerchantId($id)
    {
        if ($this->gatewayMerchantId === null)
        {
            $this->gatewayMerchantId = $id;
        }
    }

    protected function fetchGatewayEntities($paymentIds)
    {
        return $this->repo->mozart->fetchByPaymentIdsAndAction($paymentIds, Action::AUTHORIZE);
    }
}
