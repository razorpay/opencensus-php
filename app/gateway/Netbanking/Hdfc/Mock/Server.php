<?php

namespace Gateway\Netbanking\Hdfc\Mock;

use Carbon\Carbon;
use Gateway\Paytm;
use Gateway\Base;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'MerchRefNo'    => $input['MerchantRefNo'],
            'TxnAmount'     => $input['TxnAmount'],
            'TxnCurrency'   => 'INR',
            'ClientCode'    => $input['ClientCode'],
            'TxnScAmount'   => $input['TxnScAmount'],
            'CheckSum'      => '',
            'BankRefNo'     => random_integer(6),
            'MerchantCode'  => $input['MerchantCode'],
            'Date'          => $input['Date'],
            'StFailFlg'     => 'N',
            'StSucFlg'      => 'N',
            'Message'       => '',
            'fldSessionNbr' => '5',
        );

        $content['CheckSum'] = $this->getCallbackChecksum($content);

        $url = $input['DynamicUrl'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $id = $input['MerchantRefNo'];

        $payment = (new Paytm\Repository)->findByPaymentIdAndAction(
                                                    $id, Action::AUTHORIZE);

        $content = array(
            'ClientCode'        => $payment['client_code'],
            'MerchantCode'      => $input['MerchantCode'],
            'TxnAmount'         => $payment['amount'],
            'MerchantRefNo'     => $payment['id'],
            'SuccessStaticFlag' => 'N',
            'FailureStaticFlag' => 'N',
            'Date'              => $input['Date'],
            'TransactionId'     => 'XTXTV01',
            'flgVerify'         => 'Y',
            'BankRefNo'         => $payment['bank_payment_id'],
            'flgSuccess'        => 'S',
            'Message'           => $payment['error_message'],
        );

        return $this->prepareResponse($content);
    }

    protected function getCallbackChecksum($input)
    {
        $paramsOrder = array(
            'ClientCode',
            'MerchantCode',
            'TxnCurrency',
            'TxnAmount',
            'TxnScAmount',
            'MerchRefNo',
            'StSucFlg',
            'StFailFlg',
            'Date',
            'Ref1',
            'Ref2',
            'Ref3',
            'Ref4',
            'Ref5',
            'Ref6',
            'Ref7',
            'Ref8',
            'Ref9',
            'Ref10',
            'Ref11',
            'Date1',
            'Date2',
            'BankRefNo',
            'Message',
        );

        $str = '';

        $data = [];

        foreach ($paramsOrder as $param)
        {
            if (isset($input[$param]))
            {
                $data[$param] = $input[$param];
            }
        }

        return $this->generateHash($data);
    }

    protected function prepareResponse($content)
    {
        $body = http_build_query($content);
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'text/plain;charset=iso-8859-1');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
