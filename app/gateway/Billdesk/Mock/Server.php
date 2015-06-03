<?php

namespace Gateway\Billdesk\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Billdesk;
use Gateway\Base;
use Models\Card;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = $this->getContentFromInput($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        // Format - YYYYMMDD
        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y H:i:s');

        $content = array(
            'MerchantID'        => $input['MerchantID'],
            'CustomerID'        => $input['CustomerID'],
            'TxnReferenceNo'    => 'NA',
            'BankReferenceNo'   => 'NA',
            'TxnAmount'         => $input['TxnAmount'],
            'BankID'            => $input['BankID'],
            'BankMerchantID'    => $input['BankID'],
            'TxnType'           => 'INR',
            'CurencyName'       => 'INR',
            'ItemCode'          => 'DIRECT',
            'SecurityType'      => 'NA',
            'SecurityID'        => 'NA',
            'SecurityPassword'  => 'NA',
            'TxnDate'           => $date,
            'AuthStatus'        => '0300',
            'SettlementType'    => 'NA',
            'AdditionalInfo1'   => 'NA',
            'AdditionalInfo2'   => 'NA',
            'AdditionalInfo3'   => 'NA',
            'AdditionalInfo4'   => 'NA',
            'AdditionalInfo5'   => 'NA',
            'AdditionalInfo6'   => 'NA',
            'AdditionalInfo7'   => 'NA',
            'ErrorStatus'       => 'NA',
            'ErrorDescription'  => 'NA',
        );

        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        $request = array(
            'url' => $input['RU'],
            'content' => ['msg' => $msg],
            'method' => 'post'
        );

        return $this->makePostResponse($request);;
    }

    protected function getContentFromInput($input)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $fields = $this->getGatewayInstance()->getFields($name, 'request');

        $content = explode('|', $input['msg']);
        $input = array_combine($fields, $content);

        return $input;
    }
}