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
    protected static $authorizeRequestFields = array(
        'MerchantID',
        'CustomerID',
        'Unknown1',
        'TxnAmount',
        'BankID',
        'Unknown2',
        'Unknown3',
        'CurrencyType',
        'ItemCode',
        'TypeField1',
        'SecurityID',
        'Unknown4',
        'Unknown5',
        'TypeField2',
        'AdditionalInfo1',
        'Unknown6',
        'Unknown7',
        'Unknown8',
        'Unknown9',
        'Unknown10',
        'Unknown11',
        'RU',
        'Checksum',
    );

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
        'MerchantID',
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
