<?php

namespace RZP\Gateway\Netbanking\Kotak;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Kotak;

trait ResponseFieldsTrait
{
    protected static $authorizeRequestFields = array(
        'MessageCode',
        'DateTimeInGMT',
        'MerchantId',
        'TraceNumber',
        'Amount',
        'TransactionDescription',
        'Checksum',
    );

    protected static $authorizeNewRequestFields = array(
        'MessageCode',
        'DateTimeInGMT',
        'MerchantId',
        'TraceNumber',
        'Amount',
        'TransactionDescription',
        'FUP-1',
        'FUP-2',
        'FUP-3',
        'Checksum',
    );

    protected static $callbackResponseFields = array(
        'MessageCode',
        'DateTimeInGMT',
        'MerchantId',
        'TraceNumber',
        'Amount',
        'AuthorizationStatus',
        'BankReference',
        'Checksum',
    );

    protected static $verifyRequestFields = array(
        'MessageCode',
        'DateTimeInGMT',
        'MerchantId',
        'TraceNumber',
        'Future1',
        'Future2',
        'Checksum',
    );

    protected static $verifyResponseFields = array(
        'MessageCode',
        'DateTimeInGMT',
        'MerchantId',
        'TraceNumber',
        'Amount',
        'AuthorizationStatus',
        'BankReference',
        'Checksum',
    );

    public function getFieldsForAction($action)
    {
        $var = $action . 'ResponseFields';

        return self::$$var;
    }

    public function getFields($action, $type = 'response', $variant)
    {
        if ($type === 'response')
        {
            return $this->getFieldsForAction($action);
        }
        else
        {
            if ($variant === Gateway::newIntegration and $action === 'authorize')
            {
                $var = $action . 'NewRequestFields';
            }

            else
            {
                $var = $action . 'RequestFields';
            }

            return self::$$var;
        }
    }
}
