<?php

namespace Gateway\Netbanking\Kotak\Mock;

use Carbon\Carbon;
use Gateway\Paytm;
use Gateway\Base;
use Gateway\Netbanking;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = $this->getContentFromInput($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'MessageCode' => $input['MessageCode'],
            'DateTimeInGMT' => $input['DateTimeInGMT'],
            'MerchantId' => $input['MerchantId'],
            'TraceNumber' => $input['TraceNumber'],
            'Amount' => $input['amount'],
            'AuthorizationStatus' => 'Y',
            'BankReference' => random_integer(6),
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

        $payment = (new Netbanking\Base\Repository)->findByPaymentIdAndAction(
                                                    $id, Base\Action::AUTHORIZE);

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

        $html = $this->prepareVerifyResponseHtml($content);

        return $this->prepareResponse($html);
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

    protected function prepareVerifyResponseHtml($content)
    {
        $content = http_build_query($content);
        $redirectUrl = 'api.razorpay.com' . '?' . $content;

        ob_start();

        require ('VerifyResponseHtml.php');

        $html = ob_get_clean();

        return $html;
    }

    protected function prepareResponse($content)
    {
        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
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
