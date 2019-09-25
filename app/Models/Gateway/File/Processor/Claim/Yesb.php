<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Gateway\Mozart\NetbankingYesb\ClaimFields;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;


class Yesb extends Base
{
    const EXTENSION = FileStore\Format::XLS;
    const FILE_TYPE = FileStore\Type::YESB_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_YESB;

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
                ClaimFields::MERCHANT_CODE      => $row['terminal']['gateway_merchant_id'],
                ClaimFields::TRANSACTION_DATE   => $date,
                ClaimFields::PAYMENT_ID         => $row['payment']['id'],
                ClaimFields::BANK_REFERENCE_ID  => $this->fetchBankPaymentId($row['gateway']['raw']),
                ClaimFields::TRANSACTION_AMOUNT => $this->getFormattedAmount($row['payment']['amount']),
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        // the serial no is hardcoded as the file is generated only once
        return 'RAZORPAY'.'_'. $date . '_' . '01';
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function fetchBankPaymentId($data)
    {
        $dataArray = json_decode($data, true);

        return $dataArray['bank_payment_id'];
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
