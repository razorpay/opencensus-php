<?php

namespace RZP\Gateway\Sbiepay;

use Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Sbiepay;
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

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
        'AggregatorId',
        'MerchantId',
        'RefundRequestId',
        'ATRN',
        'PostingAmount',
        'MerchantCurrency',
        'MerchantOrderNo',
        'RefundResponseURL'
    );

    protected static $refundResponseFields = array(
        'RefundRequestId',
        'Status',
        'Message',
        'SBIePayReferenceID',
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
