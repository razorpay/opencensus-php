<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class AxisCybersource extends Base
{
    const GATEWAY                = Payment\Gateway::CYBERSOURCE;
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_NAME              = 'Axis_Cybersource_Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::AXIS_CYBERSOURCE_FAILED_REFUND;
    const ACQUIRER               = Payment\Gateway::ACQUIRER_AXIS;
    const BASE_STORAGE_DIRECTORY = 'AxisCybersource/Refund/Failed/';

    const SR_NO                   = 'Sr No';
    const CARD_NUMBER             = 'Card Number';
    const TRANSACTION_AMOUNT      = 'Transaction Amount';
    const MERCHANT_TRAN_REF       = 'Merchant Transaction Reference No.';
    const MID                     = 'MID';
    const REFUND_AMOUNT           = 'Amount to be refunded';
    const TRANSACTION_DATE        = 'Transaction Date and Time';
    const APPROVAL_CODE           = 'Approval Code';
    const RAZORPAY_TRANSACTION_ID = 'Razorpay Payment ID';
    const RAZORPAY_REFUND_ID      = 'Razorpay Refund ID';
    const REF                     = 'Authorized Ref';

    const CARD_GATEWAY_API_REFUND_SPAN = 15552000;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $refunds = $this->repo->refund->fetchFailedCardRefundsToProcessManually(
            $begin,
            $end,
            static::GATEWAY,
            static::ACQUIRER,
            static::CARD_GATEWAY_API_REFUND_SPAN
            );

        return $refunds;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $formattedData[] = [
                self::SR_NO              => $index + 1,
                self::CARD_NUMBER        => $this->getCardNumber($row['card']['iin'], $row['card']['last4']),
                self::TRANSACTION_AMOUNT => $this->getFormattedAmount($row['payment']['amount']),
                self::MERCHANT_TRAN_REF  => $row['gateway']['ref'],
                self::MID                => $row['terminal']['gateway_terminal_id'],
                self::REFUND_AMOUNT      => $this->getFormattedAmount($row['refund']['amount']),
                self::TRANSACTION_DATE   => $this->getFormattedDate($row['payment']['created_at'], 'd/m/y H:m'),
                self::APPROVAL_CODE      => $row['gateway']['authorizationCode'],
                self::REF                => $row['gateway']['ref'],
                self::RAZORPAY_REFUND_ID => $row['refund']['id'],
                self::RAZORPAY_TRANSACTION_ID => $row['payment']['id'],
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
