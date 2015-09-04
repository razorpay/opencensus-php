<?php

namespace Gateway\Sbiepay;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Sbiepay;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

trait ResponseFieldsTrait
{
    protected static $authorizeRequestFields = array(
        'MerchantId',
        'OperatingMode',
        'MerchantCountry',
        'MerchantCurrency',
        'PostingAmount',
        'OtherDetails',
        'SuccessURL',
        'FailURL',
        'AggregatorId',
        'MerchantOrderNo',
        'MerchantCustomerID',
        'Paymode',
        'Accesmedium',
        'TransactionSource',
    );

    protected static $callbackResponseFields = array(
        'orderReqId',
        'atrn',
        'transStatus',
        'amount',
        'currency',
        'paymode',
        'otherDetails',
        'bankCode',
        'bankRefNumber',
        'trascationdate',
        'Country',
        'CIN',
        'AdditionalInfo1',
        'AdditionalInfo2',
        'AdditionalInfo3',
        'AdditionalInfo4',
        'AdditionalInfo5',
        'AdditionalInfo6',
        'AdditionalInfo7',
        'AdditionalInfo8',
        'AdditionalInfo9',
    );

    protected static $refundRequestFields = array(
        'RequestType',
        'MerchantID',
        'TxnReferenceNo',
        'TxnDate',
        'CustomerID',
        'TxnAmount',
        'RefAmount',
        'RefDateTime',
        'MerchantRefNo',
        'Filler1',
        'Filler2',
        'Filler3',
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

    protected static $verifyRequestFields = array(
        'RequestType',
        'Merchant ID',
        'Customer ID',
        'Current Date/ Timestamp',
        'Checksum',
    );

    protected static $verifyResponseFields = array(
        'RequestType',
        'MerchantID',
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
