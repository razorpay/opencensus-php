<?php

namespace Gateway\Billdesk;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Billdesk;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

trait ResponseFieldsTrait
{
    protected static $callbackResponseFields = array(
        'MercantID',
        'CustomerID',
        'TxnReferenceNo',
        'BankReferenceNo',
        'TxnAmount',
        'BankID',
        'BankMerchantID',
        'TxnType',
        'CurrencyName',
        'ItemCode',
        'SecurityType',
        'SecurityID',
        'SecurityPassword',
        'TxnDate',
        'AuthStatus',
        'SettlementType',
        'AdditionalInfo1',
        'AdditionalInfo2',
        'AdditionalInfo3',
        'AdditionalInfo4',
        'AdditionalInfo5',
        'AdditionalInfo6',
        'AdditionalInfo7',
        'ErrorStatus',
        'ErrorDescription',
        'Checksum',
    );

    protected static $refundResponseFields = array(
        'RequestType',
        'MerchantID',,
        'TxnReferenceNo',
        'TxnDate',
        'CustomerID',
        'TxnAmount',
        'RefAmount',
        'RefDateTime',
        'RefStatus',
        'RefundId',
        'ErrorCode',
        'ErrorReason',
        'ProcessStatus',
        'Checksum',
    );

    protected static $verifyResponseFields = array(
        'RequestType',
        'MercantID',
        'CustomerID',
        'TxnReferenceNo',
        'BankReferenceNo',
        'TxnAmount',
        'BankID',
        'BankMerchantID',
        'TxnType',
        'CurrencyName',
        'ItemCode',
        'SecurityType',
        'SecurityID',
        'SecurityPassword',
        'TxnDate',
        'AuthStatus',
        'SettlementType',
        'AdditionalInfo1',
        'AdditionalInfo2',
        'AdditionalInfo3',
        'AdditionalInfo4',
        'AdditionalInfo5',
        'AdditionalInfo6',
        'AdditionalInfo7',
        'ErrorStatus',
        'ErrorDescription',
        'Filler1',
        'RefundStatus',
        'TotalRefundAmount',
        'LastRefundDate',
        'LastRefundRefNo',
        'QueryStatus',
        'Checksum',
    );

    protected function getFieldsForAction($action)
    {
        $var = $action . 'ResponseFields';

        return self::$$var;
    }
}
