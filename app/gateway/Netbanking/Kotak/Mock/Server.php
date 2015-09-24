<?php

namespace Gateway\Netbanking\Kotak\Mock;

use Carbon\Carbon;
use Gateway\Paytm;
use Gateway\Base;
use Gateway\Netbanking;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Models\Payment\Processor\Processor;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        //fot test only
        $resp_url = explode('|',$input['msg']);
        $resp_url1 =$resp_url[7];
        unset($resp_url[7]);
        $input['msg'] = implode('|',$resp_url);
        //-
        $input = $this->getContentFromInput($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'MessageCode'         => $input['MessageCode'],
            'DateTimeInGMT'       => $input['DateTimeInGMT'],
            'MerchantId'          => $input['MerchantId'],
            'TraceNumber'         => $input['TraceNumber'],
            'Amount'              => $input['Amount'],
            'AuthorizationStatus' => 'Y',
            'BankReference'       => random_integer(6),
        );
        $content = ['msg' => $this->getDataWithChecksum($content)];
        $url = route('gateway_payment_callback_kotak');
        $url = $resp_url1;
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function verify($input)
    {
        parent::verify($input);

        $input = $this->getContentFromInput($input);

//        $this->validateActionInput($input,'verify');
        $id = $input['TraceNumber'];

        $payment = (new Netbanking\Base\Repository)->findByTraceIdAndAction(
            $id, Base\Action::AUTHORIZE);

        $content = array(
            'MessageCode'         => $input['MessageCode'],
            'DateTimeInGMT'       => $input['DateTimeInGMT'],
            'MerchantId'          => $input['MerchantId'],
            'TraceNumber'         => $input['TraceNumber'],
            'Amount'              => $payment['Amount'],
            'AuthorizationStatus' => 'Y',
            'BankReference'       => random_integer(6),
        );

        $content = ['msg' => $this->getDataWithChecksum($content)];

        return $this->makeResponse($content);
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

    protected function getDataWithChecksum($data)
    {
        $dataStr = implode("|", $data);
        $dataStrWithSecret = $dataStr . "|" . $this->getHashSecret();

        return (string)$dataStr . '|' . str_pad((crc32($dataStrWithSecret)), 8, '0', STR_PAD_LEFT);
    }

    protected function getHashSecret()
    {
        return 'KMBANK';
//        if ($this->mode === Mode::LIVE)
//        {
//            return $this->config['live_hash_secret'];
//        }
//        else
//        {
//            return $this->config['test_hash_secret'];
//        }


    }

    protected function makeResponse($msg)
    {
        $response = \Response::make($msg);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
