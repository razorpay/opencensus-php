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
        'MerchantOrderNo',
        'SBIePayReferenceID',
        'Status',
        'Amount',
        'Currency',
        'Paymode',
        'OtherDetails',
        'Reason',
        'BankCode',
        'BankReferenceNumber',
        'TrasactionDate',
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
        'Atrn',
        'merchantId',
        'MerchantOrderNo',
        'ReturnURL',
    );

    protected static $verifyResponseFields = array(
        'MerchantOrderNo',
        'SBIePayReferenceID',
        'Status',
        'Amount',
        'Currency',
        'Paymode',
        'OtherDetails',
        'Reason',
        'BankCode',
        'BankReferenceNumber',
        'TrasactionDate',
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
