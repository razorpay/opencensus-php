<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Services\NbPlus\Netbanking;

class Obc extends Base
{
    use FileHandler;

    const FILE_NAME              = 'REFUND_NB_OBC_RAZORPAY';
    const HEADER                 = 'HOBCUTLPRFD';
    const FOOTER                 = 'TOBCUTLPRFD';
    const DELIMITER              = '|';

    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::OBC_NETBANKING_REFUND;

    const GATEWAY                = Payment\Gateway::NETBANKING_OBC;
    const GATEWAY_CODE           = IFSC::ORBC;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Obc/Refund/Netbanking/';

    protected $type = Payment\Entity::BANK;

    public function __construct()
    {
        parent::__construct();

        $this->claimDate = Carbon::now(Timezone::IST)->format('Ymd');
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $count = 0;

        $totalAmount = 0;

        $header = [self::HEADER,  $this->claimDate, $data[0]['terminal']['gateway_merchant_id']];

        $formattedData[0] = $header;

        foreach ($data as $row)
        {
            $fields  = [
                $row['payment']['id'],
                $this->getRefundType($row['payment']['refund_status']),
                $this->getFormattedAmount($row['refund']['amount']),
                $this->getBankPaymentID($row),
                $this->claimDate,
                $this->getFormattedAmount($row['payment']['amount']),
                $row['refund']['id'],
            ];

            $formattedData[] = $fields;

            $count++;

            $totalAmount += $row['refund']['amount'];
        }

        $footer = [self::FOOTER, $this->claimDate, $count, $this->getFormattedAmount($totalAmount)];

        $formattedData[] = $footer;

        $textData = $this->getTextData($formattedData);

        return $textData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
       return static::BASE_STORAGE_DIRECTORY . self::FILE_NAME . '_' . $this->claimDate;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getRefundType($refundType)
    {
        return (($refundType === Payment\RefundStatus::FULL) ? 'C' : 'R');
    }

    protected function getBankPaymentID($row)
    {
        if($row['payment']['cps_route'] == Payment\Entity::NB_PLUS_SERVICE)
        {
            return $row['gateway'][Netbanking::BANK_TRANSACTION_ID];
        }

        return $row['gateway']['bank_payment_id'];
    }
}
