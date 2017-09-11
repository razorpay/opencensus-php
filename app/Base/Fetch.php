<?php

namespace RZP\Base;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Upi;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Status;

class Fetch
{
    const LABEL         = 'label';
    const TYPE          = 'type';
    const VALUES        = 'values';

    const TYPE_STRING   = 'string';
    const TYPE_NUMBER   = 'number';
    const TYPE_BOOLEAN  = 'boolean';
    const TYPE_ARRAY    = 'array';
    const TYPE_OBJECT   = 'object';

    const FIELD_MERCHANT_ID    = 'merchant_id';
    const FIELD_GATEWAY        = 'gateway';
    const FIELD_PAYMENT_ID     = 'payment_id';
    const FIELD_PAYMENT_STATUS = 'payment_status';
    const FIELD_METHOD         = 'method';
    const FIELD_WALLET         = 'wallet';
    const FIELD_UPI            = 'upi';

    public static function getCommonFields()
    {
        $gatewayList    = config('gateway.available');
        $statusList     = Status::getStatusList();
        $methodList     = Method::getMethodsNamesMap();
        $walletList     = Wallet::getWalletNetworkNamesMap();
        $upiList        = Upi::getFullBankNamesMap();

        return [
            // Merchant
            static::FIELD_MERCHANT_ID => [
                self::LABEL     => 'Merchant Id',
                self::TYPE      => self::TYPE_STRING
            ],
            // Gateway
            static::FIELD_GATEWAY => [
                self::LABEL     => 'Gateway',
                self::TYPE      => self::TYPE_ARRAY,
                self::VALUES    => $gatewayList
            ],
            //Payment
            static::FIELD_PAYMENT_STATUS => [
                self::LABEL     => 'Status',
                self::TYPE      => self::TYPE_ARRAY,
                self::VALUES    => $statusList
            ],
            static::FIELD_PAYMENT_ID => [
                self::LABEL     => 'Payment Id',
                self::TYPE      => self::TYPE_STRING,
            ],
            // Method
            static::FIELD_METHOD => [
                self::LABEL     => 'Method',
                self::TYPE      => self::TYPE_OBJECT,
                self::VALUES    => $methodList
            ],
            // Wallet
            static::FIELD_WALLET => [
                self::LABEL     => 'Wallet',
                self::TYPE      => self::TYPE_OBJECT,
                self::VALUES    => $walletList
            ],
            // UPI
            static::FIELD_UPI => [
                self::LABEL     => 'UPI',
                self::TYPE      => self::TYPE_OBJECT,
                self::VALUES    => $upiList
            ]
        ];
    }
}
