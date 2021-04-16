<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicCollection;

class IcicFirstData extends Base
{
    const GATEWAY          = Payment\Gateway::FIRST_DATA;
    const EXTENSION        = FileStore\Format::XLSX;
    const ACQUIRER         =  Payment\Gateway::ACQUIRER_ICIC;

    const FILE_NAME              = 'Icic_FirstData_Failed_Refunds';
    const FILE_TYPE              = FileStore\Type::ICIC_FIRST_DATA_FAILED_REFUND;
    const BASE_STORAGE_DIRECTORY = 'IcicFirstData/Refund/Failed/';

    const SR_NO                   = 'Sr No';
    const MERCHANT_TRANSACTION_ID = 'Merchant Transaction ID';
    const REFUND_TYPE             = 'Refund Type';
    const TRANSACTION_DATE        = 'Transaction date';
    const REFUND_DATE             = 'refund Date';
    const ORDER_ID                = 'Order ID';
    const REFUND_AMOUNT           = 'Refund Amount';
    const PAYMENT_AMOUNT          = 'Payment Amount';
    const STORE_ID                = 'Store ID';
    const AUTH_CODE               = 'Auth Code';
    const CARD_IIN                = 'Card IIN';
    const LAST_FOUR_CARD_NUM      = 'Card Number Last Four';
    const SESSION_ID              = 'Session Id';
    const FT_NUMBER               = 'F.T. Number';

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
            if (isset($row['gateway']) === false)
            {
                $this->trace->alert(
                    TraceCode::GATEWAY_FILE_ERROR_GENERATING_DATA,
                    [
                        'message'       => 'Gateway entity not found',
                        'payment_id'    => $row['payment']['id'],
                        'refund_id'     => $row['refund']['id'],
                    ]);

                continue;
            }

            $formattedData[] = [
                self::SR_NO                   => $index + 1,
                self::MERCHANT_TRANSACTION_ID => $row['refund']['id'],
                self::REFUND_TYPE             => $row['payment']['refund_status'],
                self::REFUND_DATE             => $this->getFormattedDate($row['refund']['last_attempted_at'], 'Y/m/d'),
                self::SESSION_ID              => $row['gateway']['caps_payment_id'],
                self::FT_NUMBER               => str_pad($row['gateway']['gateway_transaction_id'],
                                                         15, '0', STR_PAD_LEFT),
                self::AUTH_CODE               => $row['gateway']['auth_code'],
                self::TRANSACTION_DATE        => $this->getFormattedDate($row['payment']['created_at'], 'Y/m/d'),
                self::ORDER_ID                => $row['payment']['id'],
                self::PAYMENT_AMOUNT          => $this->getFormattedAmount($row['payment']['amount']),
                self::REFUND_AMOUNT           => $this->getFormattedAmount($row['refund']['amount']),
                self::STORE_ID                => $row['terminal']['gateway_merchant_id'],
                self::CARD_IIN                => $row['card']['iin'],
                self::LAST_FOUR_CARD_NUM      => $row['card']['last4'],
            ];
        }

        return $formattedData;
    }

    protected function addGatewayEntitiesToData(array $data, PublicCollection $refunds)
    {
        $gateway = static::GATEWAY;

        $paymentIds = $refunds->pluck('payment_id')->toArray();

        $gatewayEntitiesAll = $this->repo
                                   ->$gateway
                                   ->fetchByPaymentIdsAndActions($paymentIds,
                                       [
                                           Action::PURCHASE,
                                           Action::AUTHORIZE,
                                           Action::CAPTURE
                                       ]);

        $gatewayEntities = [];

        foreach ($gatewayEntitiesAll as $gatewayEntity)
        {
            $paymentId = $gatewayEntity['payment_id'];

            // FirstData requires FT Number to process the refunds, Now for different transactions
            // We get different FT Numbers as IpgTransactionId, thus if the payment has capture
            // entity, We will only use that and override the authorize entity
            if (isset($gatewayEntities[$paymentId]) === true)
            {
                if ($gatewayEntity['action'] === Action::CAPTURE)
                {
                    $gatewayEntities[$paymentId] = $gatewayEntity;
                }
            }
            else
            {
                $gatewayEntities[$paymentId] = $gatewayEntity;
            }
        }

        $data = array_map(function($row) use ($gatewayEntities)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayEntities[$paymentId]) === true)
            {
                $row['gateway'] = $gatewayEntities[$paymentId]->toArray();
            }

            return $row;
        }, $data);

        return $data;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
