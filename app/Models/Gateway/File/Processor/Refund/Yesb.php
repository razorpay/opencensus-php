<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Gateway\Mozart\NetbankingYesb\RefundFields;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Yesb extends Base
{
    use FileHandler;

    const FILE_NAME                  = '_REFUND_';
    const SERIAL_NO                  = '_01';
    const EXTENSION                  = FileStore\Format::XLS;
    const FILE_TYPE                  = FileStore\Type::YESB_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_YESB;
    const PAYMENT_BANK               = 'Yesbank';
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::YESB;

    // This value needs be stored as this is used in the file name
    protected $gatewayMerchantId;

    protected function formatDataForFile(array $data)
    {
        $content = [];

        $this->setGatewayMerchantId($data[0]['terminal']['gateway_merchant_id']);

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('dmY');

            $content[] = [
                RefundFields::MERCHANT_CODE      => $row['terminal']['gateway_merchant_id'],
                RefundFields::TRANSACTION_DATE   => $date,
                RefundFields::PAYMENT_ID         => $row['payment']['id'],
                RefundFields::BANK_REFERENCE_ID  => $this->fetchBankPaymentId($row['gateway']['raw']),
                RefundFields::TRANSACTION_AMOUNT => $this->getFormattedAmount($row['payment']['amount']),
                RefundFields::REFUND_AMOUNT      => $this->getFormattedAmount($row['refund']['amount']),
            ];
        }

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        // the serial no is hardcoded as the file is generated only once
        return $this->gatewayMerchantId. self::FILE_NAME . $date . '_' . '01';
    }

    protected function fetchBankPaymentId($data)
    {
        $dataArray = json_decode($data, true);

        return $dataArray['bank_payment_id'];
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function setGatewayMerchantId($id)
    {
        if ($this->gatewayMerchantId === null)
        {
            $this->gatewayMerchantId = $id;
        }
    }
}
