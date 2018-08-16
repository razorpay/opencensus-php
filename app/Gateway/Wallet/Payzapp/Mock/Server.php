<?php

namespace RZP\Gateway\Wallet\Payzapp\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Payzapp\TransactionType;
use RZP\Models\Card;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = json_decode($input['json'], true);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $wibmoTxnId = $this->getWibmoTxnId();

        $content = array(
            'resCode'                   =>  '000',
            'resDesc'                   =>  'SUCCESS',
            'actionCode'                =>  '0',
            'additionalUserInputData'   =>  '[object Object]',
            'msgHash'                   =>  'cOMsjV/9Vw3dBN5ECr54tEl7ITCwWj4O+pWbqrz8ZuM=',
            'dataPickUpCode'            =>  '201512091604066125oW33aC8:2jG2cS5gT9',
            'wibmoTxnId'                =>  $wibmoTxnId,
            'merTxnId'                  =>  $input['transactionInfo']['merTxnId'],
        );

        $contentToHash = array(
            'wpay'              => 'wpay',
            'merId'             => $input['merchantInfo']['merId'],
            'merAppId'          => $input['merchantInfo']['merAppId'],
            'merTxnId'          => $input['transactionInfo']['merTxnId'],
            'merAppData'        => $input['transactionInfo']['merAppData'],
            'txnAmount'         => $input['transactionInfo']['txnAmount'],
            'txnCurrency'       => '356',
            'wibmoTxnId'        => $content['wibmoTxnId'],
            'resCode'           => $content['resCode'],
            'dataPickUpCode'    => $content['dataPickUpCode'],
        );

        $content['msgHash'] = $this->getGatewayInstance()
                                   ->getHashForAuthorizeResponse($contentToHash);

        $this->content($content, __FUNCTION__);

        $request = array(
            'url' => $input['wIapDefaults']['wIapReturnUrl'],
            'content' => $content,
            'method' => 'post',
        );

        return $this->makePostResponse($request);
    }

    public function callback($input)
    {
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $txnTypeToCodeMap = TransactionType::$codes;

        $txnCodeToTypeMap = array_flip($txnTypeToCodeMap);

        if ($txnCodeToTypeMap[strval($input['transaction_type'])] === 'SETTLE')
        {
            echo "Not supposed to reach here";
        }

        $txnId = $this->getAcosaTxnId();

        $txnResponse = 'transaction_id='.$txnId.'&status=50020&pg_error_code=0&'.
        'pg_error_msg=No Error&merchant_reference_no='.$input['merchant_reference_no'];

        return $this->makeResponse($txnResponse);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $refundTxnId = $this->getAcosaTxnId();

        $this->content($input, 'refund');

        $refundResponse = 'status=50020&pg_error_code=0&pg_error_detail=No Error&
        &new_transaction_id='.$refundTxnId.'&new_merchant_reference_no='.$input['new_merchant_reference_no'];

        return $this->makeResponse($refundResponse);
    }

    protected function getContentFromInput($input)
    {
        return $input;
    }

    protected static function getWibmoTxnId()
    {
        return random_alphanum_string(21);
    }

    protected static function getAcosaTxnId()
    {
        return random_int(10000000,99999999);
    }

    protected static function getDataPickupCode($wibmoTxnId)
    {
        return $wibmoTxnId . ':' . random_alphanum_string(10);
    }
}
