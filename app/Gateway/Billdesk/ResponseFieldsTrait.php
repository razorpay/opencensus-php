<?php

namespace RZP\Gateway\Billdesk;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Billdesk;

trait ResponseFieldsTrait
{
    protected static $authorizeRequestFields = array(
        'MerchantID',
        'CustomerID',
        'AccountNumber',
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
        'AdditionalInfo2',
        'AdditionalInfo3',
        'AdditionalInfo4',
        'AdditionalInfo5',
        'AdditionalInfo6',
        'AdditionalInfo7',
        'RU',
        'Checksum',
    );

    protected static $callbackResponseFields = array(
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
        'Checksum',
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
        'RefStatus',
        'RefAmount',
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
