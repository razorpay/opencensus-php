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

    public function getFields($action, $type = 'response')
    {
        if ($type === 'response')
        {
            return $this->getFieldsForAction($action);
        }
        else
        {
            $var = $action . 'RequestFields';

            return self::$$var;
        }
    }
}
